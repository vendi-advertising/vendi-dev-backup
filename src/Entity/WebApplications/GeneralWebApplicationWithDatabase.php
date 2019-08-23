<?php

namespace Vendi\InternalTools\DevServerBackup\Entity\WebApplications;

class GeneralWebApplicationWithDatabase extends WebApplicationBase
{
    public function get_application_type(): string
    {
        return 'General Web Application Without Database';
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