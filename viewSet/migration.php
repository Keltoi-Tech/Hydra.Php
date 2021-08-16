<?php 
namespace viewSet;
include_once("model/migration.php");
include_once("repository/migration.php");
use hydra\{IAuth,ViewSet,Result};
use model\Migration;
use repository\MigrationRepository;
use persistence\IProvider;
use token\HS256Jwt;

class MigrationViewSet extends ViewSet
{
    private $migrationRepository;

    private function __construct(
        Result              $valid,
        MigrationRepository   $migrationRepository
    )
    {
        parent::__construct($valid);
        $this->migrationRepository = $migrationRepository;
    }

    function __destruct(){
        parent::__destruct();
        $this->migrationRepository= null;
    }

    function create($entry){
        $id= $entry["id"];

        
    }

    public static function getInstance(IProvider $provider, IAuth $auth=null){
        return new MigrationViewSet(
            isset($auth)?
                HS256Jwt::validate($auth->getAuth(),$provider->getHash()): 
                new Result(401,["error"=>"No auth provided"]),
            MigrationRepository::getInstance($provider)
        );
    }
}
?>