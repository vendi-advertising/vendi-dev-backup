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

        return  $this->look_for_wordpress() ??
            $this->look_for_drupal() ??
            $this->look_for_default_site() ??
            $this->look_for_generic_env() ??
            new GeneralWebApplicationWithoutDatabase($this->nginxSite);
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

    protected function look_for_default_site() : ?GeneralWebApplicationWithoutDatabase
    {
        if('html' === $this->nginxSite->get_project_name()){
            return new GeneralWebApplicationWithoutDatabase($this->nginxSite);
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
        ];

        $finder = new Finder();

        //Don't forget that Finder ignores DotFiles by default!
        //We're limiting to only 3 folders deep for now because there really shouldn't be a configuration
        //that's deeper than that, right?
        $env_files = $finder->ignoreDotFiles(false)->depth('< 3')->files()->in(dirname($this->nginxSite->get_folder_abs_path()))->name('.env');
        if($env_files->hasResults()) {
            foreach($env_files as $file){

                dump($file->getPathname());

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
                            dump('key not found: ' . $value);
                            break;
                        }

                        switch($key){
                            case 'host':
                                $potential->setDbHost($_ENV[$value]);
                                break;

                            case 'name':
                                $potential->setDbName($_ENV[$value]);
                                break;

                            case 'user':
                                $potential->setDbUser($_ENV[$value]);
                                break;

                            case 'pass':
                                $potential->setDbPass($_ENV[$value]);
                                break;

                            case 'port':
                                $potential->setDbPort($_ENV[$value]);
                                break;

                            default:
                                throw new \Exception('The known formats array is not setup correctly.');
                        }
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
