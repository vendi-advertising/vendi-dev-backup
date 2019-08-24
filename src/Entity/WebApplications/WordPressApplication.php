<?php

declare(strict_types=1);

namespace Vendi\InternalTools\DevServerBackup\Entity\WebApplications;

class WordPressApplication extends GeneralWebApplicationWithDatabase
{
    final public function get_application_type(): string
    {
        return WebApplicationInterface::KNOWN_APPLICATION_TYPE_WORDPRESS;
    }
}
