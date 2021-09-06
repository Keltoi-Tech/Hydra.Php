<?php
namespace persistence;
use hydra\IEntity;

interface IDefinition{
    public function alterAddConstraint(string $property):string;
    public function alterAddColumn(string $property):string;
    public function alterDropColumn(string $property):string;
    public function alterModifyColumn(string $property):string;
    public function create():string;
}

class Definition implements IDefinition{
    private $table;
    private $fields;

    function __construct(IEntity $entity){
        $this->table = $entity->getEntityName();
        $structure = $entity->getDB();
        $provider = $this->provider;

        foreach($structure as $field=>$definition){
            $definition->setName($field);
        }

        $this->fields = $definition;
    }

    public function getTable(){
        return $this->table;
    }

    public function alterAddConstraint(string $property):string{
        $pdo->exec("alter table {$this->table} add {$fields[$property]->constraint()}");
    }

    public function alterAddColumn(string $property):string{
        return "alter table {$this->table} add {$fields[$property]->build()}";
    }

    public function alterModifyColumn(string $property):string{
        return "alter table {$this->table} modify {$fields[$property]->build()}";
    }

    public function alterDropColumn(string $property):string{
        return "alter table {$this->table} drop column {$fields[$property]->getName()}";
    }

    public function create():string{
        $properties=[];
        $constraints=[];
        
        array_push($properties,"id int not null primary auto_increment");
        array_push($properties,"uid char(36) not null collate latin1_swedish_ci unique");
        foreach($this->fields as $field){
            array_push($properties,$field->build());     
            if (get_class($field)=="ForeignKey")
                array_push($constraints,$field->constraint());
        }
        array_push($properties,"creationDate datetime not null default current_timestamp");
        array_push($properties,"updateDate datetime null on update current_timestamp");
        
        $f = implode(",",$properties) . "," . implode(",",$constraints);

        return "create table {$this->table}($f)";
    }

    public static function getInstance(IEntity $entity){
        return new Definition($entity);
    }
}
?>