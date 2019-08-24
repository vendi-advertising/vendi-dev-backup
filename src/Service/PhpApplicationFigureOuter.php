<?php

declare(strict_types=1);

namespace Vendi\InternalTools\DevServerBackup\Service;

use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\Finder\Finder;
use Vendi\InternalTools\DevServerBackup\Entity\NginxSite;
use Vendi\InternalTools\DevServerBackup\Entity\WebApplications\GeneralWebApplicationWithDatabase;
use Vendi\InternalTools\DevServerBackup\Entity\WebApplications\GeneralWebApplicationWithoutDatabase;
use Vendi\InternalTools\DevServerBackup\Entity\WebApplications\WebApplicationInterface;
use Vendi\InternalTools\DevServerBackup\Service\ApplicationTesters\DrupalApplicationTester;
use Vendi\InternalTools\DevServerBackup\Service\ApplicationTesters\WordPressApplicationTester;
use Webmozart\PathUtil\Path;

class PhpApplicationFigureOuter extends ServiceWithLogger
{
    /**
     * @var NginxSite
     */
    private $nginxSite;

    public function __construct(LoggerInterface $logger, NginxSite $nginxSite)
    {
        parent::__construct($logger);
        $this->nginxSite = $nginxSite;
    }

    public function get_application(): WebApplicationInterface
    {
        $this->getLogger()->debug('Starting application detection', ['nginx-site' => $this->nginxSite]);
        $ret = $this->look_for_wordpress() ??
            $this->look_for_drupal() ??
            $this->look_for_default_site() ??
            $this->look_for_html_only_site() ??
            $this->look_for_generic_env() ??
            $this->look_for_magic_in_files();

        if (!$ret) {
            $ret = new GeneralWebApplicationWithoutDatabase($this->nginxSite);
        }

        return $ret;
    }

    protected function look_for_wordpress(): ?WebApplicationInterface
    {
        return (new WordPressApplicationTester($this->getLogger(), $this->nginxSite))->tryToGetApplication();
    }

    protected function look_for_drupal(): ?WebApplicationInterface
    {
        return (new DrupalApplicationTester($this->getLogger(), $this->nginxSite))->tryToGetApplication();
    }

    protected function look_for_default_site(): ?GeneralWebApplicationWithoutDatabase
    {
        $this->getLogger()->debug('Performing nginx default test');
        if ('html' === $this->nginxSite->get_project_name()) {
            return new GeneralWebApplicationWithoutDatabase($this->nginxSite);
        }

        $this->getLogger()->debug('Site is not a html-only site');
        return null;
    }

    protected function look_for_html_only_site(): ?GeneralWebApplicationWithoutDatabase
    {
        $this->getLogger()->debug('Performing html-only test');
        if (mb_strpos($this->nginxSite->get_folder_abs_path(), 'html') > 0) {
            $this->getLogger()->info('Site is a html-only site', ['nginx-site' => $this->$this->nginxSite]);
            return new GeneralWebApplicationWithoutDatabase($this->nginxSite, true);
        }

        $this->getLogger()->debug('Site is not a html-only site');
        return null;
    }

