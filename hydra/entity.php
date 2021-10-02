<?php
namespace hydra;
//ENTITY BASE CLASS
abstract class Entity{
    protected $id;
    protected $uid;
    protected $creationDate;
    protected $updateDate;
    protected $db;

    protected function __construct(array $db){
        $this->db = $db;
    }

    public function getId():int{
        return $this->id;
    }
    public function setId(int $id){
        $this->id = $id;
    }

    public function getUid():string{
        return $this->uid;
    }
    public function setUid(Uuid $uid){
        $this->uid = $uid->getData();
    }
    public function newUid(){
        $this->uid = Uuid::raiseFromNew()->getData();
    }
    
    public function creationDate():DateTime{
        return $this->creationDate;
    }
    
    public function updateDate():DateTime{
        return $this->updateDate;
    }

    public function getDB():array{
        return $this->db;
    }

    public function getProperties():array{
        return array_keys($this->db);
    }
}
?>