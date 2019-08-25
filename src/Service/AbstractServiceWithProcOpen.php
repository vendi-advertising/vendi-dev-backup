<?php

declare(strict_types=1);

namespace Vendi\InternalTools\DevServerBackup\Service;

use function fclose;
use function is_resource;
use function proc_close;
use function proc_get_status;
use function proc_open;
use function sleep;
use function stream_get_contents;
use function sys_get_temp_dir;
use function tempnam;

abstract class AbstractServiceWithProcOpen extends AbstractServiceWithLogger
{
    /**
     * @var int
     */
    private $timeoutInSeconds = 120;

    final protected function create_tmp_file(): string
    {
        return tempnam(sys_get_temp_dir(), 'VENDI_BACKUP');
    }

    final protected function run_command($properly_escaped_command, &$command_outputs = null): bool
    {
        //Default to an empty array to erase anything coming in
        $command_outputs = [];

        $this->getLogger()->info('Running shell command', ['command' => $properly_escaped_command]);
        $descriptorspec = [
            0 => ['pipe', 'r'],  // stdin
            1 => ['pipe', 'w'],  // stdout
            2 => ['pipe', 'w'],  // stderr
        ];

        $process = proc_open($properly_escaped_command, $descriptorspec, $pipes);
        if (!is_resource($process)) {
            $this->getLogger()->error('Calling proc_open did not return a resource.');
            return false;
        }

        $exit_code = null;

        //Allow commands to run for 120 seconds
        for ($i = 0; $i < $this->getTimeoutInSeconds(); $i++) {
            $status = proc_get_status($process);
            if (!$status['running']) {
                $exit_code = (int)$status['exitcode'];
                break;
            }

            sleep(1);
        }

        //TODO: We're not handling dangling processes above, I think we need to
        //call proc_get_status( $process ) one last time.

        $stdout = stream_get_contents($pipes[1]);
        fclose($pipes[1]);

        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[2]);

        proc_close($process);

        //Pass to provided array
        $command_outputs = [
            'stdout' => $stdout,
            'stderr' => $stderr,
        ];

        if (null === $exit_code) {
            $this->getLogger()->warning(
                'Process did not complete before timeout, might be dangling',
                [
                    'command' => $properly_escaped_command,
                    'timeout' => $this->getTimeoutInSeconds(),
                    'stderr' => $stderr,
                    'stdout' => $stdout,
                ]
            );
            return false;
        }

        //Non-zero exit code means error
        if (0 !== $exit_code) {
            $this->getLogger()->warning(
                'Process returned non-zero exit code: ' . $exit_code,
                [
                    'command' => $properly_escaped_command,
                    'exit-code' => $exit_code,
                    'stderr' => $stderr,
                    'stdout' => $stdout,
                ]
            );
            return false;
        }

        $this->getLogger()->info('Process completed successfully');
        return true;
    }

    /**
     * @return int
     */
    final public function getTimeoutInSeconds(): int
    {
        return $this->timeoutInSeconds;
    }
}
