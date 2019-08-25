<?php

declare(strict_types=1);

namespace Vendi\InternalTools\DevServerBackup\Service;

use Archive_Tar;
use DateTime;
use Exception;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\SyslogHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Vendi\InternalTools\DevServerBackup\Entity\NginxSite;
use Vendi\InternalTools\DevServerBackup\Entity\WebApplications\WebApplicationInterface;
use Vendi\InternalTools\DevServerBackup\Service\DatabaseDumpers\DatabaseDumperInterface;
use Vendi\InternalTools\DevServerBackup\Service\DatabaseDumpers\DrupalDatabaseDumper;
use Vendi\InternalTools\DevServerBackup\Service\DatabaseDumpers\WordPressDatabaseDumper;
use Webmozart\PathUtil\Path;
use const LOG_USER;

class BackupAgent
{
    public const BACKUP_MODE_DATABASE = 2;
    public const BACKUP_MODE_FILE_SYSTEM = 4;
    public const BACKUP_MODE_ALL = 6;

    private $sites = [];

    private $applications = [];

    private $timestamp;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var string
     */
    private $storage_location;

    private $backupMode;

    public function __construct(string $storage_location, int $backupMode = self::BACKUP_MODE_ALL)
    {
        $this->storage_location = $storage_location;
        $this->createLogger();
//        $this->addLoggerSource(new StreamHandler('path/to/your.log', Logger::DEBUG));
        $this->addLoggerSource(new SyslogHandler('vendi-dev-backup', LOG_USER, Logger::WARNING));
        $this->timestamp = $this->create_timestamp_for_file();
        $this->backupMode = $backupMode;
    }

    protected function createLogger()
    {
        if (!$this->logger) {
            // create a log channel
            $this->logger = new Logger('vendi-dev-backup');
        }
    }

    public function addLoggerSource(AbstractProcessingHandler $handler)
    {
        $this->getLogger()->pushHandler($handler);
    }

    /**
     * @return Logger
     */
    public function getLogger(): Logger
    {
        return $this->logger;
    }

    protected function create_timestamp_for_file(DateTime $dateTime = null): string
    {
        if (!$dateTime) {
            $dateTime = new DateTime();
        }

        return $dateTime->format('Y-m-d-Y-H-i-s');
    }

    public function run()
    {
        $this->load_sites_to_backup();
        $this->convert_sites_to_applications();

        if(self::BACKUP_MODE_DATABASE === ($this->getBackupMode() & self::BACKUP_MODE_DATABASE)){
            $this->backup_databases();
        }else{
            $this->getLogger()->info('Database backup flagged to not run');
        }

        if(self::BACKUP_MODE_FILE_SYSTEM === ($this->getBackupMode() & self::BACKUP_MODE_FILE_SYSTEM)){
            $this->backup_sites();
        }else{
            $this->getLogger()->info('File system backup flagged to not run');
        }
    }

    protected function load_sites_to_backup()
    {
        $nginx_config_dumper = new NginxConfigDumper($this->getLogger());
        $stdout = $nginx_config_dumper->get_nginx_config();

        $parser = new NginxSiteParser($this->getLogger());
        $this->sites = $parser->parse_nginx_output($stdout);
    }

    protected function convert_sites_to_applications()
    {
        foreach ($this->getSites() as $site) {
            $this->applications[] = (new PhpApplicationFigureOuter($this->getLogger(), $site))->get_application();
        }
    }

    /**
     * @return NginxSite[]
     */
    public function getSites(): array
    {
        return $this->sites;
    }

    protected function backup_databases()
    {
        $this->getLogger()->info('Beginning database backup process');
        foreach ($this->getApplications() as $app) {
            if ($app->exclude_from_backup()) {
                $this->getLogger()->info('Application is flagged as excluded from backup', ['application' => $app]);
                continue;
            }
            if (!$app->has_database()) {
                $this->getLogger()->info('Application does not have a database', ['application' => $app]);
                continue;
            }

            /* @var DatabaseDumperInterface $dumper */
            $dumper = null;

            switch ($app->get_application_type()) {
                case WebApplicationInterface::KNOWN_APPLICATION_TYPE_WORDPRESS:
                    $dumper = new WordPressDatabaseDumper($this->getLogger(), $app);
                    break;

                case WebApplicationInterface::KNOWN_APPLICATION_TYPE_DRUPAL:
                    $dumper = new DrupalDatabaseDumper($this->getLogger(), $app);
                    break;
            }

            if (!$dumper) {
                $this->getLogger()->warn('Application does not have a known database backup format', ['application' => $app]);
                continue;
            }

            $backup_file_name_original = $dumper->dump_database();

            $backup_file_name = sprintf(
                '%1$s.%2$s.sql.tgz',
                $this->getTimestamp(),
                $app->get_nginx_site()->get_project_name()
            );

            $backup_file_path_abs = Path::join($this->getStorageLocation(), $backup_file_name);
            if (is_file($backup_file_path_abs)) {
                unlink($backup_file_path_abs);
            }

            $tar_object_compressed = new Archive_Tar($backup_file_path_abs, 'gz');
            if (!$tar_object_compressed->create([$backup_file_name_original])) {
                throw new Exception('Unable to make archive for some reason');
            }

            $app->add_backup('DB', $backup_file_path_abs);
        }
        $this->getLogger()->info('Database backup process complete');
    }

    /**
     * @return WebApplicationInterface[]
     */
    public function getApplications(): array
    {
        return $this->applications;
    }

    /**
     * @return string
     */
    public function getTimestamp(): string
    {
        return $this->timestamp;
    }

    /**
     * @return string
     */
    public function getStorageLocation(): string
    {
        return $this->storage_location;
    }

    protected function backup_sites()
    {
        $this->getLogger()->info('Beginning folder backup process');
        foreach ($this->getApplications() as $app) {
            if ($app->exclude_from_backup()) {
                $this->getLogger()->info('Application is flagged as excluded from backup', ['application' => $app]);
                continue;
            }
            $backup_file_name = sprintf(
                '%1$s.%2$s.site.tgz',
                $this->getTimestamp(),
                $app->get_nginx_site()->get_project_name()
            );

            $backup_file_path_abs = Path::join($this->getStorageLocation(), $backup_file_name);
            if (is_file($backup_file_path_abs)) {
                unlink($backup_file_path_abs);
            }

            $tar_object_compressed = new Archive_Tar($backup_file_path_abs, 'gz');
            if (!$tar_object_compressed->create([$app->get_nginx_site()->get_folder_abs_path()])) {
                throw new Exception('Unable to make archive for some reason');
            }

            $app->add_backup('FS', $backup_file_path_abs);
        }
        $this->getLogger()->info('Folder backup process complete');
    }

    /**
     * @return mixed
     */
    public function getBackupMode()
    {
        return $this->backupMode;
    }
}
