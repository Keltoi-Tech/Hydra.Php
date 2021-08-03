<?php
namespace hydra;
//ENTITY BASE CLASS
abstract class Entity{
    protected $id;
    protected $uid;
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
    public function setUid(string $uid){
        $this->uid = $uid;
    }       

    public function getDB():array{
        return $this->db;
    }

    public function getProperties():array{
        return array_keys($this->db);
    }
}
?>