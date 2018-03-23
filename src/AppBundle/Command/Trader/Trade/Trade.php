<?php
declare(strict_types=1);

namespace AppBundle\Command\Trader\Trade;
use AppBundle\Command\Trader\Model\Certainty;
use AppBundle\Command\Trader\Model\Current;
use AppBundle\Command\Trader\Model\Limit;
use AppBundle\Command\Trader\Model\Price;

/**
 * Class Trade
 * @package AppBundle\Command\Trader\Trade
 */
class Trade {

    const STATE_PENDING = 0;
    const STATE_OPEN = 1;
    const STATE_CLOSED = 2;
    const STATE_ERROR = 4;

    /** @var \DateTime */
    private $timestamp;

    /** @var string */
    private $token;

    /** @var string */
    private $basicToken;

    /** @var float */
    private $amount;

    /** @var float */
    private $buyTokenAmount;

    /** @var Certainty */
    private $certainty;

    /** @var Current */
    private $current;

    /** @var Limit */
    private $limit;

    /** @var Price */
    private $price;

    /** @var float */
    private $binanceBalance;

    /** @var array */
    private $exchangeFilters;

    /** @var int */
    private $state;

    /** @var array */
    private $signal;

    /**
     * @return \DateTime
     */
    public function getTimestamp(): \DateTime
    {
        return $this->timestamp;
    }

    /**
     * @param \DateTime $timestamp
     */
    public function setTimestamp(\DateTime $timestamp): void
    {
        $this->timestamp = $timestamp;
    }

    /**
     * @return string
     */
    public function getToken(): string
    {
        return $this->token;
    }

    /**
     * @param string $token
     */
    public function setToken(string $token): void
    {
        $this->token = $token;
    }

    /**
     * @return string
     */
    public function getBasicToken(): string
    {
        return $this->basicToken;
    }

    /**
     * @param string $basicToken
     */
    public function setBasicToken(string $basicToken): void
    {
        $this->basicToken = $basicToken;
    }

    /**
     * @return float
     */
    public function getAmount(): float
    {
        return $this->amount;
    }

    /**
     * @param float $amount
     */
    public function setAmount(float $amount): void
    {
        $this->amount = $amount;
    }

    /**
     * @return float
     */
    public function getBuyTokenAmount(): float
    {
        return $this->buyTokenAmount;
    }

    /**
     * @param float $buyTokenAmount
     */
    public function setBuyTokenAmount(float $buyTokenAmount): void
    {
        $this->buyTokenAmount = $buyTokenAmount;
    }

    /**
     * @return Certainty
     */
    public function getCertainty(): Certainty
    {
        return $this->certainty;
    }

    /**
     * @param Certainty $certainty
     */
    public function setCertainty(Certainty $certainty): void
    {
        $this->certainty = $certainty;
    }


    /**
     * @return Current
     */
    public function getCurrent(): Current
    {
        return $this->current;
    }

