<?php

define( 'VENDI_DEV_BACKUP_FILE', __FILE__ );
define( 'VENDI_DEV_BACKUP_PATH', __DIR__ );

require VENDI_DEV_BACKUP_PATH . '/includes/autoload.php';

$application = new Symfony\Component\Console\Application( 'Vendi Dev Backup', '0.1' );
$application->add(new Vendi\InternalTools\DevServerBackup\Commands\NginxSitesCommand());
$application->add(new Vendi\InternalTools\DevServerBackup\Commands\BackupAgentCommand());
$application->run();