    protected function look_for_generic_env(): ?GeneralWebApplicationWithDatabase
    {
        /*

         */
        /**
         * This is a collection of known .env format. All five keys are required (although not enforce below)
         * and should be set to null if it isn't supported by that .env file. Generally that should only ever
         * by the port but we'll cross that road later.
         *
         * Below is a sample configuration:
         * DB_HOST=127.0.0.1
         * DB_DATABASE=snipeit
         * DB_USERNAME=snipeit
         * DB_PASSWORD=snipeit
         */
        $known_formats = [
            [
                'host' => 'DB_HOST',
                'name' => 'DB_DATABASE',
                'user' => 'DB_USERNAME',
                'pass' => 'DB_PASSWORD',
                'port' => null,
            ],
            [
                'host' => 'DB_HOST',
                'name' => 'DB_NAME',
                'user' => 'DB_USERNAME',
                'pass' => 'DB_PASSWORD',
                'port' => null,
            ],
        ];

        $finder = new Finder();

        //Don't forget that Finder ignores DotFiles by default!
        //We're limiting to only 3 folders deep for now because there really shouldn't be a configuration
        //that's deeper than that, right?
        $env_files = $finder->ignoreDotFiles(false)->depth('< 3')->files()->in(dirname($this->nginxSite->get_folder_abs_path()))->name('.env');
        if ($env_files->hasResults()) {
            foreach ($env_files as $file) {
                $backup = $_ENV;
                foreach ($_ENV as $key => $value) {
                    unset($_ENV[$key]);
                }

                $dotenv = new Dotenv(false);
                try {
                    $dotenv->loadEnv($file->getPathname());
                } catch (Exception $ex) {
                    $_ENV = $backup;
                    continue;
                }

                foreach ($known_formats as $keys) {
                    $is_valid = true;

                    $potential = new GeneralWebApplicationWithDatabase($this->nginxSite);

                    foreach ($keys as $key => $value) {
                        if (!$value) {
                            continue;
                        }

                        if (!array_key_exists($value, $_ENV)) {
                            $is_valid = false;
                            break;
                        }

                        $this->set_property_by_key($potential, $key, $_ENV[$value]);
                    }

                    if ($is_valid) {
                        $_ENV = $backup;
                        return $potential;
                    }
                }

                $_ENV = $backup;
            }
        }

        return null;
    }

    protected function set_property_by_key(GeneralWebApplicationWithDatabase $obj, $key, $value)
    {
        switch ($key) {
            case 'host':
                $obj->setDbHost($value);
                break;

            case 'name':
                $obj->setDbName($value);
                break;

            case 'user':
                $obj->setDbUser($value);
                break;

            case 'pass':
                $obj->setDbPass($value);
                break;

            case 'port':
                $obj->setDbPort($value);
                break;

            default:
                throw new Exception('The known formats array is not setup correctly.');
        }
    }

    protected function look_for_magic_in_files(): ?GeneralWebApplicationWithDatabase
    {
        $known_files = [
            '../includes/constants.php' => [
                [
                    'host' => null,
                    'name' => "/^\s*define\(\s*'VENDI_DB_NAME',\s*'(?<VALUE>[^']+)'\s*\);/m",
                    'user' => "/^\s*define\(\s*'VENDI_DB_USER',\s*'(?<VALUE>[^']+)'\s*\);/m",
                    'pass' => "/^\s*define\(\s*'VENDI_DB_PASS',\s*'(?<VALUE>[^']+)'\s*\);/m",
                    'port' => null,
                ],
                [
                    'host' => null,
                    'name' => "/^\s*define\(\s*'VENDI_BLORK_DB_NAME',\s*'(?<VALUE>[^']+)'\s*\);/m",
                    'user' => "/^\s*define\(\s*'VENDI_BLORK_DB_USER',\s*'(?<VALUE>[^']+)'\s*\);/m",
                    'pass' => "/^\s*define\(\s*'VENDI_BLORK_DB_PASS',\s*'(?<VALUE>[^']+)'\s*\);/m",
                    'port' => null,
                ],
            ],
        ];

        foreach ($known_files as $rel_path => $collection_of_keys) {
            $abs_path = Path::canonicalize(Path::join($this->nginxSite->get_folder_abs_path(), $rel_path));
            if (!is_file($abs_path)) {
                continue;
            }

            $contents = file_get_contents($abs_path);

            foreach ($collection_of_keys as $keys) {
                $is_valid = true;
                $potential = new GeneralWebApplicationWithDatabase($this->nginxSite);

                foreach ($keys as $key => $value) {
                    if (!$value) {
                        continue;
                    }

                    if (!preg_match($value, $contents, $matches)) {
                        $is_valid = false;
                        continue;
                    }

                    $actual_value = $matches['VALUE'];

                    $this->set_property_by_key($potential, $key, $actual_value);
                }

                if ($is_valid) {
                    return $potential;
                }
            }
        }

        return null;
    }
}
