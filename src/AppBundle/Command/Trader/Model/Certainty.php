<?php
declare(strict_types=1);

namespace AppBundle\Command\Trader\Model;

/**
 * Class Certainty
 * @package AppBundle\Command\Trader\Model
 */
class Certainty {

    /** @var int */
    private $profit;

    /** @var int */
    private $loss;

    /** @var int */
    private $dump;

    /**
     * @return int
     */
    public function getProfit(): int
    {
        return $this->profit;
    }

    /**
     * @param int $profit
     */
    public function setProfit(int $profit): void
    {
        $this->profit = $profit;
    }

    /**
     * @return int
     */
    public function getLoss(): int
    {
        return $this->loss;
    }

    /**
     * @param int $loss
     */
    public function setLoss(int $loss): void
    {
        $this->loss = $loss;
    }

    /**
     * @return int
     */
    public function getDump(): int
    {
        return $this->dump;
    }

    /**
     * @param int $dump
     */
    public function setDump(int $dump): void
    {
        $this->dump = $dump;
    }



    /**
     * Certainty constructor.
     */
    public function __construct()
    {
        $this->profit = 0;
        $this->loss = 0;
        $this->dump = 0;
    }

}