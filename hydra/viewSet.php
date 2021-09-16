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

    protected function verifyKey($key){
        $payload = $this->valid->getInfo();
        return array_key_exists($key,$payload)?
                new Result(100,
                    ["ok"=>"continue"]):
                new Result(403,
                    ["error"=>"Provided credentials has no association with provided app"]);
    }

    protected function getPayload($field=null)
    {
        return $this->valid->getInfo($field);
    }

    public function authorize()
    {
        return $this->valid->assert(100);
    }  
}?>