<?php
namespace viewModel;
use persistence\ViewModel;
use model\Supply;

class SupplyViewModel extends ViewModel{
    private function __construct(){

    }

    public static function getInstance(){
        return new SupplyViewModel();
    }

    public function getModelInstance($entry=null,$itself=true){
        return isset($entry)?$this->fill(new Supply(),$entry,$itself):new Supply();
    }
}

?>