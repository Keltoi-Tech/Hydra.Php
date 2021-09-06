<?php 
namespace viewSet;
include_once("model/version.php");
include_once("repository/version.php");
include_once("repository/migration.php");
use hydra\{IAuth,ViewSet,Result};
use repository\{VersionRepository,MigrationRepository};
use persistence\{IProvider,Migration,Definition};
use token\HS256Jwt;
use model\{
    Version
};

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

    public function postAuthTerraform():Result{
        return $this->migrationRepository->authTerraform();
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
                new Result(100,["request"=>"token"]),
            VersionRepository::getInstance($provider),
            MigrationRepository::getInstance($provider)
        );
    }
}
?>