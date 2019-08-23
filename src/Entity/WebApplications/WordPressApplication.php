<?php

namespace Vendi\InternalTools\DevServerBackup\Entity\WebApplications;

class WordPressApplication implements WebApplicationInterface
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