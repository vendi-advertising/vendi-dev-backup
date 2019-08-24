<?php

namespace Vendi\InternalTools\DevServerBackup\Service\ApplicationTesters;

use Psr\Log\LoggerInterface;
use Symfony\Component\Finder\Finder;
use Vendi\InternalTools\DevServerBackup\Entity\WebApplications\WebApplicationInterface;
use Vendi\InternalTools\DevServerBackup\Entity\WebApplications\WordPressApplication;

class WordPressApplicationTester extends ApplicationTesterBase
{
    public function __construct(LoggerInterface $logger)
    {
        parent::__construct('WordPress', $logger);
    }

    public function tryToGetApplication(): ?WebApplicationInterface
    {
        $this->logTestStart();
        $finder = new Finder();
        if ($finder->depth('== 0')->files()->in($this->getNginxSite()->get_folder_abs_path())->name('wp-config.php')->hasResults()) {
            $this->logTestEnd(true);
            return new WordPressApplication($this->getNginxSite());
        }

        $this->logTestEnd(false);
        return null;
    }
}