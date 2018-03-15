<?php
declare(strict_types=1);

namespace AppBundle\Entity;

/**
 * Class Log
 * @package AppBundle\Entity
 */
class Log {

    const LOG_LEVEL_SYSTEM = 'system';
    const LOG_LEVEL_INFO = 'info';
    const LOG_LEVEL_ERROR = 'error';

    const LOG_HEARTBEAT_INTERVAL = 120;

    /** @var string */
    private $text;

    /** @var string */
    private $level;

    /** @var \DateTime */
    private $createdAt;

    /**
     * Log constructor.
     * @param $text
     * @param $level
     */
    public function __construct($text, $level)
    {
        $this->setText($text);
        $this->setLevel($level);
        $this->setCreatedAt(new \DateTime());
    }

    /**
     * @return string
     */
    public function getText(): string
    {
        return $this->text;
    }

    /**
     * @param string $text
     */
    public function setText(string $text): void
    {
        $this->text = $text;
    }

    /**
     * @return string
     */
    public function getLevel(): string
    {
        return $this->level;
    }

    /**
     * @param string $level
     */
    public function setLevel(string $level): void
    {
        $this->level = $level;
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