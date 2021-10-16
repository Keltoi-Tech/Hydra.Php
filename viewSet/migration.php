<?php 
namespace viewSet;
include_once("repository/version.php");
include_once("repository/migration.php");
use hydra\{IConfig,IAuth,ViewSet,Result};
use repository\{VersionRepository,MigrationRepository};
use persistence\{IProvider,Definition,Joining};
use token\HS256Jwt;
use model\{
    Version,
    User,
    Role,
    Profile,
    Feature,
    ProfileRoleFeature
};

class MigrationViewSet extends ViewSet
{
    private $versionRepository;
    private $migrationRepository;
    private $appName;
    private $definitions;

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

        $this->definitions=[
            Definition::getInstance(new Version())
        ];
    }

    function __destruct(){
        parent::__destruct();
        $this->versionRepository= null;
        $this->migrationRepository = null;
    }

    public function postAuthTerraform($entry):Result{
        return (
            isset($entry["app"])?
            (
                isset($entry["password"])?
                    $this->migrationRepository->authTerraform($entry["app"],$entry["password"]):
                    new Result(400,["error"=>"No password provided"])
            ):
            new Result(400,["error"=>"No app provided"])
        );
    }

    public function postAuthMigration($entry):Result{
        return (
            isset($entry["app"])?
            (
                isset($entry["password"])?
                    $this->migrationRepository->authMigration($entry["app"],$entry["password"]):
                    new Result(400,["error"=>"No password provided"])
            ):
            new Result(400,["error"=>"No app provided"])
        );
    }    

    function postTerraform():Result{
        $issuer = $this->getPayload("iss");
        $op = $this->getPayload("sub");

        if ($issuer==$this->appName && $op=="terraform"){
            $result= $this->migrationRepository->terraform($this->definitions);
            if ($result->assert(201)){
                $version = new Version();
                $version->newUid();
                $this->versionRepository->insert($version);
            }
            return $result;
        }else return new Result(403,["error"=>"Operation not allowed"]);        
    }

    function postMigration():Result{
        $issuer = $this->getPayload("iss");
        $op = $this->getPayload("sub");

        if ($issuer==$this->appName && $op=="migration"){
            $version = new Version();
            $version->setId(1);
            $versionOrFail = $this->versionRepository->get($version);
            if ($versionOrFail->assert(100)){
                $result = $this->migrationRepository->migration($this->definitions);
                if ($result->assert(201)){
                    $version->add();
                    $this->versionRepository->update($version);
                }
                return $result;
            }else return $versionOrFail;
        }return new Result(403,["error"=>"Operation not allowed"]);        
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