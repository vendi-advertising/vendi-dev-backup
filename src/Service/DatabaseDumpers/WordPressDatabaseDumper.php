<?php

namespace Vendi\InternalTools\DevServerBackup\Service;

use Vendi\InternalTools\DevServerBackup\Entity\NginxSite;
use Vendi\InternalTools\DevServerBackup\Entity\WebApplications\WordPressApplication;

class WordPressDatabaseDumper extends DatabaseDumperBase
{
    private $application;

    /**
     * @return mixed
     */
    public function getApplication()
    {
        return $this->application;
    }

    public function __construct(WordPressApplication $application)
    {
        $this->application = $application;
    }

    public function dump_database()
    {
        $backup_file_name = $this->create_tmp_file() . '.sql';
        $command = sprintf(
            'wp db dump %2$s --path=%1$s --allow-root',
            escapeshellarg($this->getApplication()->getNginxSite()->get_folder_abs_path()),
            escapeshellarg($backup_file_name)
        );

        $command_outputs = [];

        $this->run_command($command, $command_outputs);

        return $backup_file_name;
    }
}