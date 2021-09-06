<?php
namespace repository;
use persistence\{IProvider,IDefinition,Crud,Migration};
use hydra\{Result};
use PDO;
use DateInterval;

class MigrationRepository extends Crud
{
    private $migration;
    private function __construct(IProvider $provider){
        parent::__construct($provider);
        $this->migration = new Migration($provider);
    }

    public static function getInstance(IProvider $provider){
        return new MigrationRepository($provider);
    }

    public function authTerraform():Result{
        return $this->migration->request("terraform");
    }

    public function terraform(...$definitions):Result{
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
}
?>