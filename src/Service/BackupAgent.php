<?php

namespace Vendi\InternalTools\DevServerBackup\Service;

use Archive_Tar;
use Vendi\InternalTools\DevServerBackup\Entity\WebApplications\WebApplicationInterface;
use Vendi\InternalTools\DevServerBackup\Service\DatabaseDumpers\DatabaseDumperInterface;
use Vendi\InternalTools\DevServerBackup\Service\DatabaseDumpers\DrupalDatabaseDumper;
use Vendi\InternalTools\DevServerBackup\Service\DatabaseDumpers\WordPressDatabaseDumper;
use Webmozart\PathUtil\Path;

class BackupAgent
{
    private $sites = [];

    private $applications = [];

    /**
     * @return WebApplicationInterface[]
     */
    public function getApplications(): array
    {
        return $this->applications;
    }

    /**
     * @return NginxSite[]
     */
    public function getSites(): array
    {
        return $this->sites;
    }

    protected function load_sites_to_backup()
    {
        $nginx_config_dumper = new NginxConfigDumper();
        $stdout = $nginx_config_dumper->get_nginx_config();

        $parser = new NginxSiteParser();
        $this->sites = $parser->parse_nginx_output($stdout);
    }

    protected function convert_sites_to_applications()
    {
        foreach($this->getSites() as $site){
            $this->applications[] = (new PhpApplicationFigureOuter($site))->get_application();
        }
    }

    protected function create_timestamp_for_file(\DateTime $dateTime = null) : string
    {
        if(!$dateTime){
            $dateTime = new \DateTime();
        }

        return $dateTime->format('Y-m-d-Y-H-i-s');
    }

    protected function dump_databases()
    {
        $timestamp = $this->create_timestamp_for_file();

        foreach($this->getApplications() as $app) {

            if(!$app->has_database()){
                continue;
            }

            /* @var DatabaseDumperInterface $dumper */
            $dumper = null;

            switch($app->get_application_type()) {
                case WebApplicationInterface::KNOWN_APPLICATION_TYPE_WORDPRESS:
                    $dumper = new WordPressDatabaseDumper($app);
                    break;

                case WebApplicationInterface::KNOWN_APPLICATION_TYPE_DRUPAL:
                    $dumper = new DrupalDatabaseDumper($app);
                    break;
            }

            if(!$dumper){
                continue;
            }

            $backup_file_name_original = $dumper->dump_database();

            $backup_file_name = sprintf(
                '%1$s.%2$s.sql.tgz',
                $timestamp,
                $app->get_nginx_site()->get_project_name()
            );

            $backup_file_path_abs = Path::join('/data/backups/mysql/v2/', $backup_file_name);
            if(is_file($backup_file_path_abs)){
                unlink($backup_file_path_abs);
            }

            $tar_object_compressed = new Archive_Tar($backup_file_path_abs, 'gz');
            if(!$tar_object_compressed->create($backup_file_name_original)){
                throw new \Exception('Unable to make archive for some reason');
            }

            $app->add_backup('DB', $backup_file_path_abs);
        }

    }

    public function run()
    {
        $this->load_sites_to_backup();
        $this->convert_sites_to_applications();
        $this->dump_databases();
    }
}