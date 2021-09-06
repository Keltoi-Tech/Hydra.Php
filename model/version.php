<?php
namespace model;
use hydra\{IEntity,ISerializable,Entity,Result,Schema};
class Version extends Entity implements IEntity,ISerializable
{
    private $number;

    public function __construct(){
        parent::__construct([
            "number"=>new Schema("DECIMAL(9,2)",false,true,1.0)
        ]);
    }

    public function getEntityName():string{
        return "Version";
    }

    public function serialize():array{
        return [
            "uid"=>$this->getUid(),
            "number"=>$this->number
        ];
    }
}
?>