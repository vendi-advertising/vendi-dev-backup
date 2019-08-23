<?php

namespace Vendi\InternalTools\DevServerBackup\Entity\WebApplications;

use Vendi\InternalTools\DevServerBackup\Entity\NginxSite;

abstract class WebApplicationBase implements WebApplicationInterface
{
    private $backups = [];

    /**
     * @return string[]
     */
    public function get_backups(): array
    {
        return $this->backups;
    }

    /**
     * @var NginxSite
     */
    private $nginxSite;

    public function __construct(NginxSite $nginxSite)
    {
        $this->nginxSite = $nginxSite;
    }

    /**
     * @return NginxSite
     */
    public function getNginxSite(): NginxSite
    {
        return $this->nginxSite;
    }

    public function add_backup(string $key, string $path) : array
    {
        $this->backups[$key] = $path;
    }

    public function get_nginx_site() : NginxSite
    {
        return $this->getNginxSite();
    }
}