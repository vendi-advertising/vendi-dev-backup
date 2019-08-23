<?php

namespace Vendi\InternalTools\DevServerBackup\Entity\WebApplications;

use Vendi\InternalTools\DevServerBackup\Entity\NginxSite;

class WordPressApplication extends GeneralWebApplicationWithDatabase
{
    final public function get_application_type(): string
    {
        return WebApplicationInterface::KNOWN_APPLICATION_TYPE_WORDPRESS;
    }
}