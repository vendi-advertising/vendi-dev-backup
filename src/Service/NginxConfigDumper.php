<?php

declare(strict_types=1);

namespace Vendi\InternalTools\DevServerBackup\Service;

use Psr\Log\LoggerInterface;

class NginxConfigDumper extends ServiceWithProcOpen
{
    public function get_nginx_config(): string
    {
        $this->getLogger()->debug('Starting dump of nginx configuration');
        $command = 'nginx -T';
        if($this->run_command($command, $command_outputs)){
            $this->getLogger()->debug('Dump of nginx configuration completed successfully');
            return $command_outputs['stdout'];
        }

        $this->getLogger()->error('Could not get nginx configuration');
    }
}
