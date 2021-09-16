<?php
namespace hydra;
use PDO;

interface IConfig{
    public function getEntity():string;
    public function getAppName():string;
    public function getHash():string;
    public function getExpire();
    public function validateAppHash(string $app, string $code):string;
    public function getMigration():bool;
} 

class Config implements IConfig
{
    private $obj;
	private $entity; 

	function __construct(string $filePath,string $entity)
	{
        $this->entity = $entity;
		$this->obj = json_decode(file_get_contents($filePath));
	}

    public function getAppName():string{
        return $this->obj->appName;
    }

    public function getMigration():bool{
        return $this->obj->migration==1;
    }

    public function getHash():string{
        return hash("sha256","{$this->obj->appName}{$this->obj->code}");
    }

    public function validateAppHash(string $app, string $code):string{
        $actualApp = $this->obj->appName;
        $actualCode = $this->obj->code;
        return hash("sha256","{$actualApp}{$actualCode}") === hash("sha256","{$app}{$code}");
    }

    public function getExpire(){
        return $this->obj->expire;
    }

    public function getEntity():string{
        return $this->entity;
    }

    public static function getInstance(string $filePath,string $entity):Config
    {
        return new Config($filePath,$entity);
    }
}
?>