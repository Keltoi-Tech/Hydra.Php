<?php
use persistence\IProvider;
use concept\IAuth;

class ViewSetFactory{
    public static function getInstance($entity,IProvider $provider, IAuth $auth=null){

        $viewSetFactory = "viewSet\\{$entity}ViewSet::getInstance";

        return $viewSetFactory($provider,$auth);
    }
}
?>