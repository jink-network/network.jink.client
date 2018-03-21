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

    /** @var float */
    private $price;

    /** @var float|null */
    private $profit;

    /** @var \DateTime */
    private $createdAt;

    /**
     * Event constructor.
     * @param $action
     * @param $basicToken
     * @param $token
     * @param $price
     * @param null $profit
     */
    public function __construct($action, $basicToken, $token, $price, $profit = null)
    {
        $this->action = $action;
        $this->basicToken = $basicToken;
        $this->token = $token;
        $this->price = $price;
        $this->profit = $profit;
        $this->setCreatedAt(new \DateTime());
    }


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
     * @return float
     */
    public function getPrice(): float
    {
        return $this->price;
    }

    /**
     * @param float $price
     */
    public function setPrice(float $price): void
    {
        $this->price = $price;
    }

    /**
     * @return float|null
     */
    public function getProfit(): ?float
    {
        return $this->profit;
    }

    /**
     * @param float|null $profit
     */
    public function setProfit(?float $profit): void
    {
        $this->profit = $profit;
    }

    /**
     * @return \DateTime
     */
    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    /**
     * @param \DateTime $createdAt
     */
    public function setCreatedAt(\DateTime $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

}