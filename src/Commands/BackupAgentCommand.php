<?php

namespace Vendi\InternalTools\DevServerBackup\Commands;

use Vendi\InternalTools\DevServerBackup\Service\BackupAgent;

class BackupAgentCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('backup:run')
            ->setDescription('Run the backup')
        ;
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $ba = new BackupAgent();
        $ba->run();
    }
}