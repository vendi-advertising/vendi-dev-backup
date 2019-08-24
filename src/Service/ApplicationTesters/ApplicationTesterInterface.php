<?php

declare(strict_types=1);

namespace Vendi\InternalTools\DevServerBackup\Service\ApplicationTesters;

use Vendi\InternalTools\DevServerBackup\Entity\NginxSite;
use Vendi\InternalTools\DevServerBackup\Entity\WebApplications\WebApplicationInterface;

interface ApplicationTesterInterface
{
    public function getTesterName(): string;

    public function getNginxSite(): NginxSite;

    public function tryToGetApplication(): ?WebApplicationInterface;
}