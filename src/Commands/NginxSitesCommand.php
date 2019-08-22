<?php

namespace Vendi\InternalTools\DevServerBackup\Commands;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class NginxSitesCommand extends CommandBase
{
    protected function configure()
    {
        $this
            ->setName('app:nginx')
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

        $is_root = ( 0 === posix_getuid() );
        if( ! $is_root ) {
            $io->error( 'The backup command must be run with higher privileges.' );
            exit;
        }

        $command_outputs = [];

        $this->run_command('nginx -T', 'Could not get nginx config', false, $command_outputs);
        $stdout = $command_outputs['stdout'];

        preg_match_all('/^server\s*\{.*?^\}/ms', $stdout, $server_blocks);

        //Grab the first item from the array
        $server_blocks = reset($server_blocks);

        foreach($server_blocks as $server_block) {
            if(!preg_match('/root\s+(?<folder>[^;]+);/', $server_block, $matches)){
                continue;
            }
            if(!array_key_exists('folder', $matches)){
                continue;
            }

            $folder_abs_path = $matches['folder'];
            $folder_parts = explode('/', $folder_abs_path);

            //Ignore the first three items in the path
            //TODO: This should be smarter
            array_shift($folder_parts);
            array_shift($folder_parts);
            array_shift($folder_parts);
            $project_name = array_shift($folder_parts);

            dump([
                'project_name' => $project_name,
                'folder_abs_path' => $folder_abs_path,
            ]);

            // dump();
        }

        // dump($stdout);
        // dump($matches);
        // if(!$)

    }
}
