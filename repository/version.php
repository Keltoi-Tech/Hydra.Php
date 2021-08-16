<?php
namespace repository;
include_once("token.php");   
use token\{HS256Jwt,ObjectToken,Token};
use persistence\IProvider,Crud}
use hydra\{Result};
use model\{Version};
use PDO;
use DateInterval;

class VersionRepository extends Crud{
    private function __construct(IProvider $provider,Terraform){
        parent::__construct($provider);
    }

    public static function getInstance(IProvider $provider){
        return new VersionRepository($provider);
    }

    public function create(Version $version){
        $this->insert($version);
        $terraform->exec($this->provider->getPdo());
    }
}
?>