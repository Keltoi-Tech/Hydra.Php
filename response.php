<?php
    namespace net;
    use persistence\Result;
    use concept\ISerializable;
    use JsonSerializable;

    class Response implements JsonSerializable
    {
        private $body;
        private $status;

        private function __construct(Result $result){
            http_response_code($result->getStatus());
            $info = $result->getInfo();
            $this->body = ($info instanceof ISerializable)?
                        $info->serialize():
                        $info;
        }

        public static function getInstance(Result $result){
            return new Response($result);
        }

        public function jsonSerialize(){
            return $this->body;
        }
    }
?>