<?php

declare(strict_types=1);

namespace Vendi\InternalTools\DevServerBackup\Entity\WebApplications;

use Vendi\InternalTools\DevServerBackup\Entity\NginxSite;

class GeneralWebApplicationWithoutDatabase extends WebApplicationBase
{
    /**
     * @var bool
     */
    private $exclude_from_backup;

    public function __construct(NginxSite $nginxSite, bool $exclude_from_backup = false)
    {
        parent::__construct($nginxSite);
        $this->exclude_from_backup = $exclude_from_backup;
    }

    public function get_application_type(): string
    {
        return 'General Web Application Without Database';
    }

    final public function has_database(): bool
    {
        return false;
    }

    public function exclude_from_backup(): bool
    {
        return $this->exclude_from_backup;
    }
}
