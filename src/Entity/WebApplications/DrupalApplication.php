<?php

namespace Vendi\InternalTools\DevServerBackup\Entity\WebApplications;

class DrupalApplication implements WebApplicationInterface
{

    public function get_application_type(): string
    {
        return 'Drupal';
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