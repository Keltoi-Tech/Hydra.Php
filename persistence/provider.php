<?php
namespace persistence;
use PDO;

//DATA PROVIDER
interface IProvider{
    public function getPdo():PDO;
    public function getFor();
} 

class Provider implements IProvider
{
    private $obj;
	
	function __construct($filePath)
	{
		$this->obj = json_decode(file_get_contents($filePath));
	}

    public function getPdo():PDO
    {
        $dsn = "{$this->obj->dbms}:host={$this->obj->host};dbname={$this->obj->database};charset=utf8";
        $result =  new PDO($dsn,$this->obj->login,$this->obj->password);
        return $result;
    }

    public function getFor(){
        return $this->obj->for;
    }

    public static function getInstance($filePath):Provider
    {
        return new Provider($filePath);
    }
}
?>