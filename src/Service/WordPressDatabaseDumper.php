<?php

namespace Vendi\InternalTools\DevServerBackup\Service;

use Vendi\InternalTools\DevServerBackup\Entity\NginxSite;
use Vendi\InternalTools\DevServerBackup\Entity\WebApplications\WordPressApplication;

class WordPressDatabaseDumper extends ServiceWithProcOpen
{
    private $application;

    private $backup_file_name;

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
        $this->application = $application;
    }

    public function get_backup_filename() : string
    {
        return $this->backup_file_name;
    }

    public function dump_database() : bool
    {
        $this->backup_file_name = $this->create_tmp_file() . '.sql';
        $command = sprintf(
            'wp db dump %2$s --path=%1$s --allow-root',
            escapeshellarg($this->getApplication()->getNginxSite()->get_folder_abs_path()),
            escapeshellarg($this->backup_file_name)
        );
//        dump($tmp_file);
//        dump($command);
        $this->run_command($command, $command_outputs);

        return $command_outputs['stdout'];
    }
}