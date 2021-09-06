<?php 
namespace viewSet;
include_once("model/version.php");
include_once("repository/version.php");
include_once("repository/migration.php");
use hydra\{IAuth,ViewSet,Result};
use model\Version;
use repository\{VersionRepository,MigrationRepository};
use persistence\{IProvider,Migration,Defintion};
use token\HS256Jwt;

class MigrationViewSet extends ViewSet
{
    private $versionRepository;
    private $migrationRepository;

    private function __construct(
        Result              $valid,
        VersionRepository   $versionRepository,
        MigrationRepository $migrationRepository
    )
    {
        parent::__construct($valid);
        $this->versionRepository = $versionRepository;
        $this->migrationRepository = $migrationRepository;
    }

    function __destruct(){
        parent::__destruct();
        $this->versionRepository= null;
        $this->migrationRepository = null;
    }

    function postTerraform():Result{
        return $this->migrationRepository->terraform(
            Definition::getInstance(new Version())
        );
    }

    public static function getInstance(IProvider $provider, IAuth $auth=null){
        return new MigrationViewSet(
            isset($auth)?
                HS256Jwt::validate($auth->getAuth(),$provider->getHash()): 
                new Result(401,["error"=>"No auth provided"]),
            VersionRepository::getInstance($provider),
            MigrationRepository::getInstance($provider)
        );
    }
}
?>