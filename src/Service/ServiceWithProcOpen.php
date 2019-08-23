<?php

namespace Vendi\InternalTools\DevServerBackup\Service;

abstract class ServiceWithProcOpen
{
    protected function run_command($properly_escaped_command, &$command_outputs = null) : bool
    {
        $descriptorspec = [
            0 => [ 'pipe', 'r' ],  // stdin
            1 => [ 'pipe', 'w' ],  // stdout
            2 => [ 'pipe', 'w' ],  // stderr
        ];

        $process = \proc_open( $properly_escaped_command, $descriptorspec, $pipes );
        if( ! \is_resource( $process ) ) {
            throw new \Exception('Could not create process. Weird.');
        }

        $exit_code = null;

        //Allow commands to run for 120 seconds
        for( $i = 0; $i < 120; $i++ ) {
            $status = \proc_get_status( $process );
            if( ! $status[ 'running' ] ) {
                $exit_code = (int) $status[ 'exitcode' ];
                break;
            }

            \sleep( 1 );
        }

        //TODO: We're not handling dangling processes above, I think we need to
        //call proc_get_status( $process ) one last time.

        $stdout = \stream_get_contents( $pipes[ 1 ] );
        \fclose( $pipes[ 1 ] );

        $stderr = \stream_get_contents( $pipes[ 2 ] );
        \fclose( $pipes[ 2 ] );

        \proc_close( $process );

        //Pass to provided array
        $command_outputs = [
            'stdout' => $stdout,
            'stderr' => $stderr,
        ];

        //Non-zero exit code means error
        if( 0 !== $exit_code ) {
            throw new \Exception('Process returned error code: ' . $exit_code);
        }

        return true;
    }
}