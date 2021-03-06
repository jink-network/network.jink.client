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
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

/**
 * Class ClientCommand
 * @package AppBundle\Command
 */
class ClientCommand extends ContainerAwareCommand
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
            ->setName('jink:client')
            ->addArgument("binance_api_key", InputArgument::REQUIRED, "Binance API KEY")
            ->addArgument("binance_api_secret", InputArgument::REQUIRED, "Binance API Secret")
            ->addArgument("bittrex_api_key", InputArgument::REQUIRED, "Bittrex API Secret")
            ->addArgument("bittrex_api_secret", InputArgument::REQUIRED, "Bittrex API Secret")
            ->addArgument("kucoin_api_key", InputArgument::REQUIRED, "Kucoin API Secret")
            ->addArgument("kucoin_api_secret", InputArgument::REQUIRED, "Kucoin API Secret")
            ->addArgument("jink_api_url", InputArgument::REQUIRED, "JiNK API URL")
            ->addArgument("jink_api_key", InputArgument::REQUIRED, "JiNK API KEY")
            ->addOption("dev", "d", InputOption::VALUE_NONE, 'Run in dev mode?')
            ->setDescription('JiNK Client App');
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
            $input->getOption('dev')
        );

        if (!$app->isProduction()) {
            $view = new View($input, $output);
            $view->getOutput()->write(sprintf("\033\143"));
        }

        if (!empty($app->getBinanceBalances())) {
            $app->addExchange('binance');
        }

        if (!empty($app->getBittrexBalances())) {
            $app->addExchange('bittrex');
        }

        if (!empty($app->getKucoinBalances())) {
            $app->addExchange('kucoin');
        }

        // no exchanges
        if (count($app->getExchanges()) == 0) {
            return;
        }

        $i = 0;
        while (true) {
            usleep($app->getIntervalTime() * 1000);

            $this->logs = [];
            $this->events = [];

            if ($i%Log::LOG_HEARTBEAT_INTERVAL == 0) {
                $this->logs[] = new Log("Heartbeat", Log::LOG_LEVEL_SYSTEM); $i = 0;
            }
            $i++;

            $signal = $app->getJink()->getSignal();

            if (isset($signal['settings'])) {
                if ($app->getExchange($signal['signal']['exchange'])) {

                    $token = $signal['signal']['token'];
                    $this->logs[] = new Log("New ".$signal['signal']['strength']." signal for ".$token." on ".$signal['signal']['exchange'], Log::LOG_LEVEL_INFO);

                    foreach ($signal['settings']['token'] as $basicToken => $tokenAmount) {
                        if (isset($signal['signal']['basicToken']) && $signal['signal']['basicToken'] != $basicToken) {
                            continue;
                        }
                        if (count($app->getProcesses()) >= App::PROCESS_LIMIT) {
                            $this->logs[] = new Log("Ignoring signal for ".$basicToken."/".$token." - too many running Trades (limit is ".App::PROCESS_LIMIT.")", Log::LOG_LEVEL_ERROR);
                            continue;
                        }

                        $trade = new Trade();
                        $trade->getLimit()->setProfit((float)$signal['settings']['limit']['profit']);
                        $trade->getLimit()->setDump((float)$signal['settings']['limit']['dump']);
                        $trade->getLimit()->setLoss((float)$signal['settings']['limit']['loss']);
                        $trade->getLimit()->setTime((int)$signal['settings']['limit']['time']);

                        $trade->setBasicToken($basicToken);
                        $trade->setSignal($signal);
                        $trade->setExchange($signal['signal']['exchange']);

                        $trade->setToken($token);
                        $trade->setAmount((float)$tokenAmount);

                        if ($tokenAmount <= 0) {
                            $this->logs[] = new Log("Ignoring ".$basicToken."/".$token." according to settings", Log::LOG_LEVEL_INFO);
                            continue;
                        }

                        $filters = $app->getExchangeFiltersTokenPair($trade);
                        if (!is_array($filters)) {
                            $app->resetExchanges();
                            $this->logs[] = new Log("Ignoring, missing exchange info for pair: ".$basicToken."/".$token." on ".$trade->getExchange().". Updating exchange info now", Log::LOG_LEVEL_ERROR);
                            continue;
                        }
                        $trade->setExchangeFilters($filters);

                        $this->logs[] = new Log("Placing Buy Order for " . $trade->getBasicToken() . "/" . $trade->getToken(), Log::LOG_LEVEL_INFO);

                        if ($app->isProduction()) {
                            $result = $trade->buyMarket($app);

                            if (!$result) {
                                $this->logs[] = new Log("Invalid response for setting up order", Log::LOG_LEVEL_ERROR);
                            } elseif (!isset($result['orderId'])) {
                                $this->logs[] = new Log("Error while buying pair " . $trade->getBasicToken() . "/" . $trade->getToken().": ".$result['msg'], Log::LOG_LEVEL_ERROR);
                            } else {
                                // open new process for this trade
                                $app->setTradeProcess($trade);
                                $this->events[] = new Event(Event::ACTION_BUY, $trade);
                            }
                        } else {
                            $trade->getPrice()->setBuy($trade->getCurrentPrice($app, 'buy'));
                            $trade->setBuyTokenAmount($trade->getAmount() / $trade->getPrice()->getBuy());

                            $app->setTradeProcess($trade);
                            $this->events[] = new Event(Event::ACTION_BUY, $trade);
                        }

                        unset($trade);
                    }

                } else {
                    $this->logs[] = new Log("Ignoring signal on ".$signal['signal']['exchange']." - not configured", Log::LOG_LEVEL_INFO);
                }

            }

            /**
             * @var integer $key
             * @var Process $process
             */
            foreach ($app->getProcesses() as $process) {
                if (!$process->isRunning()) {
                    $app->removeProcess($process);
                }
            }

            if (count($this->events) > 0) {
                $app->getJink()->postEvents($this->events);
            }

            if (count($this->logs) > 0) {
                $app->getJink()->postLogs($this->logs);
            }

            if (!$app->isProduction()) {
                $view->renderClientView($app);
            }
        }
    }
}