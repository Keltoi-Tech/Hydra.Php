<?php
namespace hydra;
use hydra\{Result,Uuid};
abstract class Validation{
    protected $entry;

    protected function __construct(array $entry){
        $this->entry = $entry;
    }

    public function validateUid():Result{
        return (
            isset($this->entry["uid"])?
                (
                    strlen($this->entry["uid"])===36?
                        new Result(100,null):
                        new Result(400,["error"=>"Invalid uuid format for uid"])
                ):
                new Result(400,["error"=>"No uid provided"])
        );
    }

    public function validateId():Result{
        return (
            isset($this->entry["id"])?
                (
                    $this->entry["id"]>0?
                        new Result(100,null):
                        new Result(400,["error"=>"Invalid id format"])
                ):
                new Result(400,["error"=>"No id provided"])
        );
    }
}
?>
