<?php
namespace hydra;

class Uuid
{
    private $data;

    public function __construct($data){
        $this->data=$data;
    }

    public static function raiseFromNew():Uuid{
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // set version to 0100
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // set bits 6-7 to 10
        return new Uuid($data);
    }

    public static function raiseFromString($string):Uuid{
        return new Uuid(hex2bin(str_replace("-","",$string)));
    }


    public function getData():string{
        return $this->data;
    }
    public function setData(string $data){
        $this->data = $data;
    }    

    public function toString():string{
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($this->data), 4));
    }
}
?>