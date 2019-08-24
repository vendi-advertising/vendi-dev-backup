<?php

declare(strict_types=1);

namespace Vendi\InternalTools\DevServerBackup\Service\DatabaseDumpers;

interface DatabaseDumperInterface
{
    public function dump_database();
}
