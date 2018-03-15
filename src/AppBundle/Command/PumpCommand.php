<?php
declare(strict_types=1);

namespace AppBundle\Command;

use AppBundle\Command\Trader\App;
use AppBundle\Command\Trader\Trade\Trade;
use AppBundle\Command\Trader\View\View;
use AppBundle\Entity\Log;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Class PumpCommand
 * @package AppBundle\Command
 */
class PumpCommand extends ContainerAwareCommand
{
    /** @var SymfonyStyle */
    private $io;

    /** @var OutputInterface */
    private $output;

    /**
     * Configure console command
     */
    protected function configure()
    {
        $this
            ->setName('jink:client')
            ->addArgument("binance_api_key", InputArgument::REQUIRED, "Binance API KEY")
            ->addArgument("binance_api_secret", InputArgument::REQUIRED, "Binance API Secret")
            ->addArgument("jink_api_url", InputArgument::REQUIRED, "JiNK API URL")
            ->addArgument("jink_api_key", InputArgument::REQUIRED, "JiNK API KEY")
            ->addOption("dev", "d", InputOption::VALUE_NONE, 'Run in dev mode?')
            ->setDescription('Flip token to desired level');
    }


    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $app = new App(
            $input->getArgument('jink_api_key'),
            $input->getArgument('jink_api_url'),
            $input->getArgument('binance_api_key'),
            $input->getArgument('binance_api_secret'),
            $input->getOption('dev')
        );

        $view = new View($input, $output);
        $view->getOutput()->write(sprintf("\033\143"));

        /** If problem with Binance connection -- EXIT */
        if (empty($app->getBinanceBalances())) {
            return;
        }

        $i = 0;
        while (true) {
            $logs = [];

            if ($i%Log::LOG_HEARTBEAT_INTERVAL == 0) {
                $logs[] = new Log("Heartbeat", Log::LOG_LEVEL_SYSTEM); $i = 0;
            }
            $i++;

            if ($app->isTrading()) {

                $app->calculateCurrentState();
                $app->checkLimits();

                /** @var Trade $trade */
                foreach ($app->getTrades() as $trade) {
                    if ($trade->isOpen()) {
                        $result = $trade->sellOnLimits($app->getCertaintyLimit(), $app->getBinance(), $app->isProduction());
                        if (is_array($result)) {
                            if ($app->isProduction() && !isset($result['orderId'])) {
                                $trade->setState(Trade::STATE_ERROR);
                                $logs[] = new Log("Error while selling pair " . $trade->getBasicToken() . "/" . $trade->getToken(), Log::LOG_LEVEL_ERROR);
                            } else {
                                $logs[] = new Log("Placed Market Sell for " . $trade->getBasicToken() . "/" . $trade->getToken() . " with ".$trade->getCurrent()->getProfit()."% profit [Dump: ".$trade->getCurrent()->getDump()."%]", Log::LOG_LEVEL_INFO);
                            }
                        }
                    }
                }
                if (!$app->isTrading()) {
                    // close trades with LOGs
                    $logs[] = new Log("Closing trades on ".$trade->getToken(), Log::LOG_LEVEL_INFO);

                    // re-set app
                    $app->resetApp();
                }

            } else {
                $signal = $app->getJink()->getSignal();

                /** try activate trading mode */
                if (isset($signal['settings'])) {
                    $token = $signal['token'];
                    $logs[] = new Log("New ".$signal['strength']." signal for ".$token, Log::LOG_LEVEL_INFO);
                    $app->getLimit()->setProfit((float)$signal['settings']['limit']['profit']);
                    $app->getLimit()->setDump((float)$signal['settings']['limit']['dump']);
                    $app->getLimit()->setLoss((float)$signal['settings']['limit']['loss']);
                    foreach ($signal['settings']['token'] as $basicToken => $tokenAmount) {
                        $trade = new Trade();
                        $trade->setBasicToken($basicToken);

                        $trade->setToken($token);
                        $trade->setAmount((float)$tokenAmount);

                        $buyPrice = $app->getBinancePrice($trade->getTokenPair());
                        if (!$buyPrice) {
                            $logs[] = new Log("No such pair (".$basicToken."/".$token.") on Binance", Log::LOG_LEVEL_ERROR);
                            continue;
                        }

                        if ($tokenAmount <= 0) {
                            $logs[] = new Log("Ignoring ".$basicToken."/".$token." according to settings", Log::LOG_LEVEL_INFO);
                            continue;
                        }
                        if ($tokenAmount > $app->getBinanceBalance($basicToken)) {
                            $logs[] = new Log("Ignoring ".$basicToken."/".$token." due to insufficient balance", Log::LOG_LEVEL_INFO);
                            continue;
                        }

                        $trade->getPrice()->setBuy($buyPrice);
                        $trade->setBuyTokenAmount($trade->getAmount() / $trade->getPrice()->getBuy());
                        $trade->setExchangeFilters($app->getExchangeFiltersTokenPair($trade->getTokenPair()));

                        if ($app->isProduction()) {
                            $result = $trade->buyMarket($app->getBinance());

                            if (!$result) {
                                $logs[] = new Log("Invalid Binance response for setting up order", Log::LOG_LEVEL_ERROR);
                            } elseif (!isset($result['orderId'])) {
                                $logs[] = new Log("Invalid Binance response " . $result['msg'] . " [" . $result['code'] . "]", Log::LOG_LEVEL_ERROR);
                            } else {
                                $logs[] = new Log("Placed Market Buy for " . $trade->getBasicToken() . "/" . $trade->getToken() . " at price " . sprintf("%.8f", $trade->getPrice()->getBuy()), Log::LOG_LEVEL_INFO);
                            }
                        } else {
                            $logs[] = new Log("Placed Market Buy for ".$trade->getBasicToken() ."/". $trade->getToken(), Log::LOG_LEVEL_INFO);
                        }
                        $trade->setState(Trade::STATE_OPEN);
                        $app->addTrade($trade);

                    }
                }
            }

            /** @var Log $l */
            $app->getJink()->postLogs($logs);


            $view->renderView($app);
            usleep($app->getIntervalTime() * 1000);
        }
    }
}