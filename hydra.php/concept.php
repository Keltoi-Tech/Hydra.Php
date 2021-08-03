<?php
namespace hydra;

interface ILog{
    public function getAuthor():string;
    public function setAuthor(string $author);
}

interface IAuth{
    public function getAuth():string;
    public function getType():string;
}

interface ISerializable{
    public function serialize();
}

interface IEntity
{
	public function setId(int $id);
	public function getId():int;
    public function getUid():string;
    public function setUid(string $uid);
    public function getEntityName():string;
    public function getDB():array;
    public function getProperties():array;
}

interface IOnthos extends IEntity
{
    public function setName(string $name);
	public function getName():string;
}

interface IObject extends IEntity
{
    public function setDescription(string $description);
	public function getDescription():string;
}
?>