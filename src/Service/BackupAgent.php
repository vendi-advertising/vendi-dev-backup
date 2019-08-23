<?php

namespace Vendi\InternalTools\DevServerBackup\Service;

use Vendi\InternalTools\DevServerBackup\Entity\WebApplications\WebApplicationInterface;

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