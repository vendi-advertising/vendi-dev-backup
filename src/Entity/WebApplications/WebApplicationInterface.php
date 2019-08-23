<?php

namespace Vendi\InternalTools\DevServerBackup\Entity\WebApplications;

use Vendi\InternalTools\DevServerBackup\Entity\NginxSite;

interface WebApplicationInterface
{
    public const KNOWN_APPLICATION_TYPE_WORDPRESS = 'WordPress';

    public const KNOWN_APPLICATION_TYPE_DRUPAL = 'Drupal';

    public function get_application_type() : string;

    public function has_database() : bool;

    public function exclude_from_backup() : bool;

    public function get_backups() : array;

    public function add_backup(string $key, string $path);

    public function get_nginx_site(): NginxSite;

}