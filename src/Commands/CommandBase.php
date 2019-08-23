<?php

namespace Vendi\InternalTools\DevServerBackup\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CommandBase extends Command
{

    const DEFAULT_TIMEOUT = 100;

    private $_io;

    private $_next_command_timeout = 100;

    private $_in_multi_command = false;

    private $_warn_about_long_running_command = false;

    public function set_io( SymfonyStyle $io )
    {
        $this->_io = $io;
    }

    public function get_io() : SymfonyStyle
    {
        return $this->_io;
    }

    final protected function set_next_command_timeout( int $seconds )
    {
        if( $seconds > self::DEFAULT_TIMEOUT ) {
            $this->_warn_about_long_running_command = true;
        }

        $this->_next_command_timeout = $seconds;
    }

    final protected function reset_next_command_timeout( )
    {
        $this->set_next_command_timeout( self::DEFAULT_TIMEOUT );
    }

    protected function get_or_create_io( InputInterface $input = null, OutputInterface $output = null ) : SymfonyStyle
    {
        if( ! $this->_io ) {
            if( ! $input || ! $output ) {
                throw new \Exception( 'You must either initialize IO elsewhere or provide Input and Output to do so here.' );
            }

            $this->_io = new SymfonyStyle( $input, $output );
        }
        return $this->_io;
    }

    protected function run_mulitple_commands_with_working_directory( array $commands, string $working_directory = null, bool $quiet = false ) : bool
    {
        $this->_in_multi_command = true;

        foreach( $commands as $command ) {
            if( ! $this->run_command_with_working_directory( $command, "An unknown error occurred while running command ${command}" , $working_directory, $quiet ) ) {
                $this->_in_multi_command = false;
                $this->reset_next_command_timeout();
                return false;
            }
        }

        $this->_in_multi_command = false;
        $this->reset_next_command_timeout();
        return true;
    }

    /**
     * [run_command_with_working_directory description]
     *
     * This is the workhorse function. All run commands lead here so be careful
     * when editing.
     *
     * @param  string       $command               The command to run.
     * @param  string       $failure_error_message The message to display before termintating if there was an error running the command. Ignored if $quiet is true.
     * @param  string|null  $working_directory     Optional, default null. The directory to change to for running the command.
     * @param  bool         $quiet                 Optional, default false. If true, this function will return false on error instead of terminating.
     * @param  array|null   $command_outputs       Optional, default null. An array that will be populated with stdout and stderr for the given command.
     * @return bool                                Returns true for commands that exit with code 0, otherwise false as long as $quiet is true.
     * 
     * NOTE: This code was lifted from the vendi-admin-cli however I think that was lifted from another project. Unfortunately
     * I'm unable to find the original code so I don't quite remember the full logic for multi-command mode. It generally
     * makes sense, however I don't currently remember the specifics. Sorry.
     */
    protected function run_command_with_working_directory( string $command, string $failure_error_message, string $working_directory = null, bool $quiet = false, array &$command_outputs = null) : bool
    {
        $io = $this->get_or_create_io();

        if( $this->_warn_about_long_running_command ) {
            if( ! $quiet ) {
                $io->note( sprintf( 'The next command has been allowed %1$s seconds to run, please be patient', number_format( $this->_next_command_timeout ) ) );
            }
            $this->_warn_about_long_running_command = false;
        }

        $descriptorspec = [
                               0 => [ 'pipe', 'r' ],  // stdin
                               1 => [ 'pipe', 'w' ],  // stdout
                               2 => [ 'pipe', 'w' ],  // stderr
                    ];

        $process = proc_open( $command, $descriptorspec, $pipes, $working_directory );
        if( ! is_resource( $process ) ) {
            if( $quiet ) {
                if( ! $this->_in_multi_command ) {
                    $this->reset_next_command_timeout();
                }
                return false;
            }
            $io->error( "An unknown error occurred while creating a process for the following command:" );
            $io->error( $command );
            exit;
        }

        $exit_code = null;
        for( $i = 0; $i < $this->_next_command_timeout; $i++ ) {
            $status = proc_get_status( $process );
            if( ! $status[ 'running' ] ) {
                $exit_code = $status[ 'exitcode' ];
                break;
            }

            sleep( 1 );
        }

        //TODO: We're not handling dangling processes above, I think we need to
        //call proc_get_status( $process ) one last time.

        $stdout = stream_get_contents( $pipes[ 1 ] );
        fclose( $pipes[ 1 ] );

        $stderr = stream_get_contents( $pipes[ 2 ] );
        fclose( $pipes[ 2 ] );

        proc_close( $process );

        //Pass to provided array
        $command_outputs = [
            'stdout' => $stdout,
            'stderr' => $stderr,
        ];

        //Non-zero exit code means error
        if( 0 !== $exit_code ) {

            if( $quiet ) {

                //Mulit-command mode stuff
                if( ! $this->_in_multi_command ) {
                    $this->reset_next_command_timeout();
                }

                //In quiet mode we just return false
                return false;
            }

            $io->error( $failure_error_message );

            //Dump out some additional hopefully helpful messages
            if( $stderr ) {
                $io->error( $stderr );
            }

            if( $stdout ) {
                $io->error( $stdout );
            }

            exit;
        }

        if( ! $this->_in_multi_command ) {
            $this->reset_next_command_timeout();
        }

        return true;
    }

    protected function run_command( string $command, string $failure_error_message, bool $quiet = false, array &$command_outputs = null ) : bool
    {
        return $this->run_command_with_working_directory( $command, $failure_error_message, null, $quiet, $command_outputs );
    }
}
