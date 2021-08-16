<?php
namespace persistence;
use concept\IEntity;
use PDO;

class Definition{
    private $table;
    private $fields;

    public function __construct(IEntity $entity){
        $this->table = $entity->getEntityName();
        $structure = $entity->getDB();

        foreach($structure as $field=>$definition){
            $definition->setName($field);
        }

        $this->fields = $definition;
    }

    public function alterAddConstraint(string $property):string{
        return "alter table {$this->table} add {$fields[$property]->constraint()}";
    }

    public function alterAddColumn(string $property):string{
        return "alter table {$this->table} add {$fields[$property]->build()}";
    }

    public function alterDropColumns(string $property):string{
        return "alter table {$this->table} drop column {$fields[$property]->getName()}";
    }

    public function create():string{
        $properties=[];
        $constraints=[];
        
        foreach($this->fields as $field){
            array_push($properties,$field->build());     
            if (get_class($field)=="ForeignKey")
                array_push($constraints,$field->constraint());
        }

        $f = implode(",",$properties) . "," . implode(",",$constraints);

        return "create table {$this->table}($f)";
    }
}
?>