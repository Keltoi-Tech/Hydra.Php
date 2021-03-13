<?php
namespace api;
use concept\IRest;
use net\Request;
use persistence\Result;

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

    function post(){
        $associate = $this->request->getOp();
        $data = $this->request->getStream();
        return
            (isset($associate))?
                method_exists($this->viewSet,"associate")?
                    $this->viewSet->associate($data):
                    new Result(404,array("error"=>"Route not found")):
                $this->viewSet->post($data);
    }

    function put(){
        $guid = $this->request->getOp()[0];
        $data = $this->request->getStream();
        return
            (isset($guid))?
                $this->viewSet->put($guid,$data):
                new Result(400,array("error"=>"Invalid format. No guid found in url"));
    }

    function patch(){
        $param = $this->request->getOp();
        $data = $this->request->getStream();
        if (isset($param))
        {
            $call = $param[0];
            return  method_exists($this->viewSet,$call)?
                        isset($param[1])?
                            $this->viewSet->$call($param[1]):
                            $this->viewSet->$call($data):
                    new Result(404,array("error"=>"Route not found"));
        }
        else return new Result(400,array("error"=>"Invalid format.No route found"));
    }

    function delete(){
        $param = $this->request->getOp();
        if (isset($param)){
            $call = isset($param[1])?$param[1]:"delete";
            return method_exists($this->viewSet,$call)?
                        $this->viewSet->$call($param[0]):
                        new Result(404,array("error"=>"Route not found"));
        }else return new Result(400,array("error"=>"Invalid format. No guid found"));
    }

    function get(){
        $op = $this->request->getOp();
        $q = $this->request->getQueryString();

        if (isset($q))
        {
            $eachPart = explode('-',$op[0]);
            array_walk($eachPart,'arrayUcfirst');
            $call = "list".implode('',$eachPart);
            return method_exists($this->viewSet,$call)?
                        $this->viewSet->$call($q):
                        new Result(404,array("error"=>"Route not found"));
        }
        else
        {
            if (isset($op))
            {
                $call = (count($op)==1)?"get":"getBy".ucfirst($op[1]);
                return  $this->viewSet->$call($op[0]);
            }else return $this->viewSet->list();
        }
    }

    public function run(){
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
                return new Result(405,array("error"=>"HTTP method forbiden"));
		}
    }
}
?>