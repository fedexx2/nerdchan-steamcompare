<?php defined('DIRECTACCESS') OR die('No direct script access.');

function return_OK($data = "")
{
    $ret = new stdClass();
    $ret->result = "OK";
    $ret->data = $data;
    echo json_encode($ret);
    die();
}

function return_ERR($err)
{
    $ret = new stdClass();
    $ret->result = "ERR";
    $ret->data = $err;
    echo json_encode($ret);
    die();
}

function return_RST()
{
    $ret = new stdClass();
    $ret->result = "RST";
    echo json_encode($ret);
    die();
}

class Input {

    static public function get($key, $default = NULL) {
        if (isset($_GET[$key]))
            return $_GET[$key];
        else
            return $default;
    }

    static public function post($key, $default = NULL) {
        if (isset($_POST[$key]))
            return $_POST[$key];
        else
            return $default;
    }
}

class Db {

    static private $_db;
    static private $_db_info;
    
    static private $_lastRes;

    static public function Init($db_info) {
        self::$_db_info = $db_info;
    }

    static private function connect() {
        if (is_null(self::$_db)) {
            $host = self::$_db_info['host'];
            $database = self::$_db_info['database'];
            $username = self::$_db_info['username'];
            $password = self::$_db_info['password'];
            self::$_db = new PDO("mysql:host={$host};dbname={$database};charset=utf8", $username, $password);
            self::$_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }
    }

    static public function queryArray($qry, $params = NULL) {
        return self::query($qry, $params)->fetchAll(PDO::FETCH_OBJ);        
    }

    static public function queryRow($qry, $params = NULL) {
        return self::query($qry, $params)->fetch(PDO::FETCH_OBJ);
    }
    
    static public function closeLast() {
        self::$_lastRes->closeCursor();
    }

    static public function query($qry, $params = NULL) {
        self::connect();
        try {
            if ($params == NULL) {
                self::$_lastRes = self::$_db->query($qry);
            } else {
                self::$_lastRes = self::$_db->prepare($qry);
                self::$_lastRes->execute($params);
            }
            return self::$_lastRes;
        } catch (PDOException $ex) {
            if (function_exists("dd"))
                dd($ex);
            else
                die(var_dump($ex));
        }
    }

    static public function queryScalar($qry, $params = NULL) {
        $res = self::query($qry, $params);
        return $res->fetchColumn();
    }
}

class Cookie {

    public static function get($key, $default = NULL) {
        if (!isset($_COOKIE[$key]))
            return $default;
        return $_COOKIE[$key];
    }

    public static function set($key, $value, $expiration) {
        return setcookie($key, $value, $expiration, SITEPATH, NULL, FALSE, FALSE);
    }

    public static function delete($key) {
        unset($_COOKIE[$key]);
        return setcookie($key, NULL, -86400, SITEPATH, NULL, FALSE, FALSE);
    }
}


function startsWith($haystack, $needle) {
    // search backwards starting from haystack length characters from the end
    return $needle === "" || strrpos($haystack, $needle, -strlen($haystack)) !== false;
}

function endsWith($haystack, $needle) {
    // search forward starting from end minus needle length characters
    return $needle === "" || (($temp = strlen($haystack) - strlen($needle)) >= 0 && strpos($haystack, $needle, $temp) !== false);
}

function str_contains_both($str1, $str2) {    
    return (strpos($str1, $str2) !== false) || (strpos($str2, $str1) !== false);
}

function str_contains($needle, $haystack) {
    return strpos($haystack, $needle) !== false;
}

function strextract($source, $start, $end, &$offset)
{
    $l = strlen($start);
    $pos = strpos($source, $start, $offset);
    if($pos === false) return NULL;

    $end = strpos($source, $end, $pos+$l);
    $value = substr($source, $pos+$l, $end-$pos-$l);
    $offset = $end;
    return $value;
}

function php_is_cli()
{
    return (php_sapi_name() === 'cli');
}

function argv1($a)
{
    global $argv;
    return (isset($argv[1]) && strtolower($argv[1]) == $a); 
}

function askSteam($url)
{    
    $data = file_get_contents($url);
    if($data === false) return_ERR("Error requesting data from Steam");
    $o = json_decode($data);
    if(!$o) return_ERR("Error decoding steam json");
    return($o);
}

function INPUT($key) {
    return Input::get($key);
}

class SubStrIndex 
{
    private $_index = [];
    private $_length;
    
    function __construct(array $gamess) 
    {
        $this->_length = count($gamess);
        $len=count($gamess);
        for($i=0; $i<$len; $i++)
        {
            $ss = $gamess[$i];
            isset($this->_index[$ss]) ? $this->_index[$ss][] = $i : $this->_index[$ss] = [$i];
        }
    }
    
    function length()
    {
        return $this->_length;
    }
    
    function findFrom($ss, $pos)
    {
               
        foreach($this->_index[$ss] as $i)
        {
            
            if($i > $pos) return $i;
        }
        return -1;
    }
    
    function dump()
    {
        var_dump($this->_index);
    }
}

function matchO1(array $orig, SubStrIndex $idx)  //orig=da DB, //test=ta TXTA
{
    $oCnt = count($orig);
    if($oCnt == 1) return 1; 
    if($oCnt == 2) {
        $a = $orig[0];
        $b = $orig[1];
        
        $v1 = $idx->findFrom($a, 0);
        $v2 = $idx->findFrom($b, 0);
        return ($v1<$v2)? 2:1;
    }
    
    $oLen = count($orig);
    $max = 0;

    for($os = 0; $os < $oLen; $os++) 
    {
        $match=0;
        $oc = $os;
        $t = -1;     
       
        while(true) 
        {
            $ss = $orig[$oc];
            $newt = $idx->findFrom($ss, $t);
            
            if($newt == -1) 
                break;
            
            $t = $newt;
            $match++;
            $oc++;
            
            if($oc == $oLen) break;
        }
        if($max<$match) $max=$match;
    }
    return $max;
}

function matchUnoptimized(array $orig, array $test) 
{
    $os = 0; //orig start
    $oc = 0; //orig cursor
    $t  = 0; //test cursor

    $oLen = count($orig);
    $tLen = count($test);
    $max = 0;

    for($os = 0; $os < $oLen; $os++) 
    {
        $match=0;
        $oc = $os;
        $t = 0;
        
        while(true)
        {
            if($orig[$oc] == $test[$t]) {
                
                $match++;
                $t++;
                $oc++;
            }
            else
            {
                $t++;
            }
            
            if($t==$tLen || $oc == $oLen)
            {
                if($max<$match) $max=$match;
                break;
            }
        }
    }
    return $max;
}