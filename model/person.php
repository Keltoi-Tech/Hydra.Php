<?php
namespace model;
use hydra\{IOnthos,ISerializable,Entity,Result,TextSchema};
class Person extends Entity implements IOnthos,ISerializable
{
    private $name;
    private $lastName;

    public function __construct(){
        parent::__construct([
            "name"=>new TextSchema("VARCHAR(50)",false,true,null,null,"Persons name"),
            "lastName"=>new TextSchema("VARCHAR(50)",true,false,null,null,"Surname")
        ]);
    }

    public function getLastName():string{
        return $this->lastName;
    }
    public function setLastName(string $lastName){
        $this->lastName = $lastName;
    }

    public function getName():string{
        return $this->name;
    }

    public function setName(string $name){
        $this->name = $name;
    }

    public function getEntityName():string{
        return "Person";
    }

    public function serialize():array{
        return [
            "uid"=>$this->getUid(),
            "name"=>$this->getName()
        ];
    }
}
?>