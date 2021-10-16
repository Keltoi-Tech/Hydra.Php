<?php
namespace viewSet;
use persistence\IProvider;
use hydra\{IAuth,IConfig};
class ViewSetFactory{
    public static function getInstance(
        IConfig $config,
        IProvider $provider, 
        IAuth $auth=null
    ){
        if ($config->getMigration())include_once("migration.php");

        $entity = $config->getEntity();

        $viewSetFactory = "viewSet\\{$entity}ViewSet::getInstance";

        return $viewSetFactory($config,$provider,$auth);
    }
}
?>