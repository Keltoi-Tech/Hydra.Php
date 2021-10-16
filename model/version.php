<?php
namespace model;
use hydra\{
    IEntity,
    ISerializable,
    Entity,
    Schema,
    Uuid
};
class Version extends Entity implements IEntity,ISerializable
{
    private $number;

    public function __construct(){
        parent::__construct([
            "number"=>new Schema("DECIMAL(9,2)",false,true,1.0,"Version number")
        ]);
    }

    public function add(){
        $this->number+=0.01;
    }

    public function newUid(){
        parent::newUid();
        $this->number=1.0;
    }

    public function getNumber():float{
        return $this->number;
    }

    public function getEntityName():string{
        return "Version";
    }

    public function serialize():array{
        $uuid = new Uuid($this->getUid());
        return [
            "uid"=>$uuid->toString(),
            "number"=>$this->number
        ];
    }
}
?>