<?php

declare(strict_types=1);

namespace Vendi\InternalTools\DevServerBackup\Service\DatabaseDumpers;

use Spatie\DbDumper\Databases\MySql;

class GenericDatabaseDumper extends DatabaseDumperBase
{
    public function dump_database()
    {
        $backup_file_name = $this->create_tmp_file() . '.sql';

//        $dumper = MySql::create()
//            ->setDbName($databaseName)
//            ->setUserName($userName)
//            ->setPassword($password)
//            ->dumpToFile('dump.sql');

        $command = sprintf(
            'mysql -u%1$s -p%2$2 sql:dump --result-file=%2$s --root=%1$s',
            escapeshellarg($this->getApplication()->getNginxSite()->get_folder_abs_path()),
            escapeshellarg($backup_file_name)
        );

        $command_outputs = [];

        $this->run_command($command, $command_outputs);

        return $backup_file_name;
    }
}
