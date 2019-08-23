<?php

namespace Vendi\InternalTools\DevServerBackup\Service\DatabaseDumpers;

class DrupalDatabaseDumper extends DatabaseDumperBase
{
    public function dump_database()
    {
        $backup_file_name = $this->create_tmp_file() . '.sql';
        $command = sprintf(
            'drush sql:dump --result-file=%2$s --root=%1$s',
            escapeshellarg($this->getApplication()->getNginxSite()->get_folder_abs_path()),
            escapeshellarg($backup_file_name)
        );

        $command_outputs = [];

        $this->run_command($command, $command_outputs);

        return $backup_file_name;
    }
}