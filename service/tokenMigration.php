<?php
namespace service;
include_once("token.php");
use token\{HS256Jwt,ObjectToken};
use hydra\{Result,IConfig};
use DateInterval;

class TokenMigration extends Token
{
    public function __construct(IConfig $config)
    {
        parent::__construct($config);
    }

    public function raiseTerraformToken(string $app, string $password):Result
    {
        if ($this->config->validateAppHash($app,$password))
        {
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

    public function raiseMigrationToken(string $app, string $password):Result
    {
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
}
?>