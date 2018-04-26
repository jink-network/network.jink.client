<?php
declare(strict_types=1);

namespace AppBundle\Command;

use AppBundle\Command\Trader\App;
use AppBundle\Command\Trader\Trade\Trade;
use AppBundle\Command\Trader\View\View;
use AppBundle\Entity\Event;
use AppBundle\Entity\Log;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Class TradeCommand
 * @package AppBundle\Command
 */
class TradeCommand extends ContainerAwareCommand
{
    /** @var SymfonyStyle */
    private $io;

    /** @var OutputInterface */
    private $output;

    /** @var array */
    private $logs = [];

    /** @var array */
    private $events = [];

    /**
     * Configure console command
     */
    protected function configure()
    {
        $this
            ->setName('jink:trade')
            ->addArgument("binance_api_key", InputArgument::REQUIRED, "Binance API KEY")
            ->addArgument("binance_api_secret", InputArgument::REQUIRED, "Binance API Secret")
            ->addArgument("bittrex_api_key", InputArgument::REQUIRED, "Bittrex API Secret")
            ->addArgument("bittrex_api_secret", InputArgument::REQUIRED, "Bittrex API Secret")
            ->addArgument("kucoin_api_key", InputArgument::REQUIRED, "Kucoin API Secret")
            ->addArgument("kucoin_api_secret", InputArgument::REQUIRED, "Kucoin API Secret")
            ->addArgument("jink_api_url", InputArgument::REQUIRED, "JiNK API URL")
            ->addArgument("jink_api_key", InputArgument::REQUIRED, "JiNK API KEY")
            ->addArgument("jink_client_id", InputArgument::REQUIRED, "JiNK ClientID")
            ->addArgument("trade", InputArgument::REQUIRED, "Trade JSON")
            ->addOption("dev", "d", InputOption::VALUE_NONE, 'Run in dev mode?')
            ->setDescription('Trade token to desired level');
    }


    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $app = new App(
            $input->getArgument('binance_api_key'),
            $input->getArgument('binance_api_secret'),
            $input->getArgument('bittrex_api_key'),
            $input->getArgument('bittrex_api_secret'),
            $input->getArgument('kucoin_api_key'),
            $input->getArgument('kucoin_api_secret'),
            $input->getArgument('jink_api_key'),
            $input->getArgument('jink_api_url'),
            $input->getOption('dev'),
            $input->getArgument('jink_client_id')
       );

        if (!$app->isProduction()) {
            $view = new View($input, $output);
            $view->getOutput()->write(sprintf("\033\143"));
        }

        $tradeJson = $input->getArgument('trade');
        $tradeJson = json_decode($tradeJson, true);

        $trade = new Trade();
        $trade->setState(Trade::STATE_OPEN);
        $trade->getLimit()->setProfit((float)$tradeJson['limit']['profit']);
        $trade->getLimit()->setDump((float)$tradeJson['limit']['dump']);
        $trade->getLimit()->setLoss((float)$tradeJson['limit']['loss']);
        $trade->getLimit()->setTime((int)$tradeJson['limit']['time']);

        $trade->setBasicToken($tradeJson['basicToken']);
        $trade->setToken($tradeJson['token']);
        $trade->setSignal($tradeJson['signal']);
        $trade->setExchange($tradeJson['exchange']);

        $trade->setAmount((float)$tradeJson['amount']);
        $trade->getPrice()->setBuy($tradeJson['price']['buy']);
        $trade->setBuyTokenAmount($tradeJson['buyTokenAmount']);
        $trade->setExchangeFilters($tradeJson['exchangeFilters']);

        $i = 0;
        while ($trade->isOpen()) {
            $this->logs = [];
            $this->events = [];
            $now = new \DateTime();
            $interval = $now->diff($trade->getTimestamp());

            $trade->calculateCurrentState($app);
            $trade->checkLimits();

            /** Check for limit sell */

            $handleSell = $trade->sellOnLimits($app->getCertaintyLimit(), $app, $app->isProduction());
            if ($trade->isOpen()) {
                $handleSell = $trade->sellOnTime($app, $app->isProduction());
            }

            /** Check for user forced actions */
            if ($trade->isOpen() && $i%App::EVENT_STATUS_INTERVAL == 0) {
                switch ($app->getJink()->getEventState($trade)) {
                    case Event::STATE_TO_CANCEL:
                        $handleSell = $trade->cancelOnRequest($app->isProduction());
                        $this->logs[] = new Log("Canceling trade " . $trade->getBasicToken() . "/" . $trade->getToken() . " due to user request after ".$interval->format("%h hours %i minutes"), Log::LOG_LEVEL_INFO);
                        break;
                    case Event::STATE_TO_SELL:
                        $handleSell = $trade->sellOnRequest($app, $app->isProduction());
                        $this->logs[] = new Log("Manually closing trade " . $trade->getBasicToken() . "/" . $trade->getToken() . " due to user request after ".$interval->format("%h hours %i minutes"), Log::LOG_LEVEL_INFO);
                        break;
                }
                $i = 0;
            }

            if (is_array($handleSell)) {

                if (isset($handleSell['orderId']) || !$app->isProduction()) {
                    $this->logs[] = new Log("Placed Market Sell for " . $trade->getBasicToken() . "/" . $trade->getToken() . " with ".$trade->getCurrent()->getProfit()."% profit [Dump: ".$trade->getCurrent()->getDump()."%] after ".$interval->format("%h hours %i minutes"), Log::LOG_LEVEL_INFO);
                    $this->events[] = new Event(Event::ACTION_SELL, $trade);
                } else {
                    $trade->setState(Trade::STATE_ERROR);
                    $this->logs[] = new Log("Error while selling pair " . $trade->getBasicToken() . "/" . $trade->getToken().": ".$handleSell['msg'], Log::LOG_LEVEL_ERROR);
                }

            }

            if (!$trade->isOpen()) {
                $this->logs[] = new Log("Closing trade on ".$trade->getBasicToken() . "/" . $trade->getToken(), Log::LOG_LEVEL_INFO);
            }

            /** Handle logs and sync with API */

            if (count($this->logs) > 0) {
                $app->getJink()->postLogs($this->logs);
            }
            if (count($this->events) > 0) {
                $app->getJink()->postEvents($this->events);
            }

            if (!$app->isProduction()) {
                $view->renderTradingView($app, $trade);
            }
            usleep($app->getIntervalTime() * 1000);
            $i++;
        }
    }
}