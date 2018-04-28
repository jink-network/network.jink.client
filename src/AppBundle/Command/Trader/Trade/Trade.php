<?php
declare(strict_types=1);

namespace AppBundle\Command\Trader\Trade;
use AppBundle\Command\Trader\App;
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
    private $exchange;

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
     * @return string
     */
    public function getExchange(): string
    {
        return $this->exchange;
    }

    /**
     * @param string $exchange
     */
    public function setExchange(string $exchange): void
    {
        $this->exchange = $exchange;
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
        if ($this->getExchange() == 'binance') {
            return $this->getToken() . $this->getBasicToken();
        }
        if ($this->getExchange() == 'bittrex') {
            return $this->getBasicToken().'-'.$this->getToken();
        }
        if ($this->getExchange() == 'kucoin') {
            return $this->getToken().'/'.$this->getBasicToken();
        }
        return false;
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

    }

    /**
     * @param App $app
     * @return array
     */
    public function buyMarket(App $app, $deviation = 0.1) {
        $result = false;

        $buyTokenAmount = $this->roundTokenAmount($this->getBuyTokenAmount());

        if (!$buyTokenAmount) {
            return ['msg' => 'Invalid amount to Buy: '.$this->getBuyTokenAmount().', rounded to 0'];
        }

        if ($this->getExchange() == 'binance') {
            $result = $app->getBinance()->marketBuy($this->getTokenPair(), $buyTokenAmount);

            $this->setBuyTokenAmount($buyTokenAmount);
            $sumAmount = 0;
            foreach ($result['fills'] as $fill) {
                $sumAmount += $fill['price'] * $fill['qty'];
            }
            $this->getPrice()->setBuy($sumAmount / $this->getBuyTokenAmount());
            $this->getPrice()->setMax($this->getPrice()->getBuy());
        }

        if ($this->getExchange() == 'bittrex') {

            $orderBook = json_decode($app->getBittrex()->getOrderBook($this->getTokenPair(), 'sell'), true);

            $sumQuantity = 0;
            $sumPrice = 0;
            $maxPrice = 0;
            foreach($orderBook['result'] as $order) {
                $sumQuantity += $order['Quantity'];
                $sumPrice += $order['Rate'] * $order['Quantity'];
                if ($sumQuantity >= ($this->getBuyTokenAmount())) {
                    $maxPrice = $order['Rate']*(1+$deviation);
                    break;
                }
            }

            $result = json_decode($app->getBittrex()->buyLimit($this->getTokenPair(), $buyTokenAmount, $maxPrice), true);
            $orderId = $result['result']['uuid'];
            sleep(2);
            $order = json_decode($app->getBittrex()->getOrder($orderId), true);

            if (isset($order['result']['Quantity']) && isset($order['result']['QuantityRemaining']) && ($order['result']['Quantity'] - $order['result']['QuantityRemaining']) == $buyTokenAmount) {
                $buyPrice = isset($order['result']['PricePerUnit'])?$order['result']['PricePerUnit']:0;
                $this->setBuyTokenAmount($buyTokenAmount);
                $this->getPrice()->setBuy($buyPrice);
                $this->getPrice()->setMax($this->getPrice()->getBuy());
                $result['orderId'] = $orderId;
            } else {
                $result['msg'] = 'Filled less than 100%, please check on Bittrex and proceed manually';
            }
        }

        if ($this->getExchange() == 'kucoin') {

            $orderBook = $app->getKucoin()->fetch_order_book($this->getTokenPair(), null, ['limit' => '50']);

            $sumQuantity = 0;
            $sumPrice = 0;
            $maxPrice = 0;
            foreach($orderBook['bids'] as $order) {
                $sumQuantity += $order['1'];
                $sumPrice += $order['0'] * $order['1'];
                if ($sumQuantity >= ($this->getBuyTokenAmount())) {
                    $maxPrice = $order['0']*(1+$deviation);
                    break;
                }
            }

            try {
                $result = $app->getKucoin()->create_order($this->getTokenPair(), 'limit', 'BUY', $buyTokenAmount, $maxPrice);
                $orderId = $result['info']['data']['orderOid'];
                sleep(2);
                $order = $app->getKucoin()->fetch_order($orderId, $this->getTokenPair(), ['type' => 'BUY']);
            } catch (\Exception $e) {
                $result['msg'] = 'Kucoin setting up BUY order error (check Kucoin for order details): '.$e->getMessage();
            }

            if (isset($order['info']['dealPriceAverage']) && isset($order['info']['pendingAmount']) && ($order['info']['pendingAmount'] == 0)) {
                $buyPrice = isset($order['info']['dealPriceAverage'])?$order['info']['dealPriceAverage']:0;
                $this->setBuyTokenAmount($buyTokenAmount);
                $this->getPrice()->setBuy($buyPrice);
                $this->getPrice()->setMax($this->getPrice()->getBuy());
                $result['orderId'] = $orderId;
            }
        }

        return $result;
    }

    /**
     * @param App $app
     * @param float $fee
     * @return array
     */
    private function sellMarket(App $app, $fee=0.001, $deviation = 0.1) {

        $sellTokenAmount = $this->getBuyTokenAmount()-($this->getBuyTokenAmount() * $fee);
        $sellTokenAmount = $this->roundTokenAmount($sellTokenAmount);
        $sellPrice = 0;

        if (!$sellTokenAmount) {
            return ['msg' => 'Invalid amount to Sell: '.$this->getBuyTokenAmount().', rounded to 0'];
        }

        if ($this->getExchange() == 'binance') {
            $result = $app->getBinance()->marketSell($this->getTokenPair(), $sellTokenAmount);

            $sumAmount = 0;
            foreach ($result['fills'] as $fill) {
                $sumAmount += $fill['price'] * $fill['qty'];
            }
            $sellPrice = $sumAmount / $sellTokenAmount;
        }

        if ($this->getExchange() == 'bittrex') {
            $orderBook = json_decode($app->getBittrex()->getOrderBook($this->getTokenPair(), 'buy'), true);

            $sumQuantity = 0;
            $sumPrice = 0;
            $minPrice = 0;
            foreach($orderBook['result'] as $order) {
                $sumQuantity += $order['Quantity'];
                $sumPrice += $order['Rate'] * $order['Quantity'];
                if ($sumQuantity >= ($this->getBuyTokenAmount())) {
                    $minPrice = $order['Rate']*(1-$deviation);
                    break;
                }
            }

            $result = json_decode($app->getBittrex()->sellLimit($this->getTokenPair(), $sellTokenAmount, $minPrice), true);
            $orderId = $result['result']['uuid'];
            sleep(2);
            $order = json_decode($app->getBittrex()->getOrder($orderId), true);

            if (isset($order['result']['Quantity']) && isset($order['result']['QuantityRemaining']) && ($order['result']['Quantity'] - $order['result']['QuantityRemaining']) == $sellTokenAmount) {
                $sellPrice = isset($order['result']['PricePerUnit'])?$order['result']['PricePerUnit']:0;
                $result['orderId'] = $orderId;
            } else {
                $result['msg'] = 'Filled less than 100%, please check on Bittrex and proceed manually';
            }
        }

        if ($this->getExchange() == 'kucoin') {
            $orderBook = $app->getKucoin()->fetch_order_book($this->getTokenPair(), null, ['limit' => '50']);

            $sumQuantity = 0;
            $sumPrice = 0;
            $minPrice = 0;
            $avgPrice = 0;
            foreach($orderBook['asks'] as $order) {
                $sumQuantity += $order['1'];
                $sumPrice += $order['0'] * $order['1'];
                if ($sumQuantity >= ($this->getBuyTokenAmount())) {
                    $minPrice = $order['0']*(1-$deviation);
                    $sumPrice -= $order['0'] * $order['1'];
                    $sumQuantity -= $order['1'];
                    $sumPrice += $order['0'] * ($this->getBuyTokenAmount() - $sumQuantity);
                    $avgPrice = $sumPrice / $this->getBuyTokenAmount();
                    break;
                }
            }

            try {
                $result = $app->getKucoin()->create_order($this->getTokenPair(), 'limit', 'SELL', $sellTokenAmount, $minPrice);
                $orderId = $result['info']['data']['orderOid'];
            } catch (\Exception $e) {
                $result['msg'] = 'Kucoin setting up SELL order failed (check Kucoin for order details): '.$e->getMessage();
            }

            if (isset($orderId)) {
                $sellPrice = $avgPrice;
                $result['orderId'] = $orderId;
            }
        }

        $this->calculateCurrentState($app, $sellPrice);
        return $result;
    }

    /**
     * @param App $app
     * @param $type ['buy', 'sell']
     * @return bool|float
     */
    public function getCurrentPrice(App $app, $type) {

        if ($this->getExchange() == 'binance') {
            $orderBook = $app->getBinance()->depth($this->getTokenPair());
            if ($type == 'buy') $type = 'asks';
            if ($type == 'sell') $type = 'bids';

            $sumQuantity = 0;
            $sumBasicQuantity = 0;

            foreach($orderBook[$type] as $price => $volume) {
                $sumQuantity += $volume;
                $sumBasicQuantity += $price * $volume;
                if ($sumBasicQuantity >= ($this->getAmount())) {
                    return $sumBasicQuantity / $sumQuantity;
                    break;
                }
            }
        }

        if ($this->getExchange() == 'bittrex') {
            $orderBook = json_decode($app->getBittrex()->getOrderBook($this->getTokenPair(), $type), true);

            $sumQuantity = 0;
            $sumBasicQuantity = 0;
            foreach($orderBook['result'] as $order) {
                $sumQuantity += $order['Quantity'];
                $sumBasicQuantity += $order['Rate'] * $order['Quantity'];
                if ($sumBasicQuantity >= ($this->getAmount())) {
                    return $sumBasicQuantity / $sumQuantity;
                    break;
                }
            }
        }

        if ($this->getExchange() == 'kucoin') {
            $orderBook = $app->getKucoin()->fetch_order_book($this->getTokenPair(), null, ['limit' => '20']);
            if ($type == 'buy') $type = 'asks';
            if ($type == 'sell') $type = 'bids';

            $sumQuantity = 0;
            $sumBasicQuantity = 0;
            foreach($orderBook[$type] as $order) {
                $sumQuantity += $order[1];
                $sumBasicQuantity += $order[0] * $order[1];
                if ($sumBasicQuantity >= ($this->getAmount())) {
                    return $sumBasicQuantity / $sumQuantity;
                    break;
                }
            }
        }

        return false;
    }

    /**
     * @param App $app
     * @param int $currentPrice
     */
    public function calculateCurrentState(App $app, $currentPrice = 0)
    {
        $this->getPrice()->setLast($this->getPrice()->getCurrent());
        if ($currentPrice > 0) {
            $this->getPrice()->setCurrent($currentPrice);
        } else {
            $this->getPrice()->setCurrent($this->getCurrentPrice($app, 'sell'));
        }
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

        if ($limits->getProfit() > 0) {
            if ($this->getCurrent()->getProfit() >= $limits->getProfit()) {
                $this->getCertainty()->setProfit($this->getCertainty()->getProfit() + 1);
            } else {
                $this->getCertainty()->setProfit(0);
            }
        }

        // sell as dump
        if ($limits->getDump() > 0) {
            if ($this->getCurrent()->getDump() < 0 && $this->getCurrent()->getLoss() > 0 && abs($this->getCurrent()->getDump()) >= $limits->getDump()) {
                $this->getCertainty()->setDump($this->getCertainty()->getDump() + 1);
            } else {
                $this->getCertainty()->setDump(0);
            }
        }

        // sell as exit
        if ($limits->getLoss() > 0) {
            if ($this->getCurrent()->getLoss() < 0 && abs($this->getCurrent()->getLoss()) >= $limits->getLoss()) {
                $this->getCertainty()->setLoss($this->getCertainty()->getLoss() + 1);
            } else {
                $this->getCertainty()->setLoss(0);
            }
        }
    }

    /**
     * @param $certaintyLimit
     * @param $app
     * @return array|bool|mixed
     */
    public function sellOnLimits($certaintyLimit, $app, $isProduction) {

        if (($this->getCertainty()->getProfit() >= $certaintyLimit)
            || ($this->getCertainty()->getDump() >= $certaintyLimit)
            || ($this->getCertainty()->getLoss() >= $certaintyLimit)) {

            $this->setState(Trade::STATE_CLOSED);
            if ($isProduction) {
                return $this->sellMarket($app);
            }
            return [];
        }
        return false;
    }

    /**
     * @param $app
     * @param $isProduction
     * @return array|bool
     */
    public function sellOnTime($app, $isProduction) {

        $timeLimit = $this->getLimit()->getTime();

        if ($timeLimit > 0) {
            $now = new \DateTime();
            if ($this->getTimestamp() < $now->modify("-".$timeLimit." minutes")) {
                // trigger sale
                $this->setState(Trade::STATE_CLOSED);
                if ($isProduction) {
                    return $this->sellMarket($app);
                }
                return [];
            }
        }
        return false;
    }

    /**
     * @param $app
     * @param $isProduction
     * @return array|bool|mixed
     */
    public function sellOnRequest($app, $isProduction) {

        $this->setState(Trade::STATE_CLOSED);
        if ($isProduction) {
            return $this->sellMarket($app);
        }
        return [];

    }

    /**
     * @param $isProduction
     * @return bool
     */
    public function cancelOnRequest($isProduction) {
        $this->setState(Trade::STATE_CLOSED);
        return false;
    }
}