<?php

namespace Vendi\InternalTools\DevServerBackup\Service;

use Vendi\InternalTools\DevServerBackup\Entity\NginxSite;

class WordPressDatabaseDumper
{
    /**
     * @var NginxSite
     */
    private $nginxSite;

    private $database;

    /**
     * @return mixed
     */
    public function getDatabase() : string
    {
        return $this->database;
    }

    /**
     * @param mixed $database
     */
    public function setDatabase(string $database): void
    {
        $this->database = $database;
    }

    public function hasDatabase() : bool
    {
        return $this->database ? true : false;
    }

    public function __construct(NginxSite $nginxSite)
    {
        $this->nginxSite = $nginxSite;
    }

    /**
     * @return NginxSite
     */
    public function getNginxSite(): NginxSite
    {
        return $this->nginxSite;
    }

    protected function dump_database() : bool
    {
        $command = sprintf('wp db dump - --path=%1$s', escapeshellarg($this->getNginxSite()->get_folder_abs_path()));

        $descriptorspec = [
            0 => [ 'pipe', 'r' ],  // stdin
            1 => [ 'pipe', 'w' ],  // stdout
            2 => [ 'pipe', 'w' ],  // stderr
        ];

        $process = proc_open( $command, $descriptorspec, $pipes );
        if( ! is_resource( $process ) ) {
            throw new \Exception('Could not create process. Weird.');
        }

        $exit_code = null;

        //Allow commands to run for 120 seconds
        for( $i = 0; $i < 120; $i++ ) {
            $status = proc_get_status( $process );
            if( ! $status[ 'running' ] ) {
                $exit_code = (int) $status[ 'exitcode' ];
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
            throw new \Exception('Process returned error code: ' . $exit_code);
        }

        return true;
    }
}