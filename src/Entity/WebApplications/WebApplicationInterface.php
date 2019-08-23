<?php

namespace Vendi\InternalTools\DevServerBackup\Entity\WebApplications;

interface WebApplicationInterface
{
    public function get_application_type() : string;

    public function has_database() : bool;

    public function dump_database() : string;
}