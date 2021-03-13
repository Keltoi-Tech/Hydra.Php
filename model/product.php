<?php
namespace model;
use concept\{IOnthos,ISerializable};

class Product implements IOnthos, ISerializable
{
    private $id;
    private $uid;
    private $name;

	public function setId($id){
        $this->id = $id;
    }
	public function getId(){
        return $this->id;
    }

    public function getUid(){
        return $this->uid;
    }
    public function setUid($uid){
        $this->uid= $uid;
    }

    public function setName($name){
        $this->name = $name;
    }
	public function getName(){
        return $this->name;
    }

    public function getEntityName(){
        return "Product";
    }

    public function getProperties(){
        return array("name");
    }

    public function serialize(){
        return array(
            "uid"=>$this->uid,
            "name"=>$this->name
        );
    }

    public static function getClassName(){
        return 'Product';
    }
}


?>