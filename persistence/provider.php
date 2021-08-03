<?php
namespace persistence;
use PDO;

//DATA PROVIDER
interface IProvider{
    public function getPdo():PDO;
    public function getCall():string;
    public function getHash():string;
} 

class Provider implements IProvider
{
	private $dbms;
	private $host;
	private $database;
	private $login;
	private $password;
	private $callVerb;
	private $mustCallVerb;
    private $issuer;
	
	function __construct($filePath)
	{
		$obj = json_decode(file_get_contents($filePath));
		
		$this->dbms = $obj->dbms;
		$this->host = $obj->host;
		$this->database = $obj->database;
		$this->login = $obj->login;
		$this->password = $obj->password;
		$this->callVerb = $obj->callVerb;
		$this->mustCallVerb = $obj->mustCallVerb;
        $this->issuer = $obj->issuer;
	}

    public function getHash():string
    {
        return hash("sha256",$this->issuer);
    }

    public function getPdo():PDO
    {
        $dsn = "{$this->dbms}:host={$this->host};dbname={$this->database};charset=utf8";
        $result =  new PDO($dsn,$this->login,$this->password);
        return $result;
    }
	
	public function getCall():string
	{
		return ($this->mustCallVerb)?$this->callVerb. ' ':'';
	}

    public static function getInstance($filePath):Provider
    {
        return new Provider($filePath);
    }
}
?>