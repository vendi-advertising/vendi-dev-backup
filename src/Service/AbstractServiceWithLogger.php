<?php

namespace Vendi\InternalTools\DevServerBackup\Service;

use Psr\Log\LoggerInterface;

abstract class AbstractServiceWithLogger
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @return LoggerInterface
     */
    final public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }
}