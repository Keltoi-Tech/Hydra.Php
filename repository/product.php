<?php
namespace repository;
use persistence\{IProvider,Crud};

class ProductRepository extends Crud
{
    private function __construct(IProvider $provider){
        parent::__construct($provider);
    }

    public static function getInstance(IProvider $provider){
        return new ProductRepository($provider);
    }
}
?>