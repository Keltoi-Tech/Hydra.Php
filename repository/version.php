<?php
namespace repository;
include_once("contract/version.php");
use hydra\Result;
use model\Version;
use persistence\{IProvider,Crud};
use repository\contract\IVersionRepository;

class VersionRepository extends Crud implements IVersionRepository
{
    private function __construct(IProvider $provider){
        parent::__construct($provider);
    }

    public static function getInstance(IProvider $provider){
        return new VersionRepository($provider);
    }

    public function begin():Result{
        $version = new Version();
        $version->newUid();
        $result = parent::insert($version);
        return $result->assert(100)?
                    Result::getInstance(201,$version):
                    $result;
    }

    public function exists(int $id):Version{
        $version = new Version();
        $version->setId($id);
        $exists = parent::get($version);
        return $exists->assert(100)?$version:null;
    }    

    public function increment(Version $version):Result
    {
        $version->add();
        return parent::update($version);
    }
}
?>  