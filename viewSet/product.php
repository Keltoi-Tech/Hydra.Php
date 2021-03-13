<?php
namespace viewSet;
include_once("viewModel\product.php");
include_once("repository\product.php");

use viewModel\ProductViewModel;
use repository\ProductRepository;
use persistence\{IProvider,Result};


class ProductViewSet
{
    private $repository;

    private function __construct(ProductRepository $productRepository)
    {
        $this->repository = $productRepository;
    }

    function __destruct(){
        $this->repository = null;
    }

    public static function getInstance(IProvider $provider){
        return new ProductViewSet(ProductRepository::getInstance($provider));
    }

    public function read($uid){
        $viewModel = ProductViewModel::getInstance();
        $model = $viewModel->getModelInstance(array("uid"=>$uid));
        $result = $this->repository->read($model);
        $model=null;
        $viewModel=null;
        return $result;
    }

    public function post($entry)
    {
        $viewModel = ProductViewModel::getInstance();
        $model = $viewModel->getModelInstance($entry);
        $result = $this->repository->insert($model);
        $viewModel=null;
        $model=null;
        return $result;
    }

    public function put($uid,$entry)
    {
        $result = $this->read($uid);
        if ($result->getStatus()==200)
        {
            $viewModel = ProductViewModel::getInstance();
            $model = $viewModel->getModelInstance($entry);
            $model->setId($result->getInfo()["id"]);
            $result = $this->repository->update($model);
            $viewModel=null;
            $model=null;
        }
        
        return $result;
    }
    
    public function disable($uid)
    {
        $result = $this->read($uid);
        if ($result->getStatus()==200)
        {
            $viewModel = ProductViewModel::getInstance();
            $model = $viewModel->getModelInstance($result->getInfo(),false);
            $result= $this->repository->disable($model);

            $model=null;
            $viewModel=null;
        }
        return $result;
    }

    public function enable($uid)
    {
        $result = $this->read($uid);
        if ($result->getStatus()==200)
        {
            $viewModel = ProductViewModel::getInstance();
            $model = $viewModel->getModelInstance($result->getInfo(),false);
            $result= $this->repository->enable($model);

            $model=null;
            $viewModel=null;
        }
        return $result;
    }

    public function get($uid){
        $result = $this->read($uid);
        if ($result->getStatus()==200){
            $viewModel = ProductViewModel::getInstance();
            $model = $viewModel->getModelInstance($result->getInfo(),false);
            $result = $this->repository->get($model);            
            $model=null;
            $viewModel=null;
        }
        return $result;
    }

    public function list(){
        
        $viewModel = ProductViewModel::getInstance();
        $model = $viewModel->getModelInstance();
        $result = $this->repository->list($model,"name");
        $viewModel = null;
        $model=null;

        return $result;
    }

    public function listByName($param){
        $viewModel = ProductViewModel::getInstance();
        $model = $viewModel->getModelInstance($param);
        $result = $this->repository->nameLike($model,"name");
        $viewModel = null;
        $model=null;

        return $result;
    }
}
?>