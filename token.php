<?php
namespace token;
use persistence\Result;

function base64url_encode($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}
  
function base64url_decode($data) {
    return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT));
} 

class ObjectToken{
    private $object;
    private $json;

    private function __construct($param){
        if (is_array($param)){
            $this->object = $param;
            $this->json = json_encode($param);
        }
        else
        {
            $this->json = base64url_decode($param);
            $this->object = json_decode($this->json,true);
        }
    }

    public static function getInstance($param){
        return new ObjectToken($param);
    }

    public function getObject(){
        return $this->object;
    }

    public function toBase64Encode(){
        return base64url_encode($this->json);
    }
}

class Token{
    private $splitToken;

    function __construct($token){
        $this->splitToken = explode('.',$token);
    }

    public static function getInstance($token){
        return new Token($token);
    }

    public function getHeader(){
        return ObjectToken::getInstance($this->splitToken[0]);
    }

    public function getPayload(){
        return ObjectToken::getInstance($this->splitToken[1]);
    }

    public function getSignature(){
        return array_slice($this->splitToken,2);
    }
}

abstract class Jwt{
    protected $secret;
    protected function __construct(string $secret){
        $this->secret = $secret;
    }

    protected abstract function validate_header(ObjectToken $header):Result;
    protected abstract function validate_payload(ObjectToken $payload):Result;
    protected abstract function get_hash(ObjectToken $header,ObjectToken $payload):string;

    public function getToken(ObjectToken $header,ObjectToken $payload){
        $hash = $this->get_hash($header,$payload);
        
        return "{$header->toBase64Encode()}.{$payload->toBase64Encode()}.{$hash}";
    }

    public function sign(ObjectToken $header, ObjectToken $payload, $signature){
        $headerValidation = $this->validate_header($header);
        $payloadValidation = $this->validate_payload($payload);

        return 
            ($headerValidation->assert(100))?
                ($payloadValidation->assert(100))?
                    ($this->get_hash($header,$payload)===$signature)?
                        new Result(100,$payload->getObject()):
                        new Result(401,["error"=>"Unauthorized"])
                    :
                    $payloadValidation
                :
                $headerValidation;
    }
}

class HS256Jwt extends Jwt{
    private function __construct($secret){
        parent::__construct($secret);
    }

    public static function getInstance($secret){
        return new HS256Jwt($secret);
    }

    public static function validate($jwt,$secret){
        $token = Token::getInstance($jwt);
        $header = $token->getHeader();
        $payload = $token->getPayload();
        $signature = $token->getSignature()[0];
        $jwt = self::getInstance($secret);
        $sign = $jwt->sign($header,$payload,$signature);

        $token = null;
        $header=null;
        $payload=null;
        $jwt=null;

        return $sign;
    }

    protected function validate_header(ObjectToken $header):Result{
        return ($header->getObject()["alg"]=="HS256")?
                    new Result(100,["HS256Jwt"=>"validate_header"]):
                    new Result(400,["error"=>"Algorithm type not match"]);
    }

    protected function validate_payload(ObjectToken $payload):Result{
        $p = $payload->getObject();
        $now = intval(date_format(date_create(),"U"));

        if (isset($p["nbf"]))
            if ($p["nbf"]>$now)return new Result(401,["error"=>"You can't access this token yet"]);
        
        if (isset($p["exp"]))
            if ($now>$p["exp"]) return new Result(401,["error"=>"Token expired"]);

        return new Result(100,["HS256Jwt"=>"validate_payload"]);
    }

    protected function get_hash(ObjectToken $header,ObjectToken $payload):string{
        $hash = $header->toBase64Encode() . $payload->toBase64Encode();
        return base64url_encode(hash_hmac('sha256',$hash,$this->secret,true));
    }
}
?>