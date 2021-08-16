<?php
namespace hydra;
//RESULT ABSTRACTION
class Result {
    private $status;
    private $info;
    function __construct($status,$info)
    {
        $this->status = $status;
        $this->info = $info;
    }

    public function getStatus():int{
        return $this->status;
    }

    public function hasKey($field):bool{
        return array_key_exists($field,$this->info);
    }

    public function getInfo($field=null){
        return isset($field)?$this->info[$field]:$this->info;
    }

    public function assert(int $statusCode):bool{
        return $this->status===$statusCode;
    }

    public function notAssert(int $statusCode):bool{
        return !$this->assert($statusCode);
    }

    public function setInfoEntity($name,$val){
        $call = "set".ucfirst($name);
        $this->info->$call($val);
    }

    public static function getInstance($status,$info):Result{
        return new Result($status,$info);
    }
}
?>