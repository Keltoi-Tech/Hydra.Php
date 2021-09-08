<?php 
namespace viewSet;
include_once("model/version.php");
include_once("repository/version.php");
include_once("repository/migration.php");
use hydra\{IConfig,IAuth,ViewSet,Result};
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
    private $appName;

    private function __construct(
        Result              $valid,
        VersionRepository   $versionRepository,
        MigrationRepository $migrationRepository,
        string $appName
    )
    {
        parent::__construct($valid);
        $this->versionRepository = $versionRepository;
        $this->migrationRepository = $migrationRepository;
        $this->appName = $appName;
    }

    function __destruct(){
        parent::__destruct();
        $this->versionRepository= null;
        $this->migrationRepository = null;
    }

    public function postAuthTerraform($entry):Result{
        return 
            isset($entry["app"])?
                isset($entry["password"])?
                    $this->migrationRepository->authTerraform($entry["app"],$entry["password"]):
                    new Result(400,["error"=>"No password provided"]):
                new Result(400,["error"=>"No app provided"]);
    }

    function postTerraform():Result{
        $issuer = $this->getPayload("iss");
        $op = $this->getPayload("sub");
        if ($issuer==$this->appName && $op=="terraform"){
            $result=  $this->migrationRepository->terraform(
                Definition::getInstance(new Version())
            );
            $this->versionRepository->createFirst();
            return $result;
        }else return new Result(403,["error"=>"Operation not allowed"]);        
    }

    public static function getInstance(
        IConfig $config, 
        IProvider $provider, 
        IAuth $auth=null
    ){
        return new MigrationViewSet(
            isset($auth)?
                HS256Jwt::validate($auth->getAuth(),$config->getHash()): 
                new Result(100,["request"=>"token"]),
            VersionRepository::getInstance($provider),
            MigrationRepository::getInstance($provider,$config),
            $config->getAppName()
        );
    }
}
?>