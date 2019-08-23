<?php

namespace Vendi\InternalTools\DevServerBackup\Entity\WebApplications;

use Vendi\InternalTools\DevServerBackup\Entity\NginxSite;

abstract class WebApplicationBase implements WebApplicationInterface
{
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
}