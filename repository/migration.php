<?php
namespace repository;
use persistence\{IProvider,Crud,Migration};
use hydra\{Result,IConfig};
use token\{HS256Jwt,ObjectToken};
use DateInterval;

class MigrationRepository extends Crud
{
    private $migration;
    private $config;
    private function __construct(IProvider $provider,IConfig $config){
        parent::__construct($provider);
        $this->migration = new Migration($provider,$config);
        $this->config = $config;
    }

    public static function getInstance(IProvider $provider,IConfig $config){
        return new MigrationRepository($provider,$config);
    }

    public function authTerraform(string $app, string $password):Result{
        if ($this->config->validateAppHash($app,$password)){
            $secondsToExpire = $this->config->getMigration()->expire;
            $secret = $this->config->getHash();
            $expire = date_create();
            $expire->add(new DateInterval("PT{$secondsToExpire}S"));
            $now = date_create();
            $jwt = HS256Jwt::getInstance($secret);
            $token = $jwt->getToken(
                ObjectToken::getInstance([
                    "alg"=>"HS256",
                    "typ"=>"JWT"
                ]),
                ObjectToken::getInstance([
                    "iss"=>$this->config->getAppName(),
                    "iat"=>intval(date_format($now,"U")),
                    "exp"=>intval(date_format($expire,"U")),
                    "sub"=>"terraform"
                ])
            );
            $jwt=null;

            return new Result(200,["token"=>$token]);

        }else return new Result(401,["error"=>"Unauthenticated"]);           
    }

    public function authMigration(string $app, string $password):Result{
        if ($this->config->validateAppHash($app,$password)){
            $secondsToExpire = $this->config->getMigration()->expire;
            $secret = $this->config->getHash();
            $expire = date_create();
            $expire->add(new DateInterval("PT{$secondsToExpire}S"));
            $now = date_create();
            $jwt = HS256Jwt::getInstance($secret);
            $token = $jwt->getToken(
                ObjectToken::getInstance([
                    "alg"=>"HS256",
                    "typ"=>"JWT"
                ]),
                ObjectToken::getInstance([
                    "iss"=>$this->config->getAppName(),
                    "iat"=>intval(date_format($now,"U")),
                    "exp"=>intval(date_format($expire,"U")),
                    "sub"=>"migration"
                ])
            );
            $jwt=null;

            return new Result(200,["token"=>$token]);

        }else return new Result(401,["error"=>"Unauthenticated"]);           
    }    

    public function terraform(array $definitions):Result{
        $messages = [];
        foreach ($definitions as $definition)
        {
            $result = $this->migration->create($definition);
            array_push(
                $messages,
                $result->getInfo($result->assert(100)?"ok":"error")
            );
        }
        return new Result(201,["messages"=>$messages]);
    }

    public function migration(array $definitions):Result{
        $messages=[];
        foreach($definitions as $definition){
            $result = $this->migration->schemaAnalysis($definition);
            array_push(
                $messages,
                $result->getInfo($result->assert(100)?"ok":"error")
            );
        }
        return new Result(201,["messages"=>$messages]);
    }
}
?>