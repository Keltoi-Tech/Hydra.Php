<?php
namespace hydra;
use hydra\Result;

abstract class ViewSet{
    protected $valid;

    protected function __construct(Result $valid)
    {
        $this->valid= $valid;
    }

    protected function __destruct()
    {
        $this->valid = null;
    }

    public function getValid():Result
    {
        return $this->valid;
    }

    protected function verifyFeatureRole($feature,$role):Result{
        if ($this->valid->hasKey("feature")){
            $payload = $this->valid->getInfo("feature");
            return (
                array_key_exists($feature,$payload)?
                    (
                        in_array($role,$payload[$feature])?
                            new Result(100,null):
                            new Result(403,["error"=>"Role forbidden for provided token"])
                    ):
                    new Result(403,["error"=>"Profile forbidden for provided token"])
            );
        }else return new Result(400,["error"=>"Invalid token"]);
    }

    protected function getPayload($field=null)
    {
        return $this->valid->hasKey($field)?
                    $this->valid->getInfo($field):
                    false;
    }

    public function authorize():Result
    {
        return $this->valid->assert(100)?
                new Result(100,null):
                new Result(403,["error"=>"Forbidden"]);
    }  
}?>