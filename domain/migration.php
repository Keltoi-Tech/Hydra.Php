<?php
namespace domain;
use hydra\{Result};
use persistence\Migration;

class MigrationDomain
{
    private $migration;

    public function __construct(Migration $migration)
    {
        $this->migration = $migration;
    }

    public function terraform(array $definitions):Result
    {
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
    
    public function migration(array $definitions):Result
    {
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