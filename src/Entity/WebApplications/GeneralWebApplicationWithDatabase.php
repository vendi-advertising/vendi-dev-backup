<?php

namespace Vendi\InternalTools\DevServerBackup\Entity\WebApplications;

class GeneralWebApplicationWithDatabase extends WebApplicationBase
{
    private $db_user;

    /**
     * @return mixed
     */
    public function getDbUser()
    {
        return $this->db_user;
    }

    /**
     * @param mixed $db_user
     */
    public function setDbUser($db_user): void
    {
        $this->db_user = $db_user;
    }

    /**
     * @return mixed
     */
    public function getDbPass()
    {
        return $this->db_pass;
    }

    /**
     * @param mixed $db_pass
     */
    public function setDbPass($db_pass): void
    {
        $this->db_pass = $db_pass;
    }

    /**
     * @return mixed
     */
    public function getDbHost()
    {
        return $this->db_host;
    }

    /**
     * @param mixed $db_host
     */
    public function setDbHost($db_host): void
    {
        $this->db_host = $db_host;
    }

    /**
     * @return mixed
     */
    public function getDbName()
    {
        return $this->db_name;
    }

    /**
     * @param mixed $db_name
     */
    public function setDbName($db_name): void
    {
        $this->db_name = $db_name;
    }

    /**
     * @return mixed
     */
    public function getDbPort()
    {
        return $this->db_port;
    }

    /**
     * @param mixed $db_port
     */
    public function setDbPort($db_port): void
    {
        $this->db_port = $db_port;
    }
    private $db_pass;
    private $db_host;
    private $db_name;
    private $db_port;

    public function get_application_type(): string
    {
        return 'General Web Application Without Database';
    }

    public function has_database(): bool
    {
        return true;
    }

    public function dump_database(): string
    {
        throw new \Exception('Method not implemented');
    }
}