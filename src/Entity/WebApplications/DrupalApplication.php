<?php

namespace Vendi\InternalTools\DevServerBackup\Entity\WebApplications;

class DrupalApplication extends GeneralWebApplicationWithDatabase
{
    public function get_application_type(): string
    {
        return 'Drupal';
    }
}