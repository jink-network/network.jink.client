<?php
declare(strict_types=1);

namespace AppBundle\Command\Trader;
use AppBundle\Command\Trader\Trade\Trade;
use AppBundle\Entity\Log;
use AppBundle\Service\JinkService;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Process\Process;

/**
 * Class App
 * @package AppBundle\Command\Trader
 */
class App
{
    /**
     *
     */
    const BASIC_TOKENS = ['ETH', 'BTC'];
    const INTERVAL_TIME = 1000;
    const PROCESS_LIMIT = 10;
    const CERTAINTY_LIMIT = 3;

    /** @var string */
    private $binaneApiKey;

    /** @var string */
    private $binaneApiSecret;

    /** @var \Binance\API */
    private $binance;

    /** @var JinkService */
    private $jink;

    /** @var string */
    private $jinkApiKey;

    /** @var string */
    private $jinkApiUrl;

    /** @var string */
    private $clientId;

    /** @var array */
    private $exchangeFilters;

    /** @var bool */
    private $productionMode;

    /** @var integer */
    private $certaintyLimit;

    /** @var integer */
    private $intervalTime;

    /** @var array */
    private $binanceBalances;

    /** @var array */
    private $processes;

    /**
     * App constructor.
     * @param $jinkApiKey
     * @param $jinkApiUrl
     * @param $apiKey
     * @param $apiSecret
     * @param $dev
     * @param string|null $jinkClientId
     */
    public function __construct($jinkApiKey, $jinkApiUrl, $apiKey, $apiSecret, $dev, string $jinkClientId = null)
    {
        $this->setProcesses([]);
        $this->setCertaintyLimit($this::CERTAINTY_LIMIT);
        $this->setIntervalTime($this::INTERVAL_TIME);
        $this->setProductionMode(!(bool)$dev);

        if (!$jinkClientId) {
            $this->setClientId(Uuid::uuid4()->toString());
        } else {
            $this->setClientId($jinkClientId);
        }

        /* Set up JiNK */
        $this->setJinkApiKey($jinkApiKey);
        $this->setJinkApiUrl($jinkApiUrl);
        $this->setJink(new JinkService($jinkApiKey, $jinkApiUrl, $this->getClientId(), $this->isProduction(), $jinkClientId));

        /* Set up Binance */
        $this->setBinaneApiKey($apiKey);
        $this->setBinaneApiSecret($apiSecret);
        $this->setBinance(new \Binance\API($apiKey, $apiSecret, ['useServerTime'=>true]));

        $this->resetApp();
    }

    /**
     * Reset App
     */
    public function resetApp() {

        $balances = $this->getBinance()->balances();
        if (!$balances) {
            $log = new Log("Invalid Binance credentials, please set up your JiNK bot with correct API Key and API secret",Log::LOG_LEVEL_ERROR);
            $this->getJink()->postLog($log);
            $this->setBinanceBalances([]);
        } else {
            $this->setBinanceBalances($balances);
            $this->setExchangeFilters($this->prepareExchangeInfo());
        }
        $this->getJink()->updateLastSignal();
    }

    /**
     * @return JinkService
     */
    public function getJink(): JinkService
    {
        return $this->jink;
    }

    /**
     * @param JinkService $jink
     */
    public function setJink(JinkService $jink): void
    {
        $this->jink = $jink;
    }

    /**
     * @return bool
     */
    public function isProduction()
    {
        return $this->productionMode;
    }

    /**
     * @return string
     */
    public function getBinaneApiKey(): string
    {
        return $this->binaneApiKey;
    }

    /**
     * @param string $binaneApiKey
     */
    public function setBinaneApiKey(string $binaneApiKey): void
    {
        $this->binaneApiKey = $binaneApiKey;
    }

    /**
     * @return string
     */
    public function getBinaneApiSecret(): string
    {
        return $this->binaneApiSecret;
    }

    /**
     * @param string $binaneApiSecret
     */
    public function setBinaneApiSecret(string $binaneApiSecret): void
    {
        $this->binaneApiSecret = $binaneApiSecret;
    }

    /**
     * @return string
     */
    public function getJinkApiKey(): string
    {
        return $this->jinkApiKey;
    }

    /**
     * @param string $jinkApiKey
     */
    public function setJinkApiKey(string $jinkApiKey): void
    {
        $this->jinkApiKey = $jinkApiKey;
    }

    /**
     * @return string
     */
    public function getJinkApiUrl(): string
    {
        return $this->jinkApiUrl;
    }

    /**
     * @param string $jinkApiUrl
     */
    public function setJinkApiUrl(string $jinkApiUrl): void
    {
        $this->jinkApiUrl = $jinkApiUrl;
    }


    /**
     * @return \Binance\API
     */
    public function getBinance(): \Binance\API
    {
        return $this->binance;
    }

    /**
     * @param \Binance\API $binance
     */
    public function setBinance(\Binance\API $binance): void
    {
        $this->binance = $binance;
    }

