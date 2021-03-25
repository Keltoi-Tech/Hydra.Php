<?php
    namespace net;
    use auth\Auth;

    class Request
    {
        private $queryString;
        private $version;
        private $entity;
        private $method;
        private $stream;
        private $auth;
        private $op;

        function __destruct(){
        }

        private function __construct(){
            $initialArray = explode('/',$_SERVER['REQUEST_URI']);

            $this->version = $initialArray[1];
            $this->entity = ucfirst($initialArray[2]);
            $this->method = strtolower($_SERVER['REQUEST_METHOD']);
            $this->stream = json_decode(file_get_contents("php://input"),true);
            $this->queryString = (count($_REQUEST)>0)?$_REQUEST:null;

            if (count($initialArray)>3)$this->op = array_slice($initialArray,3);
            if (!empty($_SERVER["HTTP_AUTHORIZATION"]))$this->auth = Auth::getInstance($_SERVER["HTTP_AUTHORIZATION"]);
        }

        public function getOp():?array{
            return $this->op;
        }

        public function getVersion():string{
            return $this->version;
        }

        public function getEntity():string{
            return $this->entity;
        }

        public function getMethod():string{
            return $this->method;
        }

        public function getStream():?array{
            return $this->stream;
        }

        public function getAuth():?Auth{
            return $this->auth;
        }

        public function getQueryString():?array{
            return $this->queryString;
        }

        public function queryString($name){
            return $this->queryString[$name];
        }

        public static function getInstance($headers=null):Request{
            header('Content-Type: application/json; charset=utf-8');
            if (isset($headers)){
                foreach($headers as $header){
                    header($header);
                }
            }

            return new Request();
        }
    }
?>