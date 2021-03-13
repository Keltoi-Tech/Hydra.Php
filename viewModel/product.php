<?php
namespace viewModel;
use persistence\ViewModel;
use model\Product;

class ProductViewModel extends ViewModel{
    private function __construct(){

    }

    public static function getInstance(){
        return new ProductViewModel();
    }

    public function getModelInstance($entry=null,$itself=true){
        return isset($entry)?$this->fill(new Product(),$entry,$itself):new Product();
    }
}

?>