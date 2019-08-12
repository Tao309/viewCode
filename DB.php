<?php

class DB
{
    const TYPE_INSERT = 'insert';
    const TYPE_UPDATE = 'update';
    const TYPE_DELETE = 'delete';
    const TYPE_SAVE = 'save';
    const TYPE_ERROR = 'error';
    const TYPE_SHOW_MODAL_WINDOW = 'modal_window';

    const TYPE_HTML_ELEMENT = 'htmlElement';

    private $db_host;
    private $db_user;
    private $db_password;
    private $db_name;

    private $query;

    private static $mysql_connect = null;

    public function __construct()
    {
        $config = [];
        require(zROOT.'config/mysql.php');

        if(
            !isset($config['DB_HOST'])
            || !isset($config['DB_USER'])
            || !isset($config['DB_PASSWORD'])
            || !isset($config['DB_NAME'])
        )
        {
            die('Can not use all variables for connect');
        }

        $this->db_host = $config['DB_HOST'];
        $this->db_user = $config['DB_USER'];
        $this->db_password = $config['DB_PASSWORD'];
        $this->db_name = $config['DB_NAME'];

        if (self::$mysql_connect === null) {
            $db_connect = @mysqli_connect($this->db_host, $this->db_user, $this->db_password, $this->db_name);

            if(mysqli_connect_errno($db_connect)) {
                echo 'MySQL connect error!<br/>';
                echo mysqli_connect_errno($db_connect);
                exit;
            }
            else
            {
                mysqli_query($db_connect, "SET NAMES utf8");
                self::$mysql_connect = $db_connect;
            }
        }
        else
        {
            
        }
    }

    public function __destruct()
    {
        if(self::$mysql_connect)
        {
            
        }
    }

    private $type;
    private $lastInsertId = 0;

    private function query($query = null)
    {
        if($this instanceof DB\connection && $this->getInit())
        {
            $query = $this->getSqlRow();
        }

        if($a = @mysqli_query(self::$mysql_connect, $query))
        {
            if($this->type == self::TYPE_INSERT)
            {
                $this->lastInsertId = mysqli_insert_id(self::$mysql_connect);
            }

            return $a;
        }
        else
        {
            $e = '<b>Номер ошибки:</b> '.mysqli_errno(self::$mysql_connect).PAGE_BR;
            $e .= '<b>Ошибка:</b> '.PAGE_BR;
            $e .= mysqli_error(self::$mysql_connect).PAGE_BR;
            $e .= '<b>Запрос:</b>'.PAGE_BR;
            $e .= $query;
            die($e);
        }
    }

    public function escape($query = null) {
        return @mysqli_real_escape_string(self::$mysql_connect, $query);
    }

    public function fetchNum($query = null) {
        return @mysqli_fetch_all($this->query($query), MYSQLI_NUM);
    }

    private function fetch($query = null)
    {
        return @mysqli_fetch_all($this->query($query), MYSQLI_ASSOC);
    }
    private function assoc($query = null)
    {
        return @mysqli_fetch_assoc($this->query($query));
    }
    private function array($query = null)
    {
        return @mysqli_fetch_array($this->query($query));
    }
    private function row($query = null)
    {
        return @mysqli_fetch_row($this->query($query));
    }
    private function foundRows()
    {
        $query = 'SELECT FOUND_ROWS() AS count';
        return $this->assoc($query)['count'];
    }

    public function findColumn($query = null)
    {
        return $this->array($query)[0];
    }
    public function find($query = null)
    {
        return $this->assoc($query);
    }

    public function findAll($query = null, $useAllCount = false)
    {
        $oneQuery = $this->fetch($query);

        if($useAllCount)
        {
            return [
                'rows' => $oneQuery,
                'allCount' => $this->foundRows(),
            ];
        }

        return $oneQuery;
    }

    public function update($query = null)
    {
        $this->type = self::TYPE_UPDATE;
        return $this->query($query);
    }

    public function delete($query = null)
    {
        $this->type = self::TYPE_DELETE;
        return $this->query($query);
    }

    public function insert($query = null)
    {
        $this->type = self::TYPE_INSERT;
        return $this->query($query);
    }

    public function getLastInsertId()
    {
        return $this->lastInsertId;
    }
}