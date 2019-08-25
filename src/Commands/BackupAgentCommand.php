<?php

declare(strict_types=1);

namespace Vendi\InternalTools\DevServerBackup\Commands;

use Symfony\Bridge\Monolog\Handler\ConsoleHandler;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Vendi\InternalTools\DevServerBackup\Service\BackupAgent;

class BackupAgentCommand extends Command
{
    public const EX_USAGE = 64;      // command line usage error
    /*
#define EX_OK           0       // successful termination
#define EX__BASE        64      // base value for error messages
#define EX_DATAERR      65      // data format error
#define EX_NOINPUT      66      // cannot open input
#define EX_NOUSER       67      // addressee unknown
#define EX_NOHOST       68      // host name unknown
#define EX_UNAVAILABLE  69      // service unavailable
#define EX_SOFTWARE     70      // internal software error
#define EX_OSERR        71      // system error (e.g., can't fork)
#define EX_CANTCREAT    73      // can't create (user) output file
#define EX_IOERR        74      // input/output error
#define EX_TEMPFAIL     75      // temp failure; user is invited to retry
#define EX_PROTOCOL     76      // remote error in protocol
#define EX_NOPERM       77      // permission denied
#define EX_CONFIG       78      // configuration error
     */
    protected function configure()
    {
        $this
            ->setName('backup:run')
            ->setDescription('Run the backup')
            ->addArgument('storage-location', InputArgument::REQUIRED, 'Where should backups be stored?')
            ->addOption('database-only', null, InputOption::VALUE_NONE, 'Only backup the database')
            ->addOption('file-system-only', null, InputOption::VALUE_NONE, 'Only backup the file system')
        ;
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $database_only = $input->getOption('database-only');
        $file_system_only = $input->getOption('file-system-only');

        $backup_mode = BackupAgent::BACKUP_MODE_ALL;

        if($file_system_only && $database_only){
            $io->error('You cannot specify both database-only and file-system only. What part of "only" don\'t you get?');
            exit(self::EX_USAGE);
        }elseif($file_system_only){
            $backup_mode = BackupAgent::BACKUP_MODE_FILE_SYSTEM;
        }elseif($database_only){
            $backup_mode = BackupAgent::BACKUP_MODE_DATABASE;
        }

        if (!function_exists('posix_getuid')) {
            $io->error('This command is only intended to be run on Linux machines.');
            exit;
        }

        $is_root = (0 === posix_getuid());
        if (! $is_root) {
            $io->error('The backup command must be run with higher privileges.');
            exit;
        }

        $storage_location = $input->getArgument('storage-location');

        $ba = new BackupAgent($storage_location, $backup_mode);
        $ba->addLoggerSource(new ConsoleHandler($output));
        $ba->run();
    }
}
