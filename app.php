<?php

define( 'VENDI_DEV_BACKUP_FILE', __FILE__ );
define( 'VENDI_DEV_BACKUP_PATH', __DIR__ );

require VENDI_DEV_BACKUP_PATH . '/includes/autoload.php';

$nginx_command = new Vendi\InternalTools\DevServerBackup\Commands\NginxSitesCommand();

$application = new Symfony\Component\Console\Application( 'Vendi Dev Backup', '0.1' );
$application->add( $nginx_command );
$application->run();
