<?php
declare(strict_types=1);

namespace AppBundle\Command\Trader;
use AppBundle\Command\Trader\Model\Limit;
use AppBundle\Command\Trader\Trade\EmailSignal;
use AppBundle\Command\Trader\Trade\Trade;
use AppBundle\Entity\Log;
use AppBundle\Service\JinkService;
use PhpImap\Mailbox;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

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
    const INTERVAL_TIME = 500;
    const CERTAINTY_LIMIT = 3;


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

    /** @var Limit */
    private $limit;

    /** @var array */
    private $binanceBalances;

    /** @var array */
    private $trades;

    /**
     * App constructor.
     * @param $jinkApiKey
     * @param $jinkApiUrl
     * @param $apiKey
     * @param $apiSecret
     * @param $dev
     */
    public function __construct($jinkApiKey, $jinkApiUrl, $apiKey, $apiSecret, $dev)
    {
        $this->setCertaintyLimit($this::CERTAINTY_LIMIT);
        $this->setIntervalTime($this::INTERVAL_TIME);
        $this->setProductionMode(!(bool)$dev);

        $this->setClientId(Uuid::uuid4()->toString());
        $this->setLimit(new Limit());

        /* Set up JiNK */
        $this->setJinkApiKey($jinkApiKey);
        $this->setJinkApiUrl($jinkApiUrl);
        $this->setJink(new JinkService($jinkApiKey, $jinkApiUrl, $this->getClientId(), $this->isProduction()));

        /* Set up Binance */
        $this->setBinance(new \Binance\API($apiKey, $apiSecret));

        $this->resetApp();
    }

    /**
     * Reset App
     */
    public function resetApp() {
        $this->setTrades([]);
        $this->setLimit(new Limit());

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
     * @return Limit
     */
    public function getLimit(): Limit
    {
        return $this->limit;
    }

    /**
     * @param Limit $limit
     */
    public function setLimit(Limit $limit): void
    {
        $this->limit = $limit;
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
    public function getTrades()
    {
        return $this->trades;
    }

    /**
     * @param array $trades
     */
    public function setTrades(array $trades): void
    {
        $this->trades = $trades;
    }

    /**
     * @param Trade $trade
     */
    public function addTrade(Trade $trade) : void
    {
        $this->trades[] = $trade;
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
     * calculate state for each trade
     */
    public function calculateCurrentState() {
        /** @var Trade $trade */
        foreach ($this->getTrades() as $trade) {
            if ($trade->isOpen()) {
                $trade->calculateCurrentState($this->getBinance());
            }
        }
    }

    /**
     * check limits for each trade
     */
    public function checkLimits() {
        /** @var Trade $trade */
        foreach ($this->getTrades() as $trade) {
            if ($trade->isOpen()) {
                $trade->checkLimits($this->getLimit());
            }
        }
    }

    /**
     * check if trading is done
     */
    public function isTrading() {
        /** @var Trade $trade */
        foreach ($this->getTrades() as $trade) {
            if ($trade->isOpen()) {
                return true;
            }
        }
        return false;
    }

}