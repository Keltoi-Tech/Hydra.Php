<?php
    namespace auth;
    use concept\IAuth;

    class Auth implements IAuth{
        private $auth;
        private $authType;

        private function __construct($auth){
            $parts = explode(' ',$auth);
            $this->authType = $parts[0];
            $this->auth = $parts[1];
        }

        public static function getInstance($auth){
            return new Auth($auth);
        }

        public function getType():string{
            return $this->authType;
        }

        public function getAuth():string{
            return $this->auth;
        }
    }
?>