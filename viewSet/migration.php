<?php 
namespace viewSet;
require_once("repository/version.php");
require_once("service/tokenMigration.php");
require_once("domain/migration.php");

use hydra\{
    IConfig,
    IAuth,
    ViewSet,
    Result
};
use persistence\{
    IProvider,
    Definition,
    Migration
};
use repository\VersionRepository;
use model\Version;
use token\HS256Jwt;
use domain\MigrationDomain;
use service\TokenMigration;

class MigrationViewSet extends ViewSet
{
    private $versionRepository;
    private $appName;
    private $definitions;

    protected function __construct(
        Result              $valid,
        string $appName,        
        TokenMigration $tokenMigration,
        Migration $migration,       
        VersionRepository   $versionRepository
    )
    {
        parent::__construct($valid);
        $this->versionRepository = $versionRepository;
        $this->appName = $appName;
        $this->tokenMigration = $tokenMigration;
        $this->migrationDomain = new MigrationDomain($migration);

        $this->definitions=[
            Definition::getInstance(new Version())
        ];
    }

    function __destruct(){
        parent::__destruct();
        $this->versionRepository= null;
        $this->tokenMigration = null;
        $this->migrationDomain = null;
    }

    public function postAuthTerraform($entry):Result{
        return (
            isset($entry["app"])?
            (
                isset($entry["password"])?
                    $this->tokenMigration->raiseTerraformToken($entry["app"],$entry["password"]):
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
                    $this->tokenMigration->raiseMigrationToken($entry["app"],$entry["password"]):
                    new Result(400,["error"=>"No password provided"])
            ):
            new Result(400,["error"=>"No app provided"])
        );
    }    

    public function postTerraform():Result{
        $issuer = $this->getPayload("iss");
        $op = $this->getPayload("sub");

        if ($issuer===false || $op===false)
            return new Result(401,["error"=>"Unauthorized"]);

        if ($issuer==$this->appName && $op=="terraform"){
            $result= $this->migrationDomain->terraform($this->definitions);
            if ($result->assert(201)){
                $this->versionRepository->begin();
            }
            return $result;
        }else return new Result(403,["error"=>"Operation not allowed"]);        
    }

    public function postMigration():Result{
        $issuer = $this->getPayload("iss");
        $op = $this->getPayload("sub");

        if ($issuer==$this->appName && $op=="migration"){
            $version = $this->versionRepository->exists(1);
            if ($version==null){
                $result = $this->migrationDomain->migration($this->definitions);
                if ($result->assert(201)){
                    $this->versionRepository->increment($version);
                }
                return $result;
            }else return new Result(404,["error"=>"Version not found"]);
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
            $config->getAppName(),
            new TokenMigration($config),
            new Migration($provider),
            VersionRepository::getInstance($provider)
        );
    }
}
?>