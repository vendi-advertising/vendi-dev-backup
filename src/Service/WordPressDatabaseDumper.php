<?php

namespace Vendi\InternalTools\DevServerBackup\Service;

use Vendi\InternalTools\DevServerBackup\Entity\NginxSite;
use Vendi\InternalTools\DevServerBackup\Entity\WebApplications\WordPressApplication;

class WordPressDatabaseDumper extends ServiceWithProcOpen
{
    private $application;

    /**
     * @return mixed
     */
    public function getApplication()
    {
        return $this->application;
    }

    private $database;

    /**
     * @return mixed
     */
    public function getDatabase() : string
    {
        return $this->database;
    }

    /**
     * @param mixed $database
     */
    public function setDatabase(string $database): void
    {
        $this->database = $database;
    }

    public function hasDatabase() : bool
    {
        return $this->database ? true : false;
    }

    public function __construct(WordPressApplication $application)
    {
        $this->$application = $application;
    }

    public function dump_database() : bool
    {
        $command = sprintf('wp db dump - --path=%1$s', escapeshellarg($this->getApplication()->getNginxSite()->get_folder_abs_path()));
        $this->run_command($command, $command_outputs);

        return $command_outputs['stdout'];
    }
}