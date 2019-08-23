<?php

namespace Vendi\InternalTools\DevServerBackup\Service;

use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\Finder\Finder;
use Vendi\InternalTools\DevServerBackup\Entity\NginxSite;
use Vendi\InternalTools\DevServerBackup\Entity\WebApplications\DrupalApplication;
use Vendi\InternalTools\DevServerBackup\Entity\WebApplications\GeneralWebApplicationWithDatabase;
use Vendi\InternalTools\DevServerBackup\Entity\WebApplications\GeneralWebApplicationWithoutDatabase;
use Vendi\InternalTools\DevServerBackup\Entity\WebApplications\WebApplicationInterface;
use Vendi\InternalTools\DevServerBackup\Entity\WebApplications\WordPressApplication;
use Webmozart\PathUtil\Path;

class PhpApplicationFigureOuter
{
    /**
     * @var NginxSite
     */
    private $nginxSite;

    public function __construct(NginxSite $nginxSite)
    {
        $this->nginxSite = $nginxSite;
    }

    public function get_application() : WebApplicationInterface
    {

        $ret = $this->look_for_wordpress() ??
            $this->look_for_drupal() ??
            $this->look_for_default_site() ??
            $this->look_for_html_only_site() ??
            $this->look_for_generic_env() ??
            $this->look_for_magic_in_files()
        ;

        if(!$ret){
            dump($this->nginxSite);
            $ret = new GeneralWebApplicationWithoutDatabase($this->nginxSite);
        }

        return $ret;
    }

    protected function look_for_wordpress() : ?WordPressApplication
    {
        $finder = new Finder();
        if($finder->depth('== 0')->files()->in($this->nginxSite->get_folder_abs_path())->name('wp-config.php')->hasResults()){
            return new WordPressApplication($this->nginxSite);
        }

        return null;
    }

    protected function look_for_drupal() : ?DrupalApplication
    {
        $finder = new Finder();
        $composer_files = $finder->depth('== 0')->files()->in(dirname($this->nginxSite->get_folder_abs_path()))->name('composer.json');
        if($composer_files->hasResults()) {
            $files = iterator_to_array($composer_files);
            $file = reset($files);

            $json_args = \JSON_OBJECT_AS_ARRAY ;
            if(defined('JSON_THROW_ON_ERROR')){
                $json_args |= \JSON_THROW_ON_ERROR;
            }

            $json = \json_decode($file->getContents(), true, 512, $json_args);
            if(\JSON_ERROR_NONE === \json_last_error() ){
                if(isset($json['require']['drupal/core'])){
                    return new DrupalApplication($this->nginxSite);
                }
            }
        }

        return null;
    }

    protected function look_for_html_only_site() : ?GeneralWebApplicationWithoutDatabase
    {
        if(mb_strpos($this->nginxSite->get_folder_abs_path(), 'html') > 0 ){
            return new GeneralWebApplicationWithoutDatabase($this->nginxSite);
        }

        return null;
    }

    protected function look_for_default_site() : ?GeneralWebApplicationWithoutDatabase
    {
        if('html' === $this->nginxSite->get_project_name()){
            return new GeneralWebApplicationWithoutDatabase($this->nginxSite);
        }

        return null;
    }

    protected function set_property_by_key(GeneralWebApplicationWithDatabase $obj, $key, $value)
    {
        switch($key){
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
                throw new \Exception('The known formats array is not setup correctly.');
        }
    }

    protected function look_for_magic_in_files() : ?GeneralWebApplicationWithDatabase
    {
        $known_files = [
            '../includes/constants.php' => [
                'host' => null,
                'name' => "/^\s*define\(\s*'VENDI_DB_NAME',\s*'(?<VALUE>[^']+)'\s*\);/m",
                'user' => "/^\s*define\(\s*'VENDI_DB_USER',\s*'(?<VALUE>[^']+)'\s*\);/m",
                'pass' => "/^\s*define\(\s*'VENDI_DB_PASS',\s*'(?<VALUE>[^']+)'\s*\);/m",
                'port' => null,
            ],
        ];

        foreach($known_files as $rel_path => $keys){
            $abs_path = Path::canonicalize($this->nginxSite->get_folder_abs_path(), $rel_path);
            dump($abs_path);
            if(!is_file($abs_path)){
                continue;
            }

            $contents = file_get_contents($abs_path);

            $is_valid = true;
            $potential = new GeneralWebApplicationWithDatabase($this->nginxSite);

            foreach($keys as $key => $value) {
                if(!$value){
                    continue;
                }

                if(!preg_match($value, $contents, $matches)){
                    continue;
                }

                $actual_value = $matches['VALUE'];

                $this->set_property_by_key($potential, $key, $actual_value);
            }

            if($is_valid){
                dump('THis worked!');
                return $potential;
            }
        }

        return null;
    }

    protected function look_for_generic_env() : ?GeneralWebApplicationWithDatabase
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
        if($env_files->hasResults()) {
            foreach($env_files as $file){

                $backup = $_ENV;
                foreach($_ENV as $key => $value){
                    unset($_ENV[$key]);
                }

                $dotenv = new Dotenv(false);
                try{
                    $dotenv->loadEnv($file->getPathname());
                }catch(\Exception $ex){
                    $_ENV = $backup;
                    continue;
                }

                foreach($known_formats as $keys){

                    $is_valid = true;

                    $potential = new GeneralWebApplicationWithDatabase($this->nginxSite);

                    foreach($keys as $key => $value){

                        if(!$value){
                            continue;
                        }

                        if(!array_key_exists($value, $_ENV)){
                            $is_valid = false;
                            break;
                        }

                        $this->set_property_by_key($potential, $key, $_ENV[$value]);
                    }

                    if($is_valid){
                        $_ENV = $backup;
                        return $potential;
                    }
                }

                $_ENV = $backup;
            }
        }

        return null;
    }

}
