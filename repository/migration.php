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

    public function terraform(...$defintions):Result{
        $messages = [];
        foreach ($definitions as $definition)
        {
            $result = $this->migration->create($defintion);
            array_push(
                $messages,
                $result->getInfo($result->assert(100)?"ok":"error")
            );
        }
        return new Result(201,["messages"=>$messages]);
    }
}
?>