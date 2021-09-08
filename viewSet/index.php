<?php
namespace viewSet;
include_once("migration.php");
use persistence\IProvider;
use hydra\{IAuth,IConfig};

class ViewSetFactory{
    public static function getInstance(
        IConfig $config,
        IProvider $provider, 
        IAuth $auth=null
    ){
        $entity = $config->getEntity();

        $viewSetFactory = "viewSet\\{$entity}ViewSet::getInstance";

        return $viewSetFactory($config,$provider,$auth);
    }
}
?>