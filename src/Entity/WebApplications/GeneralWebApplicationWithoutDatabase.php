<?php

namespace Vendi\InternalTools\DevServerBackup\Entity\WebApplications;

class GeneralWebApplicationWithoutDatabase extends WebApplicationBase
{    public function get_application_type(): string
    {
        return 'General Web Application Without Database';
    }

    public function has_database(): bool
    {
        return false;
    }

    public function dump_database(): string
    {
        throw new \Exception('This type of application does not support database dumping');
    }
}