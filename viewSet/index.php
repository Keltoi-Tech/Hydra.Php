<?php
use persistence\IProvider;
use hydra\IAuth;

class ViewSetFactory{
    public static function getInstance($entity,IProvider $provider, IAuth $auth=null){

        $viewSetFactory = "viewSet\\{$entity}ViewSet::getInstance";

        return $viewSetFactory($provider,$auth);
    }
}
?>