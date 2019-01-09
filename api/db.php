<?php
// Copyright © 2018 Тепляшин Сергей Васильевич. Contacts: <sergio.teplyashin@gmail.com>
// License: https://opensource.org/licenses/GPL-3.0

class Db
{
    private $connect = null;

    private $host = 'localhost';
    private $db   = 'workflow_enterprise';
    private $user = '';
    private $password = '';
    private $port = 5432;

    public function __construct(array $user_param = [])
    {
        $this->host = $user_param['host'] ?? $this->host;
        $this->db = $user_param['db'] ?? $this->db;
        $this->user = $user_param['user'] ?? $this->user;
        $this->password = $user_param['password'] ?? $this->password;
        $this->port = $user_param['port'] ?? $this->port;
    }

    protected function connectionPDO()
    {
        $dsn = "pgsql:host=$this->host;dbname=$this->db;port=$this->port";
        $opt = array(
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false);
        $this->connect = new PDO($dsn, $this->user, $this->password, $opt);
    }

    public function getConnect(array $user_param = [])
    {
        $old_user = $this->user;
        $old_password = $this->password;

        $this->user = $user_param['user'] ?? $this->user;
        $this->password = $user_param['password'] ?? $this->password;
        
        if ($old_password != $this->password || $old_user !== $this->user)
        {
            $this->connect = null;
        }

        if ($this->connect)
        {
            return $this->connect;
        }
        else
        {
            $this->connectionPDO();
            return $this->connect;
        }
    }
}