    /**
     * @return string
     */
    public function getClientId(): string
    {
        return $this->clientId;
    }

    /**
     * @param string $clientId
     */
    public function setClientId(string $clientId): void
    {
        $this->clientId = $clientId;
    }

    /**
     * @return array
     */
    public function getExchangeFilters(): array
    {
        return $this->exchangeFilters;
    }

    /**
     * @param $tokenPair
     * @return mixed
     */
    public function getExchangeFiltersTokenPair($tokenPair)
    {
        $exchangeFilters = $this->getExchangeFilters();
        return $exchangeFilters[$tokenPair];
    }

    /**
     * @param array $exchangeFilters
     */
    public function setExchangeFilters(array $exchangeFilters): void
    {
        $this->exchangeFilters = $exchangeFilters;
    }

    /**
     * @return bool
     */
    public function isProductionMode(): bool
    {
        return $this->productionMode;
    }

    /**
     * @param bool $productionMode
     */
    public function setProductionMode(bool $productionMode): void
    {
        $this->productionMode = $productionMode;
    }

    /**
     * @return int
     */
    public function getCertaintyLimit(): int
    {
        return $this->certaintyLimit;
    }

    /**
     * @param int $certaintyLimit
     */
    public function setCertaintyLimit(int $certaintyLimit): void
    {
        $this->certaintyLimit = $certaintyLimit;
    }

    /**
     * @return int
     */
    public function getIntervalTime(): int
    {
        return $this->intervalTime;
    }

    /**
     * @param int $intervalTime
     */
    public function setIntervalTime(int $intervalTime): void
    {
        $this->intervalTime = $intervalTime;
    }

    /**
     * @return array
     */
    public function getBinanceBalances(): array
    {
        return $this->binanceBalances;
    }

    /**
     * @param array $binanceBalances
     */
    public function setBinanceBalances(array $binanceBalances): void
    {
        $this->binanceBalances = $binanceBalances;
    }

    /**
     * @return array|null
     */
    public function getProcesses()
    {
        return $this->processes;
    }

    /**
     * @param array $processes
     */
    public function setProcesses(array $processes): void
    {
        $this->processes = $processes;
    }

    /**
     * @param Process $process
     */
    public function addProcess(Process $process) : void
    {
        $this->processes[] = $process;
    }

    /**
     * @param Process $process
     */
    public function removeProcess(Process $process) : void
    {
        /**
         * @var int $key
         * @var Process $p
         */
        foreach ($this->getProcesses() as $key => $p) {
            if ($p->getPid() == $process->getPid()) {
                unset($this->processes[$key]);
            }
        }
    }

    /**
     * @param Trade $t
     * @return string
     */
    public function prepareTradeProcess($t) {
        $trade['basicToken'] = $t->getBasicToken();
        $trade['token'] = $t->getToken();
        $trade['limit']['profit'] = $t->getLimit()->getProfit();
        $trade['limit']['dump'] = $t->getLimit()->getDump();
        $trade['limit']['loss'] = $t->getLimit()->getLoss();
        $trade['limit']['time'] = $t->getLimit()->getTime();
        $trade['price']['buy'] = $t->getPrice()->getBuy();
        $trade['signal'] = $t->getSignal();
        $trade['buyTokenAmount'] = $t->getBuyTokenAmount();
        $trade['amount'] = $t->getAmount();
        $trade['exchangeFilters'] = $t->getExchangeFilters();

        $command = 'bin/console jink:trade '.$this->getBinaneApiKey().' '.$this->getBinaneApiSecret().' '.$this->getJinkApiUrl().' '.$this->getJinkApiKey().' '.$this->getClientId();
        $command .= ' \''.json_encode($trade).'\'';

        if (!$this->isProduction()) {
            $command .= ' --dev';
        }
        return $command;
    }

    /**
     * @param Trade $trade
     */
    public function setTradeProcess(Trade $trade) {
        $process = new Process($this->prepareTradeProcess($trade));
        $process->start();

        $this->addProcess($process);
    }

    /**
     * @param $token
     * @return mixed
     */
    public function getBinanceBalance($token)
    {
        $balances = $this->getBinanceBalances();
        return $balances[$token]['available'];
    }

    /**
     * @param string $tokenPair
     * @return mixed
     */
    public function getBinancePrice(string $tokenPair)
    {
        return $this->getBinance()->price($tokenPair);
    }

    /**
     * @return array
     */
    private function prepareExchangeInfo()
    {
        $exchangeInfo = $this->binance->exchangeInfo();
        $filters = [];
        foreach ($exchangeInfo['symbols'] as $key => $tokenPair) {
            foreach ($tokenPair['filters'] as $filter) {
                if ($filter['filterType'] == 'LOT_SIZE') {
                    $filters[$tokenPair['symbol']] = $filter;
                }
            }
        }
        return $filters;
    }

    /**
     * check if trading is done
     */
    public function isTrading() {
        if (count($this->getProcesses()) > 0) {
            return true;
        }
        return false;
    }

}