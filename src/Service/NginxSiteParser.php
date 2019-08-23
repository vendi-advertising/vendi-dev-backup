<?php

namespace Vendi\InternalTools\DevServerBackup\Service;

use Vendi\InternalTools\DevServerBackup\Entity\NginxSite;

class NginxSiteParser
{
    /**
     * @param string $stdout
     * @return array
     */
    public function parse_nginx_output(string $stdout) : array
    {
        preg_match_all('/^server\s*\{.*?^\}/ms', $stdout, $server_blocks);

        //Grab the first item from the array
        $server_blocks = reset($server_blocks);

        $ret = [];

        foreach($server_blocks as $server_block) {
            if(!preg_match('/root\s+(?<folder>[^;]+);/', $server_block, $matches)){
                continue;
            }
            if(!array_key_exists('folder', $matches)){
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

            $ret[] = new NginxSite($project_name, $folder_abs_path);
        }

        return $ret;
    }
}