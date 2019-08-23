<?php

namespace Vendi\InternalTools\DevServerBackup\Service;

use Archive_Tar;
use Vendi\InternalTools\DevServerBackup\Entity\WebApplications\WebApplicationInterface;
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

    protected function dump_databases()
    {
        foreach($this->getApplications() as $app) {

            if(!$app->has_database()){
                continue;
            }

            switch($app->get_application_type()) {
                case WebApplicationInterface::KNOWN_APPLICATION_TYPE_WORDPRESS:
                    $dumper = new WordPressDatabaseDumper($app);
                    $dumper->dump_database();

                    $backup_file_name = Path::join('/data/backups/mysql/v2/', basename($dumper->get_backup_filename()) . '.tgz');
                    if(is_file($backup_file_name)){
                        unlink($backup_file_name);
                    }

                    $tar_object_compressed = new Archive_Tar($backup_file_name, 'gz');
                    if(!$tar_object_compressed->create($dumper->get_backup_filename())){
                        throw new \Exception('Unable to make archive for some reason');
                    }

                    $app->add_backup('DB', $tar_object_compressed);

                    exit;
                    break;
            }
        }

    }

    public function run()
    {
        $this->load_sites_to_backup();
        $this->convert_sites_to_applications();
        $this->dump_databases();
    }
}