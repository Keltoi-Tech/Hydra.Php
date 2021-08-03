<?php
namespace hydra;
use hydra\IEntity;

interface IViewModel{
    public function toModel(bool $itself=true,?array $entry);
}

abstract class ViewModel implements IViewModel
{
    protected $name;
    protected function __construct($name)
    {
        $this->name = $name;
    }

    public static function fillModelWithUid(IEntity $entity,string $uid)
    {
        $entity->setUid($uid);
        return $entity;
    }

    public static function fillModelWithFields(IEntity $entity,array $entry)
    {
        foreach($entry as $prop=>$val){
            $method = "set".ucfirst($prop);
            $entity->$method($val);
        }
        return $entity;
    }

    public static function fillModelWithProperties(IEntity $entity,array $entry)
    {
        foreach($entity->getProperties() as $prop){
            $method = "set".ucfirst($prop);
            $entity->$method($entry[$prop]);
        }
        return $entity;
    }

    protected function fill(IEntity $entity,array $entry,bool $itself=true)
    {
        return $itself?
            self::fillModelWithProperties($entity,$entry):
            self::fillModelWithFields($entity,$entry);
    }


    public function toModel(bool $itself=true,?array $entry)
    {
        $entity = new $this->name();
        return isset($entry)?$this->fill($entity,$entry,$itself):$entity;
    }
}

?>