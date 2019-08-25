<?php

namespace Vendi\InternalTools\DevServerBackup\Service\ApplicationTesters;

use Psr\Log\LoggerInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Vendi\InternalTools\DevServerBackup\Entity\NginxSite;
use Vendi\InternalTools\DevServerBackup\Entity\WebApplications\WebApplicationInterface;
use Vendi\InternalTools\DevServerBackup\Entity\WebApplications\DrupalApplication;

class DrupalApplicationTester extends AbstractApplicationTester
{
    public function __construct(LoggerInterface $logger, NginxSite $nginxSite)
    {
        parent::__construct('Drupal', $nginxSite, $logger);
    }

    public function tryToGetApplication(): ?WebApplicationInterface
    {
        $this->logTestStart();

        $finder = new Finder();
        $composer_files = $finder->depth('== 0')->files()->in(dirname($this->getNginxSite()->get_folder_abs_path()))->name('composer.json');
        if ($composer_files->hasResults()) {
            $files = iterator_to_array($composer_files);

            /* @var SplFileInfo $file */
            $file = reset($files);

            $json_args = \JSON_OBJECT_AS_ARRAY ;
            if (defined('JSON_THROW_ON_ERROR')) {
                $json_args |= \JSON_THROW_ON_ERROR;
            }

            try{
                $jsonString = $file->getContents();
            }catch(\Exception $ex){
                $this->getLogger()->error(
                    'Could not open JSON file',
                    [
                        'file' => $file->getPathname(),
                        'exception' => $ex,
                    ]
                );
                $this->logTestEnd(false);
                return null;
            }

            $json = \json_decode($jsonString, true, 512, $json_args);
            if (\JSON_ERROR_NONE === \json_last_error()) {
                if (isset($json['require']['drupal/core'])) {
                    $this->logTestEnd(true);
                    return new DrupalApplication($this->getNginxSite());
                }
            }else{
                $this->getLogger()->warning(
                    'Site has a composer file but there was an error parsing it as JSON',
                    [
                        'json-error' => \json_last_error(),
                        'json-error-message' => \json_last_error_msg(),
                        'file' => $file->getPathname(),
                        'file-contents' => $jsonString,
                    ]
                );
                $this->logTestEnd(false);
                return null;
            }
        }

        $this->logTestEnd(false);
        return null;
    }
}