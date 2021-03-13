<?php
namespace concept;
interface ISerializable{
    public function serialize();
}

interface IEntity
{
	public function setId($id);
	public function getId();
    public function getUid();
    public function setUid($uid);
    public function getEntityName();
    public function getProperties();
}

interface IOnthos extends IEntity
{
    public function setName($name);
	public function getName();
}

interface IObject extends IEntity
{
    public function setDescription($description);
	public function getDescription();
}
?>