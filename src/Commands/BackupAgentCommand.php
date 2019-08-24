<?php

namespace Vendi\InternalTools\DevServerBackup\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Vendi\InternalTools\DevServerBackup\Service\BackupAgent;

class BackupAgentCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('backup:run')
            ->setDescription('Run the backup')
            ->addArgument('storage-location', InputArgument::REQUIRED, 'Where should backups be stored?')
        ;
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        if(!function_exists('posix_getuid')){
            $io->error( 'This command is only intended to be run on Linux machines.' );
            exit;
        }

        $is_root = ( 0 === posix_getuid() );
        if( ! $is_root ) {
            $io->error( 'The backup command must be run with higher privileges.' );
            exit;
        }

        $storage_location = $input->getArgument('storage-location');

        $ba = new BackupAgent();
        $ba->run();
    }
}