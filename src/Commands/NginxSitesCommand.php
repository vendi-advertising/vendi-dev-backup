<?php

namespace Vendi\InternalTools\DevServerBackup\Commands;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Vendi\InternalTools\DevServerBackup\Service\NginxSiteParser;

class NginxSitesCommand extends CommandBase
{
    protected function configure()
    {
        $this
            ->setName('nginx:list-sites')
            ->setDescription('Get all configured nginx sites')
        ;
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->set_io(new SymfonyStyle($input, $output));

        if(!function_exists('posix_getuid')){
            $this->get_io()->error( 'This command is only intended to be run on Linux machines.' );
            exit;
        }

        $is_root = ( 0 === posix_getuid() );
        if( ! $is_root ) {
            $this->get_io()->error( 'The backup command must be run with higher privileges.' );
            exit;
        }

        $command_outputs = [];

        $this->run_command('nginx -T', 'Could not get nginx config', false, $command_outputs);
        $stdout = $command_outputs['stdout'];

        $parser = new NginxSiteParser();
        $sites = $parser->parse_nginx_output($stdout);

        $rows = [];
        foreach($sites as $site) {
            $rows[] = [$site->get_project_name(), $site->get_folder_abs_path() ];
        }

        $this->get_io()->table(['Projects', 'Folders'], $rows);

//        dump($sites);

    }
}
