<?php
declare(strict_types=1);

namespace AppBundle\Command\Trader\Model;

/**
 * Class Price
 * @package AppBundle\Command\Trader\Model
 */
class Price {

    /** @var float */
    private $current;

    /** @var float */
    private $max;

    /** @var float */
    private $last;

    /** @var float */
    private $buy;

    /** @var float */
    private $sell;

    /**
     * @return float
     */
    public function getCurrent(): float
    {
        return $this->current;
    }

    /**
     * @param float $current
     */
    public function setCurrent(float $current): void
    {
        $this->current = $current;
    }

    /**
     * @return float
     */
    public function getMax(): float
    {
        return $this->max;
    }

    /**
     * @param float $max
     */
    public function setMax(float $max): void
    {
        $this->max = $max;
    }

    /**
     * @return float
     */
    public function getLast(): float
    {
        return $this->last;
    }

    /**
     * @param float $last
     */
    public function setLast(float $last): void
    {
        $this->last = $last;
    }

    /**
     * @return float
     */
    public function getBuy(): float
    {
        return $this->buy;
    }

    /**
     * @param float $buy
     */
    public function setBuy(float $buy): void
    {
        $this->buy = $buy;
    }

    /**
     * @return float
     */
    public function getSell(): float
    {
        return $this->sell;
    }

    /**
     * @param float $sell
     */
    public function setSell(float $sell): void
    {
        $this->sell = $sell;
    }

    /**
     * Price constructor.
     */
    public function __construct()
    {
        $this->current = 0;
        $this->max = 0;
        $this->last = 0;
        $this->buy = 0;
        $this->sell = 0;
    }

}