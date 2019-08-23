<?php

namespace Vendi\InternalTools\DevServerBackup\Service;

use Symfony\Component\Finder\Finder;
use Vendi\InternalTools\DevServerBackup\Entity\NginxSite;
use Vendi\InternalTools\DevServerBackup\Entity\WebApplications\DrupalApplication;
use Vendi\InternalTools\DevServerBackup\Entity\WebApplications\GeneralWebApplicationWithoutDatabase;
use Vendi\InternalTools\DevServerBackup\Entity\WebApplications\WebApplicationInterface;
use Vendi\InternalTools\DevServerBackup\Entity\WebApplications\WordPressApplication;

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

    public function get_application() : WebApplicationInterface
    {
        $finder = new Finder();
        if($finder->depth('== 0')->files()->in($this->nginxSite->get_folder_abs_path())->name('wp-config.php')->hasResults()){
            return new WordPressApplication($this->nginxSite);
        }

        $composer_files = $finder->depth('== 0')->files()->in(dirname($this->nginxSite->get_folder_abs_path()))->name('composer.json');
        if($composer_files->hasResults()) {
            $files = iterator_to_array($composer_files);
            $file = reset($files);

            $json_args = \JSON_OBJECT_AS_ARRAY ;
            if(defined('JSON_THROW_ON_ERROR')){
                $json_args |= \JSON_THROW_ON_ERROR;
            }

            $json = \json_decode($file->getContents(), true, 512, $json_args);
            if(\JSON_ERROR_NONE === \json_last_error() ){
                if(isset($json['require']['drupal/core'])){
                    return new DrupalApplication($this->nginxSite);
                }
            }

        }

        dump($this->nginxSite);

        return new GeneralWebApplicationWithoutDatabase($this->nginxSite);
    }

}