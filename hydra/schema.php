<?php
namespace hydra;
class Schema
{
    protected $name;
    protected $type;
    protected $nullable;
    protected $unique;
    protected $default;
    protected $comment;

    public function __construct(
        string $type,
        bool $nullable=true,
        bool $unique=false,
        $default=null,
        string $comment=null
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
        $null = $this->nullable?"null":"not null";
        $uq = $this->unique?"unique":"";
        $default = (
            $this->nullable?
            "":
            (
                isset($this->default)?
                    "default {$this->default}":
                    ""
            )
        );
        $comment=isset($this->comment)?
                        "comment '{$this->comment}'":
                        "";

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
        string $collation=null,
        string $comment=null
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
        $default = (
            $this->nullable?
                "":
                (
                    isset($this->default)?
                        "default {$this->default}":
                        ""
                )
        );
        $comment=isset($this->comment)?
                    "comment '{$this->comment}'":"";

        $collation=isset($this->collation)?
                        "collate {$this->collation}":"";
                    
        return "{$this->name} {$this->type} {$null} {$default} {$uq} {$collation} {$comment}";
    }
}

class ForeignKeySchema extends Schema
{
    private $references;
    
    public function __construct(bool $nullable=true,string $references=null)
    {
        parent::__construct("int unsigned",$nullable);
        if (isset($references))$this->references = $references;
    }

    public function getTableName(){
        return isset($this->references)?$this->references:trim($this->name,"id");
    }

    public function constraintName(){
        return "fk_{$this->name}_".ucfirst($this->getTableName());
    }

    public function constraint():string{    
        $table = $this->getTableName();
        $constraint = $this->constraintName();
        return "constraint {$constraint} foreign key({$this->name}) references {$table}(id)";
    }
}
?>