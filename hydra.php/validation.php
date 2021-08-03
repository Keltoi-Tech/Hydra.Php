<?php
use hydra\{Result,IViewModel};
abstract class Validation{
    protected $entry;
    protected $viewModel;

    protected function __construct(array $entry=null, IViewModel $viewModel){
        $this->entry = $entry;
        $this->viewModel= $viewModel;
    }

    protected function validate_uid(){
        return (strlen($this->entry["uid"])===36)?
                    new Result(100,null):
                    new Result(400,["error"=>"Invalid guid format"]);
    }

    protected function validate_id(){
        return ($this->entry["id"]>0)?
            new Result(100,null):
            new Result(400,["error"=>"Invalid id format"]);
    }

    public function run(...$fields):Result
    {
        $var_model=[];
        foreach($fields as $field){
            $method= "validate_{$field}";
            $assert100 = $this->$method();
            if (!$assert100->assert(100))return $assert100;
            else $var_model[$field] = $this->entry[$field];
        }

        return new Result(100,[
            "model"=>isset($fields)?
                $this->viewModel->toModel(false,$var_model):
                $this->viewModel->toModel(true,null)
        ]);
    }
}
?>
