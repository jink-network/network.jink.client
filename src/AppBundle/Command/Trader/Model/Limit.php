<?php
declare(strict_types=1);

namespace AppBundle\Command\Trader\Model;

/**
 * Class Price
 * @package AppBundle\Command\Trader\Model
 */
class Limit {

    /** @var float */
    private $profit;

    /** @var float */
    private $dump;

    /** @var float */
    private $loss;

    /** @var float */
    private $time;

    /**
     * @return float
     */
    public function getProfit(): float
    {
        return $this->profit;
    }

    /**
     * @param float $profit
     */
    public function setProfit(float $profit): void
    {
        $this->profit = $profit;
    }

    /**
     * @return float
     */
    public function getDump(): float
    {
        return $this->dump;
    }

    /**
     * @param float $dump
     */
    public function setDump(float $dump): void
    {
        $this->dump = $dump;
    }

    /**
     * @return float
     */
    public function getLoss(): float
    {
        return $this->loss;
    }

    /**
     * @param float $loss
     */
    public function setLoss(float $loss): void
    {
        $this->loss = $loss;
    }

    /**
     * @return float
     */
    public function getTime(): float
    {
        return $this->time;
    }

    /**
     * @param float $time
     */
    public function setTime(float $time): void
    {
        $this->time = $time;
    }

    /**
     * Limit constructor.
     */
    public function __construct()
    {
        $this->profit = 0;
        $this->dump = 0;
        $this->loss = 0;
        $this->time = 0;
    }

}