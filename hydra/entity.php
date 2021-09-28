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
    
    public function getUuid():Uuid{
        return $this->uid;
    }
    public function setUuid(Uuid $uuid){
        $this->uid = $uuid;
    }

    public function getUid():string{
        return $this->uid->getData();
    }
    public function setUid(string $uid){
        $this->uid = new Uuid($uid);
    }
    public function newUid(){
        $this->uid = Uuid::raiseFromNew();
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