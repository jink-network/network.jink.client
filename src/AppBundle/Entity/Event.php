<?php
declare(strict_types=1);

namespace AppBundle\Entity;

/**
 * Class Event
 * @package AppBundle\Entity
 */
class Event {

    const ACTION_BUY = 'buy';
    const ACTION_SELL = 'sell';

    /** @var string */
    private $action;

    /** @var string */
    private $basicToken;

    /** @var string */
    private $token;

    /** @var string */
    private $price;

    /** @var string */
    private $profit;

    /** @var integer */
    private $signalId;

    /**
     * @return string
     */
    public function getAction(): string
    {
        return $this->action;
    }

    /**
     * @param string $action
     */
    public function setAction(string $action): void
    {
        $this->action = $action;
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
    public function getPrice(): string
    {
        return $this->price;
    }

    /**
     * @param string $price
     */
    public function setPrice(string $price): void
    {
        $this->price = $price;
    }

    /**
     * @return string
     */
    public function getProfit(): string
    {
        return $this->profit;
    }

    /**
     * @param string $profit
     */
    public function setProfit(string $profit): void
    {
        $this->profit = $profit;
    }

    /**
     * @return int
     */
    public function getSignalId(): int
    {
        return $this->signalId;
    }

    /**
     * @param int $signalId
     */
    public function setSignalId(int $signalId): void
    {
        $this->signalId = $signalId;
    }

}