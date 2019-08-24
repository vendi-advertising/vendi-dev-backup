<?php

declare(strict_types=1);

namespace Vendi\InternalTools\DevServerBackup\Entity;

class NginxSite
{
    private $project_name;

    private $folder_abs_path;

    public function __construct(string $project_name, string $folder_abs_path)
    {
        $this->project_name = $project_name;
        $this->folder_abs_path = $folder_abs_path;
    }

    public function get_project_name() : string
    {
        return $this->project_name;
    }

    public function get_folder_abs_path() : string
    {
        return $this->folder_abs_path;
    }
}
