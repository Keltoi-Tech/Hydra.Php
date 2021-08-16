<?php
namespace model;
use hydra\{IEntity,ISerializable,Entity,Result,Field};
class Version extends Entity implements IEntity,ISerializable
{
    private $number;

    public function __construct(){
        parent::__construct([
            "number"=>new Field("DECIMAL(9,2)",false,true,1.0)
        ]);
    }
}
?>