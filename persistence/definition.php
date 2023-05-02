<?php
namespace persistence;
use hydra\{
    IEntity,
    ForeignKeySchema
};
interface IDefinition{
    public function create():string;
    public function getTable():string;
    public function matchDatabase(array $db):array;
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

    public function getTable():string{
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

    public function matchDatabase(array $db):array{
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
        array_push($properties,"uid char(16) not null unique");
        foreach($this->fields as $field){
            array_push($properties,$field->build());
            if (get_class($field)=="hydra\ForeignKeySchema")
                array_push($constraints,$field->constraint());
        }
        array_push($properties,"creationDate datetime not null default current_timestamp");
        array_push($properties,"updateDate datetime null on update current_timestamp");
        array_push($properties,"active bit not null default 1");
        
        $p = implode(",",$properties);
        $c = empty($constraints)?"":"," . implode(",",$constraints);

        return "create table {$this->table}($p $c)";
    }

    public static function getInstance(IEntity $entity){
        return new Definition($entity);
    }
}
?>