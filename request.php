<?php
    namespace net;

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
            if (isset($_SERVER["HTTP_AUTHORIZATION"]))$this->auth = $_SERVER["HTTP_AUTHORIZATION"];
        }

        public function getOp(){
            return $this->op;
        }

        public function getVersion(){
            return $this->version;
        }

        public function getEntity(){
            return $this->entity;
        }

        public function getMethod(){
            return $this->method;
        }

        public function getStream(){
            return $this->stream;
        }

        public function getAuth(){
            return $this->auth;
        }

        public function getQueryString(){
            return $this->queryString;
        }

        public function queryString($name){
            return $this->queryString[$name];
        }

        public static function getInstance($headers=null){
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