<?php

namespace Vendi\InternalTools\DevServerBackup\Service;

use Vendi\InternalTools\DevServerBackup\Entity\NginxSite;

class PhpApplicationFigureOuter
{
    /**
     * @var NginxSite
     */
    private $nginxSite;

    public function __construct(NginxSite $nginxSite)
    {
        $this->nginxSite = $nginxSite;
    }

    public function get_application()
    {

    }
}