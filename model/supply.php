<?php
namespace model;
use concept\{IOnthos,ISerializable};

class Supply implements IOnthos, ISerializable
{
    private $id;
    private $uid;
    private $name;
    private $valuation;
    private $idProduct;
    private $product;    

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

    public function setValuation($valuation){
        $this->valuation = $valuation;
    }
    public function getValuation(){
        return $this->valuation;
    }

    public function setProduct(Product $product){
        $this->product = $product;
    }
    public function getIdProduct(){
        return isset($this->product)?$this->product->getId():$this->idProduct;
    }    

    public function getEntityName(){
        return "Supply";
    }

    public function getProperties(){
        return array("name","valuation","idProduct");
    }

    public function serialize(){
        return array(
            "uid"=>$this->uid,
            "name"=>$this->name,
            "valuation"=>$this->valuation,
            "product"=>isset($this->product)?$this->product->serialize():null
        );
    }
}
?>