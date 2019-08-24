<?php

namespace Vendi\InternalTools\DevServerBackup\Service\ApplicationTesters;

use Psr\Log\LoggerInterface;
use Vendi\InternalTools\DevServerBackup\Entity\NginxSite;
use Vendi\InternalTools\DevServerBackup\Entity\WebApplications\WebApplicationInterface;
use Vendi\InternalTools\DevServerBackup\Service\ServiceWithLogger;

abstract class ApplicationTesterBase  extends ServiceWithLogger implements ApplicationTesterInterface
{
    /**
     * @var string
     */
    private $testerName;

    /**
     * @var NginxSite
     */
    private $nginxSite;

    public function getTesterName(): string
    {
        return $this->testerName;
    }

    /**
     * @return NginxSite
     */
    public function getNginxSite(): NginxSite
    {
        return $this->nginxSite;
    }

    protected function logTestStart()
    {
        $this->getLogger()->debug(sprintf('Performing %1$s test', $this->getTesterName()));
    }

    protected function logTestEnd(bool $success)
    {
        if($success){
            $this->getLogger()->debug(sprintf('Site is a %1$s site', $this->getTesterName()), ['nginx-site' => $this->getNginxSite()]);
        }else{
            $this->getLogger()->debug(sprintf('Site is not a %1$s site', $this->getTesterName()));
        }
    }

    public function __construct(string $testerName, LoggerInterface $logger)
    {
        parent::__construct($logger);
        $this->testerName = $testerName;
    }
}