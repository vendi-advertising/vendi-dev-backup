<?php

declare(strict_types=1);

namespace Vendi\InternalTools\DevServerBackup\Service\DatabaseDumpers;

use Exception;
use Psr\Log\LoggerInterface;
use Vendi\InternalTools\DevServerBackup\Entity\WebApplications\GeneralWebApplicationWithDatabase;
use Vendi\InternalTools\DevServerBackup\Service\AbstractServiceWithProcOpen;
use function fclose;
use function is_resource;
use function proc_close;
use function proc_get_status;
use function proc_open;
use function sleep;
use function stream_get_contents;
use function sys_get_temp_dir;
use function tempnam;

abstract class AbstractDatabaseDumper extends AbstractServiceWithProcOpen implements DatabaseDumperInterface
{
    private $application;

    public function __construct(LoggerInterface $logger, GeneralWebApplicationWithDatabase $application)
    {
        parent::__construct($logger);
        $this->application = $application;
    }

    /**
     * @return mixed
     */
    public function getApplication()
    {
        return $this->application;
    }
}
