<?php
include_once("product.php");
include_once("supply.php");
use persistence\IProvider;

class ViewSetFactory{
    public static function getInstance(IProvider $provider, $entity){

        $viewSetFactory = "viewSet\\{$entity}ViewSet::getInstance";

        return $viewSetFactory($provider);
    }
}
?>