<?php

namespace Vendi\InternalTools\DevServerBackup\Service;

use Symfony\Component\Finder\Finder;
use Vendi\InternalTools\DevServerBackup\Entity\NginxSite;
use Vendi\InternalTools\DevServerBackup\Entity\WebApplications\WebApplicationInterface;

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

    public function get_application() : ? WebApplicationInterface
    {
        $finder = new Finder();
        dump($finder->files()->in($this->nginxSite->get_folder_abs_path())->name('wp-config.php')->hasResults());

        return null;
    }

}