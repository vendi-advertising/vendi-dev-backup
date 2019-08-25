<?php

declare(strict_types=1);

namespace Vendi\InternalTools\DevServerBackup\Service\DatabaseDumpers;

class DrupalDatabaseDumper extends AbstractDatabaseDumper
{
    public function dump_database()
    {
        // Depending on the drupal/drush version, the command for dumping might be
        // sql:dump or sql-dump, so we need to get a version number first. This
        // totally might be done in the wrong way, but so far it works.
        $command_outputs = [];
        $version_command = sprintf(
            //Using --pipe gets us only the version number and nothing else
            'drush version --pipe --root=%1$s',
            escapeshellarg($this->getApplication()->getNginxSite()->get_folder_abs_path())
        );
        $this->run_command($version_command, $command_outputs);
        $version = $command_outputs['stdout'];

        $dump_command = 'sql:dump';
        if (version_compare($version, '9.0.0', '<=')) {
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
