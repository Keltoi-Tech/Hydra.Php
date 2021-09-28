<?php
namespace repository;
use persistence\{IProvider,Crud};
use hydra\{Result,Uuid};
use model\{Version};
use PDO;
use DateInterval;

class VersionRepository extends Crud
{
    private function __construct(IProvider $provider){
        parent::__construct($provider);
    }

    public static function getInstance(IProvider $provider){
        return new VersionRepository($provider);
    }

    public function createFirst(){
        $version = new Version();
        $version->newUid();
        $pdo = $this->provider->getPdo();
        $statement = $pdo->prepare("insert into Version(uid) values (:uid)");
        $statement->execute(["uid"=>$version->getUid()]);

        $statement=null;
        $pdo=null;
    }
}
?>