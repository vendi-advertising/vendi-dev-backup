<?php

namespace Vendi\InternalTools\DevServerBackup\Entity\WebApplications;

use Vendi\InternalTools\DevServerBackup\Entity\NginxSite;

class WordPressApplication extends WebApplicationBase
{
    final public function get_application_type(): string
    {
        return 'WordPress';
    }

    public function has_database(): bool
    {
        return true;
    }

    public function dump_database(): string
    {
        throw new \Exception('Method not implemented');
    }
}