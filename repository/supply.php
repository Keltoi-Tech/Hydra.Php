<?php
namespace repository;
use persistence\{IProvider,Crud};

class SupplyRepository extends Crud
{
    private function __construct(IProvider $provider){
            parent::__construct($provider);
    }

    public static function getInstance(IProvider $provider){
        return new SupplyRepository($provider);
    }
}
?>