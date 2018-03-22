<?php
declare(strict_types=1);

namespace AppBundle\Entity;
use AppBundle\Command\Trader\Trade\Trade;

/**
 * Class Event
 * @package AppBundle\Entity
 */
class Event {

    const ACTION_BUY = 'buy';
    const ACTION_SELL = 'sell';

    /** @var string */
    private $action;

    /** @var Trade */
    private $trade;

    /** @var \DateTime */
    private $createdAt;

    /**
     * Event constructor.
     * @param $action
     * @param $trade
     */
    public function __construct($action, Trade $trade)
    {
        $this->setAction($action);
        $this->setTrade($trade);
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
     * @return Trade
     */
    public function getTrade(): Trade
    {
        return $this->trade;
    }

    /**
     * @param Trade $trade
     */
    public function setTrade(Trade $trade): void
    {
        $this->trade = $trade;
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