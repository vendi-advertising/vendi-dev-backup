<?php

declare(strict_types=1);

namespace Vendi\InternalTools\DevServerBackup\Service;

use Vendi\InternalTools\DevServerBackup\Entity\NginxSite;

class NginxSiteParser extends AbstractServiceWithLogger
{
    /**
     * @param string $stdout
     * @return array|null
     */
    public function parse_nginx_output(string $stdout): ?array
    {
        $this->getLogger()->debug('Starting parsing of nginx configuration');

        if (false === preg_match_all('/^server\s*\{.*?^\}/ms', $stdout, $server_blocks)) {
            $this->getLogger()->error('Could not find any server directives in nginx configuration', ['stdout' => $stdout]);
            return null;
        }

        //Grab the first item from the array
        $server_blocks = reset($server_blocks);

        $ret = [];

        foreach ($server_blocks as $server_block) {
            $this->getLogger()->debug('Parsing single server block', ['server_block' => $server_block]);

            if (!preg_match('/root\s+(?<folder>[^;]+);/', $server_block, $matches)) {
                $this->getLogger()->debug('No root directive found, skipping');
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

            $this->getLogger()->info('Found nginx site', ['project_name' => $project_name, '$folder_abs_path' => $folder_abs_path]);

            $ret[] = new NginxSite($project_name, $folder_abs_path);
        }

        return $ret;
    }
}
