<?php

declare(strict_types=1);

namespace Vendi\InternalTools\DevServerBackup\Entity\WebApplications;

class DrupalApplication extends GeneralWebApplicationWithDatabase
{
    public function get_application_type(): string
    {
        return self::KNOWN_APPLICATION_TYPE_DRUPAL;
    }
}