    /**
     * @param Current $current
     */
    public function setCurrent(Current $current): void
    {
        $this->current = $current;
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
     * @return Price
     */
    public function getPrice(): Price
    {
        return $this->price;
    }

    /**
     * @param Price $price
     */
    public function setPrice(Price $price): void
    {
        $this->price = $price;
    }


    /**
     * @return float
     */
    public function getBinanceBalance(): float
    {
        return $this->binanceBalance;
    }

    /**
     * @param float $binanceBalance
     */
    public function setBinanceBalance(float $binanceBalance): void
    {
        $this->binanceBalance = $binanceBalance;
    }

    /**
     * @return array
     */
    public function getExchangeFilters(): array
    {
        return $this->exchangeFilters;
    }

    /**
     * @param array $exchangeFilters
     */
    public function setExchangeFilters(array $exchangeFilters): void
    {
        $this->exchangeFilters = $exchangeFilters;
    }

    /**
     * @return string
     */
    public function getTokenPair()
    {
        return $this->getToken().$this->getBasicToken();
    }

    /**
     * @return int
     */
    public function getState(): int
    {
        return $this->state;
    }

    /**
     * @param int $state
     */
    public function setState(int $state): void
    {
        $this->state = $state;
    }

    /**
     * @return array
     */
    public function getSignal(): array
    {
        return $this->signal;
    }

    /**
     * @param array $signal
     */
    public function setSignal(array $signal): void
    {
        $this->signal = $signal;
    }


    public function __construct()
    {
        $this->setTimestamp(new \DateTime());
        $this->setCurrent(new Current());
        $this->setCertainty(new Certainty());
        $this->setPrice(new Price());
        $this->setLimit(new Limit());
        $this->setState($this::STATE_PENDING);
    }

    /**
     * @param $number
     * @param int $precision
     * @return float|int
     */
    function roundUp($number, $precision = 2)
    {
        $fig = (int) str_pad('1', $precision, '0');
        return (ceil($number * $fig) / $fig);
    }

    /**
     * @param $number
     * @param int $precision
     * @return float|int
     */
    function roundDown($number, $precision = 2)
    {
        $fig = (int) str_pad('1', $precision, '0');
        return (floor($number * $fig) / $fig);
    }

    /**
     * @param $x
     * @param $y
     * @return float|int
     */
    function fmodRound($x, $y) {
        $i = round($x / $y);
        return $x - $i * $y;
    }

    /**
     * @return bool
     */
    public function isOpen()
    {
        return $this->getState() == $this::STATE_OPEN;
    }

    /**
     * @return bool
     */
    public function isClosed()
    {
        return ($this->getState() == $this::STATE_CLOSED || $this->getState() == $this::STATE_ERROR);
    }

    /**
     * @param $tokenAmount
     * @return bool|float|int
     */
    public function roundTokenAmount($tokenAmount) {
        $exchangeFilters = $this->getExchangeFilters();
        $i = $exchangeFilters['stepSize'];
        $j=1;
        while ($i!=1) {
            $i*=10; $j++;
        }
        $tokenAmount = $this->roundDown($tokenAmount, $j);
        if ($tokenAmount >= $exchangeFilters['minQty']
            && $tokenAmount <= $exchangeFilters['maxQty']) {
            return $tokenAmount;
        }
        return false;
    }

    /**
     * @param $orderData
     */
    public function recalculateBuyPrice($orderData) {
        $sumAmount = 0;
        foreach ($orderData['fills'] as $fill) {
            $sumAmount += $fill['price'] * $fill['qty'];
        }

        // round ?? @TODO
        $this->getPrice()->setBuy($sumAmount / $this->getBuyTokenAmount());
    }

    /**
     * @param \Binance\API $binance
     * @return array|bool|mixed
     */
    public function buyMarket(\Binance\API $binance) {

        $buyTokenAmount = $this->roundTokenAmount($this->getBuyTokenAmount());
        if (!$buyTokenAmount) {
            return 'Invalid amount to Buy: '.$this->getBuyTokenAmount().', rounded to 0';
        }
        $result = $binance->marketBuy($this->getTokenPair(), $buyTokenAmount);

        $this->setBuyTokenAmount($buyTokenAmount);
        $this->recalculateBuyPrice($result);
        $this->getPrice()->setMax($this->getPrice()->getBuy());

        return $result;
    }

    /**
     * @param \Binance\API $binance
     * @param float $fee
     * @return array|bool|mixed
     */
    private function sellMarket(\Binance\API $binance, $fee=0.001) {

        $sellTokenAmount = $this->getBuyTokenAmount()-($this->getBuyTokenAmount() * $fee);
        $sellTokenAmount = $this->roundTokenAmount($sellTokenAmount);

        if (!$sellTokenAmount) {
            return 'Invalid amount to Sell: '.$this->getBuyTokenAmount().', rounded to 0';
        }
        $result = $binance->marketSell($this->getTokenPair(), $sellTokenAmount);

        return $result;
    }

    /**
     * @param \Binance\API $binance
     */
    public function calculateCurrentState(\Binance\API $binance)
    {
        $this->getPrice()->setLast($this->getPrice()->getCurrent());
        $this->getPrice()->setCurrent($binance->price($this->getTokenPair()));
        $this->getPrice()->setMax(max($this->getPrice()->getCurrent(), $this->getPrice()->getMax()));

        $this->getCurrent()->setProfit(round(($this->getPrice()->getCurrent() - $this->getPrice()->getBuy())*100 / $this->getPrice()->getBuy(),2));
        $this->getCurrent()->setDump(round(($this->getPrice()->getCurrent() - $this->getPrice()->getMax())*100 / $this->getPrice()->getMax(),2));
        $this->getCurrent()->setLoss(round(($this->getPrice()->getCurrent() - $this->getPrice()->getBuy())*100 / $this->getPrice()->getBuy(),2));
    }

    /**
     * checkLimits
     */
    public function checkLimits() {
        $limits = $this->getLimit();
        // sell as success
        if ($this->getCurrent()->getProfit() >= $limits->getProfit()) {
            $this->getCertainty()->setProfit($this->getCertainty()->getProfit()+1);
        } else {
            $this->getCertainty()->setProfit(0);
        }

        // sell as dump
        if ($this->getCurrent()->getDump() < 0 && $this->getCurrent()->getLoss() > 0 && abs($this->getCurrent()->getDump()) >= $limits->getDump()) {
            $this->getCertainty()->setDump($this->getCertainty()->getDump()+1);
        } else {
            $this->getCertainty()->setDump(0);
        }

        // sell as exit
        if ($this->getCurrent()->getLoss() < 0 && abs($this->getCurrent()->getLoss()) >= $limits->getLoss()) {
            $this->getCertainty()->setLoss($this->getCertainty()->getLoss()+1);
        } else {
            $this->getCertainty()->setLoss(0);
        }
    }

    /**
     * @param $certaintyLimit
     * @param $binance
     * @return array|bool|mixed
     */
    public function sellOnLimits($certaintyLimit, $binance, $isProduction) {

        if (($this->getCertainty()->getProfit() >= $certaintyLimit)
            || ($this->getCertainty()->getDump() >= $certaintyLimit)
            || ($this->getCertainty()->getLoss() >= $certaintyLimit)) {

            $this->setState(Trade::STATE_CLOSED);
            if ($isProduction) {
                return ['msg' => $this->sellMarket($binance)];
            }
            return [];
        }
        return false;
    }

    /**
     * @param $binance
     * @param $isProduction
     * @return array|bool
     */
    public function sellOnTime($binance, $isProduction) {

        $timeLimit = $this->getLimit()->getTime();

        if ($timeLimit > 0) {
            $now = new \DateTime();
            if ($this->getTimestamp() < $now->modify("-".$timeLimit." minutes")) {
                // trigger sale
                $this->setState(Trade::STATE_CLOSED);
                if ($isProduction) {
                    return ['msg' => $this->sellMarket($binance)];
                }
                return [];
            }
        }
        return false;
    }
}