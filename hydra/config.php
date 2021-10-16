<?php
namespace hydra;

interface IConfig{
    public function getEntity():string;
    public function getAppName():string;
    public function getHash():string;
    public function validateAppHash(string $app, string $code):string;
    public function getMigration():object;
    public function getSubConfig(string $name):object;
} 

class Config implements IConfig
{
    protected $obj;
	protected $entity; 

	protected function __construct(string $filePath,string $entity)
	{
        $this->entity = $entity;
		$this->obj = json_decode(file_get_contents($filePath));
	}

    public function getSubConfig(string $name): object
    {
        return $this->obj->{$name};
    }

    public function getAppName():string{
        return $this->obj->appName;
    }

    public function getMigration():object{
        return $this->obj->migration;
    }

    public function getHash():string{
        return hash("sha256","{$this->obj->appName}{$this->obj->code}");
    }

    public function validateAppHash(string $app, string $code):string{
        $actualApp = $this->obj->appName;
        $actualCode = $this->obj->code;
        return hash("sha256","{$actualApp}{$actualCode}") === hash("sha256","{$app}{$code}");
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