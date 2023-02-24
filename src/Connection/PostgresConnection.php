<?php
// Copyright © 2018-2023 Тепляшин Сергей Васильевич. Contacts: <sergio.teplyashin@gmail.com>
// License: https://opensource.org/licenses/GPL-3.0

namespace App\Connection;

use PDO;

class PostgresConnection {
    private static $connect = null;

    private $pdo  = null;

    public static function __callStatic($name, $args) {
        if ($name == 'get') {
            
            if (static::$connect === null) {
                static::$connect = new static();
            }

            switch (count($args)) {
                case 0:
                    return call_user_func_array(array(static::$connect, 'getCurrent'), $args);
                case 1:
                    return call_user_func_array(array(static::$connect, 'getByUser1'), $args);
                case 2:
                    return call_user_func_array(array(static::$connect, 'getByUser2'), $args);
             }
        }
    }

    public function getRow($sql, $args = null) {
        $query = $this->pdo->prepare($sql);
        if (isset($args)) {
            foreach ($args as $key => $value) {
                $query->bindValue(":{$key}", $value);
            }
        }

        $query->execute();
        return $query->fetch(\PDO::FETCH_OBJ);
    }

    public function perform($sql, $args = null) {
        $query = $this->pdo->prepare($sql);
        if (isset($args)) {
            foreach ($args as $key => $value) {
                $query->bindValue(":{$key}", $value);
            }
        }

        $query->execute();
    }

    public function execute($sql, $args = null) {
        $query = $this->pdo->prepare($sql);
        if (isset($args)) {
            foreach ($args as $key => $value) {
                $query->bindValue(":{$key}", $value);
            }
        }

        $query->execute();

        $total_rows = $query->rowCount();
        $rows = $query->fetchAll();

        $data = [ 'total_rows' => $total_rows, 'rows' => $rows ];

        return $data;
    }

    public function insert($entity, $args = null) {
        if ($args and count($args) > 0) {
            $fields = array();
            $values = array();
            foreach ($args as $key => $value) {
                $fields[] = $key;
                $values[] = ':'.$key;
            }

            $str_fields = implode(',', $fields);
            $str_values = implode(',', $values);

            $sql = "insert into {$entity} ({$str_fields}) values ({$str_values}) returning id";
            $query = $this->pdo->prepare($sql);

            foreach ($args as $key => $value) {
                $query->bindValue(':'.$key, $value);
            }
        } else {
            $query = $this->pdo->prepare("insert into {$entity} default values returning id");
        }

        $query->execute();

        $id = $query->fetch(PDO::FETCH_NUM);
        return $id[0];
    }

    public function updateAll($entity, $id, $fields, $params) {
        $set = array();
        foreach ($fields as $value) {
            $set[] = $value.'=:'.$value;
        }

        $str_set = implode(',', $set);

        $sql = "update {$entity} set {$str_set} where id = :id";
        $query = $this->pdo->prepare($sql);

        foreach ($params as $key => $value) {
            $query->bindValue(':'.$key, $value);
        }

        $query->bindValue(':id', $id);

        $query->execute();
    }

    public function update($entity, $id, $params) {
        $set = array();
        foreach ($params as $key => $value) {
            $set[] = $key.'=:'.$key;
        }

        $str_set = implode(',', $set);

        $sql = "update {$entity} set {$str_set} where id = :id";
        $query = $this->pdo->prepare($sql);

        foreach ($params as $key => $value) {
            $query->bindValue(':'.$key, $value);
        }

        $query->bindValue(':id', $id);

        $query->execute();
    }

    public function delete($entity, $id) {
        $sql = "update {$entity} set deleted = true where id = :id";
        $query = $this->pdo->prepare($sql);

        $query->bindValue(':id', $id);
        $query->execute();
    }

    public function wipe($entity, $id) {
        $sql = "delete from {$entity} where id = :id";
        $query = $this->pdo->prepare($sql);

        $query->bindValue(':id', $id);
        $query->execute();
    }

    protected function __construct() {

    }

    private function getCurrent() {
        return static::$connect;
    }

    private function getByUser1($user) {
        $this->createConnect($user['user_name'], $user['password']);
        return static::$connect;
    }

    private function getByUser2($user_name, $password) {
        $this->createConnect($user_name, $password);
        return static::$connect;
    }

    private function createConnect($user_name, $password) {
        $dsn = 'pgsql' .
               ':host=' . getenv('DB_HOST') .
               ';dbname=' . getenv('DB_NAME') .
               ';port=' . getenv('DB_PORT');
        $opt = array(
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false);
        $this->pdo = new PDO($dsn, $user_name, $password, $opt);
    }
}

?>