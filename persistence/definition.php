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
    private $models;

    function __construct(IEntity $entity){
        $this->table = $entity->getEntityName();
        $structure = $entity->getDB();

        foreach($structure as $field=>$definition){
            $definition->setName($field);
        }

        $this->fields = $structure;
        $this->models = array_keys($this->fields);
    }

    public function getTable(){
        return $this->table;
    }

    public function alterAddConstraint(string $field):string{
        $f = $this->fields[$field];
        return ($f instanceof ForeignKeySchema)?
                    "alter table {$this->table} add {$f->constraint()}":
                    "";
    }

    public function alterAddColumn(string $field):string{
        return "alter table {$this->table} add {$this->fields[$field]->build()}";
    }

    public function alterModifyColumn(string $field):string{
        return "alter table {$this->table} modify {$this->fields[$field]->build()}";
    }

    public function alterDropColumn(string $name):string{
        return "alter table {$this->table} drop column {$name}";
    }

    public function matchDb(array $db):array{
        $toAdd = array_diff($this->models,$db);
        $toModify = array_intersect($this->models,$db);
        $toDrop = array_diff($db,$this->models);    
        return [
            "addColumns"=>array_map(array($this,'alterAddColumn'),$toAdd),
            "addConstraint"=>array_map(array($this,'alterAddConstraint'),$toAdd),
            "modifyColumns"=>array_map(array($this,'alterModifyColumn'),$toModify),
            "dropColumns"=>array_map(array($this,'alterDropColumn'),$toDrop)
        ];
    }

    public function create():string{
        $properties=[];
        $constraints=[];
        
        array_push($properties,"id int unsigned not null primary key auto_increment");
        array_push($properties,"uid char(36) not null collate latin1_swedish_ci unique");
        foreach($this->fields as $field){
            array_push($properties,$field->build());     
            if (get_class($field)=="ForeignKey")
                array_push($constraints,$field->constraint());
        }
        array_push($properties,"creationDate datetime not null default current_timestamp");
        array_push($properties,"updateDate datetime null on update current_timestamp");
        
        $p = implode(",",$properties);
        $c = empty($constraints)?"":"," . implode(",",$constraints);

        return "create table {$this->table}($p $c)";
    }

    public static function getInstance(IEntity $entity){
        return new Definition($entity);
    }
}
?>