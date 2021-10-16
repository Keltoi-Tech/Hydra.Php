<?php
namespace persistence;
use hydra\IEntity;

class Joining implements IDefinition{
    private $query;    
    private $joiningName;

    function __construct(IEntity $entity, IEntity $join){
        $entityName = $entity->getEntityName();
        $joinName = $join->getEntityName();

        $joiningName="{$entityName}{$joinName}";
        $this->query = "Create Table {$joiningName}( "
        ."id{$entityName} int unsigned not null,"
        ."id{$joinName} int unsigned not null,"
        ."primary key (id{$entityName},id{$joinName}),"
        ."constraint foreign key fk{$entityName}{$joinName}(id{$entityName}) "
        ."references {$entityName}(id),"
        ."constraint foreign key fk{$joinName}{$entityName}(id{$joinName}) "
        ."references {$joinName}(id)"
        .")";

        $this->joiningName = $joiningName;
    }

    public function matchDatabase(array $db):array{
        return [];
    }

    public function getTable():string{
        return $this->joiningName;
    }

    public function create():string{
        return $this->query;
    }

    public static function getInstance(IEntity $entity,IEntity $join){
        return new Joining($entity,$join);
    }
}
?>