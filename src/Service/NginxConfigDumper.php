<?php

namespace Vendi\InternalTools\DevServerBackup\Service;

class NginxConfigDumper extends ServiceWithProcOpen
{
    public function get_nginx_config() : string
    {
        $command = 'nginx -T';
        $this->run_command($command, $command_outputs);

        return $command_outputs['stdout'];
    }
}