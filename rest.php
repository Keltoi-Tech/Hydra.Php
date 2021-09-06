<?php
namespace api;
use net\Request;
use hydra\Result;

class Rest
{
    private $request;
    private $viewSet;
    private function __construct(Request $request,$viewSet)
    {
        $this->request = $request;
        $this->viewSet = $viewSet;
    }

    function __destruct(){
        $this->request= null;
        $this->viewSet = null;
    }

    public static function getInstance(Request $request,$viewSet){
        return new Rest($request,$viewSet);
    }

    private function setCall($action,$rawCallingMethod){
        $eachPart = explode('-',$rawCallingMethod);
        array_walk($eachPart,'arrayUcfirst');
        return $action.implode('',$eachPart);        
    }

    function post(){
        $param = $this->request->getOp();
        $data = $this->request->getStream();
        if (isset($param)){
            $method = $this->setCall("post",$param[0]);
            return method_exists($this->viewSet,$method)?
                        $this->viewSet->$method($data):
                        new Result(404,["error"=>"Route not found"]);
        }else
            return method_exists($this->viewSet,"create")?
                $this->viewSet->create($data):
                new Result(404,["error"=>"Route not found"]);
    }

    function put(){
        $guid = $this->request->getOp()[0];
        $data = $this->request->getStream();
        return
            method_exists($this->viewSet,'update')?
                (isset($guid))?
                    $this->viewSet->update($guid,$data):
                    $this->viewSet->update($data):
                new Result(404,["error"=>"Route not found"]);
    }

    function patch(){
        $param = $this->request->getOp();
        $data = $this->request->getStream();
        $hasData = isset($data);
        if (isset($param))
        {
            $method = $this->setCall("patch",$param[0]);
            if (method_exists($this->viewSet,$method)){
                $guid = isset($param[1])?$param[1]:null;
                if ($hasData)
                    return isset($guid)?
                                $this->viewSet->$method($guid,$data):
                                $this->viewSet->$method($data);
                else        
                    return isset($guid)?
                                $this->viewSet->$method($guid):
                                $this->viewSet->$method();
            }else new Result(404,["error"=>"Route not found"]); 
        }
        else return new Result(404,["error"=>"Route not found"]); 
    }

    function delete(){
        $param = $this->request->getOp();
        if (isset($param)){
            $guid = $param[0];
            return method_exists($this->viewSet,"delete")?
                        $this->viewSet->delete($guid):
                        new Result(404,["error"=>"Route not found"]);
        }else return new Result(400,["error"=>"Invalid format. No guid found"]);
    }

    function get(){
        $param = $this->request->getOp();
        $q = $this->request->getQueryString();

        if (isset($q))
        {
            $method = $this->setCall("list",$param[0]);
            return method_exists($this->viewSet,$method)?
                        $this->viewSet->$method($q):
                        new Result(404,["error"=>"Route not found"]);
        }
        else
        {
            if (isset($param))
            {
                $method = (count($param)==1)?"get":$this->setCall("get",$param[1]);
                return method_exists($this->viewSet,$method)?
                            $this->viewSet->$method($param[0]):
                            new Result(404,["error"=>"Route not found"]);
            }else return method_exists($this->viewSet,"list")?
                            $this->viewSet->list():
                            new Result(404,["error"=>"Route not found"]);
        }
    }

    public function run(){
        if ($this->viewSet->authorize()){
            $call = strtolower($this->request->getMethod());
            switch($call)
            {
                case "post":
                    return $this->post();break;
                case "put":
                    return $this->put();break;
                case "patch":
                    return $this->patch();break;
                case "delete":
                    return $this->delete();break;
                case "get":
                    return $this->get();break;
                default:
                    return new Result(405,["error"=>"HTTP method forbiden"]);
            }
        }else return $this->viewSet->getValid();
    }
}
?>