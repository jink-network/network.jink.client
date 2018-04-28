<?php
declare(strict_types=1);

namespace AppBundle\Command\Trader;
use AppBundle\Command\Trader\Trade\Trade;
use AppBundle\Entity\Log;
use AppBundle\Service\JinkService;
use ccxt\kucoin;
use codenixsv\Bittrex\BittrexManager;
use codenixsv\Bittrex\Clients\BittrexClient;
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
    const INFINITE = 1000000000;
    const STEP_8TH = 0.00000001;

    const BASIC_TOKENS = ['ETH', 'BTC'];
    const INTERVAL_TIME = 500; // ms
    const EVENT_STATUS_INTERVAL = 30; //~s
    const PROCESS_LIMIT = 8;
    const CERTAINTY_LIMIT = 1;

    /** @var string */
    private $binaneApiKey;

    /** @var string */
    private $binaneApiSecret;

    /** @var \Binance\API */
    private $binance;

    /** @var array */
    private $binanceBalances;

    /** @var array */
    private $binanceExchangeFilters;

    /** @var string */
    private $bittrexApiKey;

    /** @var string */
    private $bittrexApiSecret;

    /** @var BittrexClient */
    private $bittrex;

    /** @var array */
    private $bittrexBalances;

    /** @var array */
    private $bittrexExchangeFilters;

    /** @var string */
    private $kucoinApiKey;

    /** @var string */
    private $kucoinApiSecret;

    /** @var kucoin */
    private $kucoin;

    /** @var array */
    private $kucoinBalances;

    /** @var array */
    private $kucoinExchangeFilters;

    /** @var JinkService */
    private $jink;

    /** @var string */
    private $jinkApiKey;

    /** @var string */
    private $jinkApiUrl;

    /** @var string */
    private $clientId;

    /** @var bool */
    private $productionMode;

    /** @var integer */
    private $certaintyLimit;

    /** @var integer */
    private $intervalTime;

    /** @var array */
    private $processes;

    /** @var array */
    private $exchanges;

    /**
     * App constructor.
     * @param $binanceApiKey
     * @param $binanceApiSecret
     * @param $bittrexApiKey
     * @param $bittrexApiSecret
     * @param $jinkApiKey
     * @param $jinkApiUrl
     * @param $dev
     * @param string|null $jinkClientId
     */
    public function __construct($binanceApiKey,
                                $binanceApiSecret,
                                $bittrexApiKey,
                                $bittrexApiSecret,
                                $kucoinApiKey,
                                $kucoinApiSecret,
                                $jinkApiKey,
                                $jinkApiUrl,
                                $dev, string
                                $jinkClientId = null)
    {
        $this->setProcesses([]);
        $this->setExchanges([]);
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
        $this->setBinaneApiKey($binanceApiKey);
        $this->setBinaneApiSecret($binanceApiSecret);
        try {
            $this->setBinance(new \Binance\API($binanceApiKey, $binanceApiSecret, ['useServerTime' => true]));
            $balances = $this->getBinance()->balances();
            if (!$balances) {
                throw new \Exception('Wrong API Keys');
            } else {
                $this->setBinanceBalances($balances);
                $this->setBinanceExchangeFilters($this->prepareBinanceExchangeInfo());
                if (!$jinkClientId) {
                    $log = new Log("Binance connected!", Log::LOG_LEVEL_INFO);
                    $this->getJink()->postLog($log);
                }
            }
        } catch (\Exception $e) {
            $log = new Log("Invalid Binance credentials - ignoring: ",Log::LOG_LEVEL_INFO);
            $this->getJink()->postLog($log);
            $this->setBinanceBalances([]);
        }

        /* Set up Bittrex */
        $this->setBittrexApiKey($bittrexApiKey);
        $this->setBittrexApiSecret($bittrexApiSecret);
        try {
            $this->setBittrex(new BittrexManager($bittrexApiKey, $bittrexApiSecret));
            $balances = json_decode($this->getBittrex()->getBalances(), true);
            if (!$balances['success']) {
                throw new \Exception('Wrong API Keys');
            } else {
                $this->setBittrexBalances($balances);
                $this->setBittrexExchangeFilters($this->prepareBittrexExchangeInfo());
                if (!$jinkClientId) {
                    $log = new Log("Bittrex connected!", Log::LOG_LEVEL_INFO);
                    $this->getJink()->postLog($log);
                }
            }
        } catch (\Exception $e) {
            $log = new Log("Invalid Bittrex credentials - ignoring",Log::LOG_LEVEL_INFO);
            $this->getJink()->postLog($log);
            $this->setBittrexBalances([]);
        }

        /* Set up KuCoin */
        $this->setKucoinApiKey($kucoinApiKey);
        $this->setKucoinApiSecret($kucoinApiSecret);
        try {
            $this->setKucoin(new kucoin(['apiKey' => $kucoinApiKey, 'secret' => $kucoinApiSecret]));
            $balances = $this->getKucoin()->fetch_balance();
            if (!$balances['info']) {
                throw new \Exception('Wrong API Keys');
            } else {
                $this->setKucoinBalances($balances);
                $this->setKucoinExchangeFilters($this->prepareKucoinExchangeInfo());
                if (!$jinkClientId) {
                    $log = new Log("KuCoin connected!", Log::LOG_LEVEL_INFO);
                    $this->getJink()->postLog($log);
                }
            }
        } catch (\Exception $e) {
            $log = new Log("Invalid Kucoin credentials - ignoring",Log::LOG_LEVEL_INFO);
            $this->getJink()->postLog($log);
            $this->setKucoinBalances([]);
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
    public function getBinanceExchangeFilters(): array
    {
        return $this->binanceExchangeFilters;
    }

    /**
     * @param array $binanceExchangeFilters
     */
    public function setBinanceExchangeFilters(array $binanceExchangeFilters): void
    {
        $this->binanceExchangeFilters = $binanceExchangeFilters;
    }

    /**
     * @return array
     */
    public function getBittrexExchangeFilters(): array
    {
        return $this->bittrexExchangeFilters;
    }

    /**
     * @param array $bittrexExchangeFilters
     */
    public function setBittrexExchangeFilters(array $bittrexExchangeFilters): void
    {
        $this->bittrexExchangeFilters = $bittrexExchangeFilters;
    }

    /**
     * @param Trade $trade
     * @return mixed
     */
    public function getExchangeFiltersTokenPair(Trade $trade)
    {
        if ($trade->getExchange() == 'binance') {
            $binanceExchangeFilters = $this->getBinanceExchangeFilters();
            return $binanceExchangeFilters[$trade->getTokenPair()];
        }
        if ($trade->getExchange() == 'bittrex') {
            $bittrexExchangeFilters = $this->getBittrexExchangeFilters();
            return $bittrexExchangeFilters[$trade->getTokenPair()];
        }
        if ($trade->getExchange() == 'kucoin') {
            $kucoinExchangeFilters = $this->getKucoinExchangeFilters();
            return $kucoinExchangeFilters[$trade->getTokenPair()];
        }
        return false;
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
     * @return array
     */
    public function getBittrexBalances(): array
    {
        return $this->bittrexBalances;
    }

    /**
     * @param array $bittrexBalances
     */
    public function setBittrexBalances(array $bittrexBalances): void
    {
        $this->bittrexBalances = $bittrexBalances;
    }


    /**
     * @return string
     */
    public function getBittrexApiKey(): string
    {
        return $this->bittrexApiKey;
    }

    /**
     * @param string $bittrexApiKey
     */
    public function setBittrexApiKey(string $bittrexApiKey): void
    {
        $this->bittrexApiKey = $bittrexApiKey;
    }

    /**
     * @return string
     */
    public function getBittrexApiSecret(): string
    {
        return $this->bittrexApiSecret;
    }

    /**
     * @param string $bittrexApiSecret
     */
    public function setBittrexApiSecret(string $bittrexApiSecret): void
    {
        $this->bittrexApiSecret = $bittrexApiSecret;
    }

    /**
     * @return BittrexClient
     */
    public function getBittrex()
    {
        return $this->bittrex;
    }

    /**
     * @param mixed $bittrex
     */
    public function setBittrex($bittrex): void
    {
        $this->bittrex = $bittrex->createClient();
    }

    /**
     * @return string
     */
    public function getKucoinApiKey(): string
    {
        return $this->kucoinApiKey;
    }

    /**
     * @param string $kucoinApiKey
     */
    public function setKucoinApiKey(string $kucoinApiKey): void
    {
        $this->kucoinApiKey = $kucoinApiKey;
    }

    /**
     * @return string
     */
    public function getKucoinApiSecret(): string
    {
        return $this->kucoinApiSecret;
    }

    /**
     * @param string $kucoinApiSecret
     */
    public function setKucoinApiSecret(string $kucoinApiSecret): void
    {
        $this->kucoinApiSecret = $kucoinApiSecret;
    }

    /**
     * @return kucoin
     */
    public function getKucoin(): kucoin
    {
        return $this->kucoin;
    }

    /**
     * @param kucoin $kucoin
     */
    public function setKucoin(kucoin $kucoin): void
    {
        $this->kucoin = $kucoin;
    }

    /**
     * @return array
     */
    public function getKucoinBalances(): array
    {
        return $this->kucoinBalances;
    }

    /**
     * @param array $kucoinBalances
     */
    public function setKucoinBalances(array $kucoinBalances): void
    {
        $this->kucoinBalances = $kucoinBalances;
    }

    /**
     * @return array
     */
    public function getKucoinExchangeFilters(): array
    {
        return $this->kucoinExchangeFilters;
    }

    /**
     * @param array $kucoinExchangeFilters
     */
    public function setKucoinExchangeFilters(array $kucoinExchangeFilters): void
    {
        $this->kucoinExchangeFilters = $kucoinExchangeFilters;
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
     * @return array
     */
    public function getExchanges(): array
    {
        return $this->exchanges;
    }

    /**
     * @param array $exchanges
     */
    public function setExchanges(array $exchanges): void
    {
        $this->exchanges = $exchanges;
    }

    /**
     * @param string $exchange
     */
    public function addExchange(string $exchange) {
        $this->exchanges[$exchange] = true;
    }

    /**
     * @param string $exchange
     * @return bool
     */
    public function getExchange(string $exchange) {
        return isset($this->exchanges[$exchange]);
    }

    /**
     * @param Trade $t
     * @return string
     */
    public function prepareTradeProcess($t) {
        $trade['basicToken'] = $t->getBasicToken();
        $trade['token'] = $t->getToken();
        $trade['exchange'] = $t->getExchange();
        $trade['limit']['profit'] = $t->getLimit()->getProfit();
        $trade['limit']['dump'] = $t->getLimit()->getDump();
        $trade['limit']['loss'] = $t->getLimit()->getLoss();
        $trade['limit']['time'] = $t->getLimit()->getTime();
        $trade['price']['buy'] = $t->getPrice()->getBuy();
        $trade['signal'] = $t->getSignal();
        $trade['buyTokenAmount'] = $t->getBuyTokenAmount();
        $trade['amount'] = $t->getAmount();
        $trade['exchangeFilters'] = $t->getExchangeFilters();

        $command = 'bin/console jink:trade '.
            $this->getBinaneApiKey().' '.
            $this->getBinaneApiSecret().' '.
            $this->getBittrexApiKey().' '.
            $this->getBittrexApiSecret().' '.
            $this->getKucoinApiKey().' '.
            $this->getKucoinApiSecret().' '.
            $this->getJinkApiUrl().' '.
            $this->getJinkApiKey().' '.
            $this->getClientId();
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
     * @return array
     */
    private function prepareBinanceExchangeInfo()
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
     * @return array
     */
    private function prepareBittrexExchangeInfo()
    {
        $exchangeInfo = json_decode($this->bittrex->getMarkets(), true);
        $filters = [];
        foreach ($exchangeInfo['result'] as $tokenPair) {
            $key = $tokenPair['BaseCurrency'].'-'.$tokenPair['MarketCurrency'];
            $filters[$key]['minQty'] = $tokenPair['MinTradeSize'];
            $filters[$key]['maxQty'] = $this::INFINITE;
            $filters[$key]['stepSize'] = $this::STEP_8TH;
        }
        return $filters;
    }

    /**
     * @return array
     */
    private function prepareKucoinExchangeInfo()
    {
        $exchangeInfo = $this->kucoin->fetch_markets();
        $filters = [];
        foreach ($exchangeInfo as $tokenPair) {
            $key = $tokenPair['base'].'/'.$tokenPair['quote'];
            $filters[$key]['minQty'] = $tokenPair['limits']['amount']['min'];
            $filters[$key]['maxQty'] = $this::INFINITE;
            $filters[$key]['stepSize'] = $tokenPair['lot'];
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