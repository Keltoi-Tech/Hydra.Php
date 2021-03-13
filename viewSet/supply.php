<?php
namespace viewSet;
include_once("viewModel\product.php");
include_once("viewModel\supply.php");
include_once("repository\product.php");
include_once("repository\supply.php");

use viewModel\{ProductViewModel,SupplyViewModel};
use repository\{ProductRepository,SupplyRepository};
use persistence\{IProvider,Result};


class SupplyViewSet
{
    private $supplyRepository;
    private $productRepository;

    private function __construct(
        ProductRepository $productRepository,
        SupplyRepository $supplyRepository
    )
    {
        $this->productRepository = $productRepository;
        $this->supplyRepository = $supplyRepository;
    }

    function __destruct(){
        $this->productRepository = null;
        $this->supplyRepository=null;
    }

    public static function getInstance(IProvider $provider){
        return new SupplyViewSet(
            ProductRepository::getInstance($provider),
            SupplyRepository::getInstance($provider)
        );
    }

    public function read($uid){
        $viewModel = SupplyViewModel::getInstance();
        $model = $viewModel->getModelInstance(array("uid"=>$uid));
        $result = $this->supplyRepository->read($model);
        $model=null;
        $viewModel=null;
        return $result;
    }

    private function readProduct($uid){
        $viewModel = ProductViewModel::getInstance();
        $model = $viewModel->getModelInstance(array("uid"=>$uid));
        $result = $this->productRepository->read($model);
        $model=null;
        $viewModel=null;
        return $result;
    }

    public function post($entry)
    {
        $product = $this->readProduct($entry["uidProduct"]);
        if ($product->getStatus()==200){
            $productViewModel = ProductViewModel::getInstance();
            $productModel = $productViewModel->getModelInstance($product->getInfo(),false);
            

            $viewModel = SupplyViewModel::getInstance();
            $model = $viewModel->getModelInstance(array(
                "valuation" => $entry["valuation"],
                "name" => $entry["name"],
                "product" => $productModel
            ),false);
            $result = $this->supplyRepository->insert($model);

            $productViewModel=null;
            $productModel=null;
            $viewModel=null;
            $model=null;
            return $result;
        }else return new Result(404,array("error"=>"Product not found"));
    }

    public function put($uid,$entry)
    {
        $result = $this->read($uid);
        if ($result->getStatus()==200)
        {
            $id = $result->getInfo()["id"];
            $product = $this->readProduct($entry["uidProduct"]);
            if ($product->getStatus()==200){
                $productViewModel = ProductViewModel::getInstance();
                $productModel = $productViewModel->getModelInstance($product->getInfo(),false);

                $viewModel = SupplyViewModel::getInstance();
                $model = $viewModel->getModelInstance(array(
                    "id"=>$id,
                    "name"=>$entry["name"],
                    "valuation"=>$entry["valuation"],
                    "product"=>$productModel
                ),false);
                $result = $this->supplyRepository->update($model);

                $productViewModel=null;
                $productModel=null;
                $viewModel=null;
                $model=null;
            }else return new Result(404,array("error"=>"Product not found"));
        }
        
        return $result;
    }
    
    public function disable($uid)
    {
        $result = $this->read($uid);
        if ($result->getStatus()==200)
        {
            $viewModel = SupplyViewModel::getInstance();
            $model = $viewModel->getModelInstance($result->getInfo(),false);
            $result= $this->supplyRepository->disable($model);

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
            $viewModel = SupplyViewModel::getInstance();
            $model = $viewModel->getModelInstance($result->getInfo(),false);
            $result= $this->supplyRepository->enable($model);

            $model=null;
            $viewModel=null;
        }
        return $result;
    }

    public function get($uid){
        $result = $this->read($uid);
        if ($result->getStatus()==200){
            $viewModel = SupplyViewModel::getInstance();
            $productViewModel = ProductViewModel::getInstance();

            $model = $viewModel->getModelInstance($result->getInfo(),false);
            $result = $this->supplyRepository->get($model);
            
            $productModel = $productViewModel->getModelInstance(
                array("id"=>$result->getInfo()->getIdProduct())
            ,false);
            $product = $this->productRepository->get($productModel)->getInfo();
            $result->setInfoEntity("product",$product);

            $model=null;
            $viewModel=null;
        }
        return $result;
    }

    public function list(){
        
        $viewModel = SupplyViewModel::getInstance();
        $model = $viewModel->getModelInstance();
        $result = $this->supplyRepository->list($model,"name,valuation");
        $viewModel = null;
        $model=null;

        return $result;
    }

    public function listByName($param){
        $viewModel = SupplyViewModel::getInstance();
        $model = $viewModel->getModelInstance($param,false);
        $result = $this->supplyRepository->nameLike($model,"name,valuation");
        $viewModel = null;
        $model=null;

        return $result;
    }
}
?>