<?php

namespace Vendi\InternalTools\DevServerBackup\Service\DatabaseDumpers;

interface DatabaseDumperInterface
{
    public function dump_database();
}