<?php
namespace hydra;
class Schema
{
    protected $name;
    protected $type;
    protected $nullable;
    protected $unique;
    protected $default;

    public function __construct(
        string $type,
        bool $nullable=true,
        bool $unique=false,
        $default=null,
        string $comment=""
    ) 
    {
        $this->type = $type;
        $this->nullable = $nullable;
        $this->unique = $unique;
        $this->default = $default;
        $this->comment = $comment;
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
        $comment=$comment==""?"":"COMMENT '{$comment}'";
        return "{$this->name} {$this->type} {$null} {$default} {$uq} {$comment}";
    }
}

class TextSchema extends Schema
{
    private $collation;
    public function __construct(
        string $type,
        bool $nullable=true,
        bool $unique=false,
        $default=null,
        string $collation="",
        string $comment=""
    ){
        parent::__construct(
            $type,
            $nullable,
            $unique,
            $default,
            $comment
        );
        $this->collation = $collation;
    }

    public function build():string{
        $null = $this->nullable?"null":"not null";
        $uq = $this->unique?"unique":"";
        $default = $this->nullable?"":
                        isset($this->default)?
                            "default {$this->default}":
                            "";
        $comment=$comment==""?"":"comment '{$comment}'";
        $collation = $collation==""?"":"collate {$collation}";
        return "{$this->name} {$this->type} {$null} {$default} {$uq} {$collation} {$comment}";
    }
}

class ForeignKeySchema extends Schema
{
    public function __construct(bool $nullable)
    {
        parent::__construct("int unsigned",$nullable);
    }

    public function constraint():string{    
        $table = trim($this->name,"id");
        return "constraint foreign key({$this->name}) references {$table}(id)";
    }
}
?>