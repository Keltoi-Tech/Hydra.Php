<?php
namespace hydra;
class Field 
{
    private $name;
    private $type;
    private $nullable;
    private $unique;
    private $collate;
    private $default;

    public function __construct(
        string $type,
        bool $nullable=true,
        bool $unique=false,
        $default=null,
        string $collate=""
    ) {
        $this->type = $type;
        $this->nullable = $nullable;
        $this->unique = $unique;
        $this->collate = $collate;
        $this->default = $default;
    }

    public function getName(){
        return $this->name;
    }
    public function setName($name){
        $this->name = $name;
    }

    public function build():string{
        $null = $this->nullable?"NULL":"NOT NULL";
        $uq = $this->unique?"UNIQUE":"";
        $default = $this->nullable?"":
                        isset($this->default)?
                            "DEFAULT {$this->default}":
                            "";
        $collate = $this->collate==""?"":"COLLATE {$this->collate}";
        return "{$this->name} {$this->type} {$null} {$collate} {$default} {$uq}";
    }
}

class ForeignKey extends Field
{
    public function __construct(bool $nullable)
    {
        parent::__construct("INT UNSIGNED",$nullable);
    }

    public function constraint():string{
        $table = trim($this->name,"id")
        return "CONSTRAINT FOREIGN KEY({$this->name}) REFERENCES {$table}(id)";
    }
}
?>