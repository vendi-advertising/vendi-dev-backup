<?php

namespace Vendi\InternalTools\DevServerBackup\Service\DatabaseDumpers;

class DrupalDatabaseDumper extends DatabaseDumperBase
{
    public function dump_database()
    {
        $command_outputs = [];
        $this->run_command('drush version --pipe', $command_outputs);
        $version = $command_outputs['stdout'];

        $dump_command = 'sql:dump';
        if(version_compare($version, '9.0.0', '<=')){
            $dump_command = 'sql-dump';
        }

        $backup_file_name = $this->create_tmp_file() . '.sql';
        $command = sprintf(
            'drush %3$s --result-file=%2$s --root=%1$s',
            escapeshellarg($this->getApplication()->getNginxSite()->get_folder_abs_path()),
            escapeshellarg($backup_file_name),
            $dump_command
        );

        $command_outputs = [];
        $this->run_command($command, $command_outputs);

        return $backup_file_name;
    }
}