<?php
namespace persistence;
use concept\{
    IEntity,
    IOnthos,
    IObject,
    ISerializable
};
use PDO;
use JsonSerializable;

//DATA PROVIDER
class Result implements JsonSerializable {
    private $status;
    private $info;
    function __construct($status,$info)
    {
        $this->status = $status;
        $this->info = $info;
    }

    public function getStatus(){
        return $this->status;
    }

    public function getInfo(){
        return $this->info;
    }

    public function setInfoEntity($name,$val){
        $call = "set".ucfirst($name);
        $this->info->$call($val);
    }

    public function jsonSerialize(){
        http_response_code($this->status);
        return ($this->info instanceof ISerializable)?
                    $this->info->serialize():
                    $this->info;
    }

    public static function getInstance($status,$info){
        return new Result($status,$info);
    }
}

interface IProvider{
    public function getPdo();
    public function getCall();
} 
class Provider implements IProvider
{
	private $dbms;
	private $host;
	private $database;
	private $login;
	private $password;
	private $callVerb;
	private $mustCallVerb;
	
	function __construct($filePath)
	{
		$obj = json_decode(file_get_contents($filePath));
		
		$this->dbms = $obj->dbms;
		$this->host = $obj->host;
		$this->database = $obj->database;
		$this->login = $obj->login;
		$this->password = $obj->password;
		$this->callVerb = $obj->callVerb;
		$this->mustCallVerb = $obj->mustCallVerb;
	}

    public function getPdo(){
        $dsn = "{$this->dbms}:host={$this->host};dbname={$this->database};charset=utf8";
        return new PDO($dsn,$this->login,$this->password);
    }
	
	public function getCall()
	{
		return ($this->mustCallVerb)?$this->callVerb. ' ':'';
	}

    public static function getInstance($filePath){
        return new Provider($filePath);
    }
}


//OPERATION
abstract class EntityCrud
{
    protected $provider;
	
	protected function __construct(IProvider $provider)
	{
        $this->provider = $provider;
	}

    protected function getAnonymousParameters($parameters)
    {
        return ($parameters==NULL)?
                    "":
                    implode(",", array_fill(0, count($parameters), '?'));
    }

    protected function getPropertiesByComma(IEntity $entity){
        return implode(",",$entity->getProperties());
    }

    protected function getParametersByComma(IEntity $entity){
        $parameters=array();
        foreach($entity->getProperties() as $prop){
            array_push($parameters,":{$prop}");
        }

        return implode(",",$parameters);
    }

    protected function getKeyValByComma(IEntity $entity){
        $parameters = array();
        foreach($entity->getProperties() as $prop){
            array_push($parameters,"{$prop}=:{$prop}");
        }
        return implode(",",$parameters);
    }

    protected function getKeyValuesBind(IEntity $entity){
        $vals = array();
        foreach($entity->getProperties() as $prop){
            $method="get".ucfirst($prop);
            $vals[$prop]=$entity->$method();
        }
        
        return $vals;
    }

    protected function getValue(IEntity $entity, $field){
        $method="get".ucfirst($field);
        return $entity->$method();
    }
    
	public function __destruct()
	{
        $this->provider = null;
	}
}

interface ICrud{
    public function insert(IEntity $entity);
    public function update(IEntity $entity);
    public function get(IEntity $entity);
    public function read(IEntity $entity);
    public function list(IEntity $entity,$fields);
    public function listBy(IEntity $entity,$fields,$by);
    public function iSeeDeadPeople(IEntity $entity);
    public function disable(IEntity $entity);
    public function enable(IEntity $entity);
    public function delete(IEntity $entity);
    public function associate(IEntity $entity,IEntity $link);
    public function deassociate(IEntity $entity,IEntity $link);
    public function getAssociate(IEntity $entity, IEntity $link);
    public function nameLike(IOnthos $onthos);
    public function descriptionLike(IObject $object);
}

class Crud extends EntityCrud implements ICrud
{
    const ERROR_MESSAGE="Database request fail, verify entity integrity";
    const REMOVE_OK_MESSAGE = "Register successfully Removed";

	protected function __construct(IProvider $provider)
	{
		parent::__construct($provider);
	}
	
	public function insert(IEntity $entity)
	{
        $entity->setUid(getPavelGuid());
        $name = $entity->getEntityName();
        $fields = $this->getPropertiesByComma($entity);
        $parameters = $this->getParametersByComma($entity);
        $bind = $this->getKeyValuesBind($entity);
        $bind["uid"]=$entity->getUid();

        
		$query = "Insert Into {$name} (uid,{$fields}) Values (:uid,{$parameters})";
        $pdo = $this->provider->getPdo();
        $pdo->beginTransaction();
        $statement = $pdo->prepare($query);
        $result=null;
        try{
            $result = $statement->execute($bind)?
                Result::getInstance(200,array("uid"=>$entity->getUid())):
                Result::getInstance(500,array("error"=>self::ERROR_MESSAGE));
            $pdo->commit();
        }
        catch (Exception $e)
        {  
            $pdo->rollBack();
            $result = Result::getInstance(500,array("error"=>$e->getMessage()));
        }
        finally
        {
            $pdo=null;
            $bind=null;
            $statement=null;
            $parameters=null;
            $fields=null;            
        }

        return $result;
	}
	
	public function update(IEntity $entity)
	{
        $name = $entity->getEntityName($entity);
        $keyValues = $this->getKeyValByComma($entity);
        $bind = $this->getKeyValuesBind($entity);
        $bind["id"]=$entity->getId();
        $query = "Update {$name} Set {$keyValues} Where id=:id";
        $result = 0;

        $pdo = $this->provider->getPdo();
        $pdo->beginTransaction();
        $statement = $pdo->prepare($query);
        $result=null;
        try{
            $result = $statement->execute($bind)?
                Result::getInstance(200,array("ok"=>"Command executed successfully")):
                Result::getInstance(500,array("error"=>self::ERROR_MESSAGE));
            $pdo->commit();
        }
        catch(Exception $e)
        {
            $pdo->rollBack();
            $result = Result::getInstance(500,array("error"=>$e->getMessage()));
        }
        finally
        {
            $pdo=null;
            $bind=null;
            $statement=null;
            $keyValues=null;
        }

        return $result;
	}
	
	public function get(IEntity $entity)
	{
        $name = $entity->getEntityName();
        $query = "Select * From {$name} Where id=:id";
        $pdo = $this->provider->getPdo();
        $statement = $pdo->prepare($query);
        $statement->setFetchMode(PDO::FETCH_CLASS,"\\model\\".$name);
        $statement->execute(array("id"=>$entity->getId()));
        $entity = $statement->fetch();
        $result = ($entity->getId()===0)?
                    Result::getInstance(404,array("error"=>"Id:{$entity->getId()} not found")):
                    Result::getInstance(200,$entity);
        $statement->closeCursor();
        $statement=null;
        $pdo=null;

        return $result;
	}
	
	public function read(IEntity $entity)
	{
        $name = $entity->getEntityName();
        $query = "Select Id From {$name} Where uid=:uid";
        $pdo = $this->provider->getPdo();
        $statement = $pdo->prepare($query);
        $statement->execute(array("uid"=>$entity->getUid()));
        $id = $statement->fetchColumn();
        $statement->closeCursor();
        $statement=null;
        $pdo=null;

        return $id===false?
                    new Result(404,array("error"=>"Uid not found")):
                    new Result(200,array("id"=>$id));
	}

    public function list(IEntity $entity,$fields){
        $name = $entity->getEntityName();
        $query = "Select uid,{$fields} From {$name} Where active=1";
        $pdo = $this->provider->getPdo();
        $statement = $pdo->query($query,PDO::FETCH_ASSOC);
        $result = Result::getInstance(200,$statement->fetchAll());
        $statement->closeCursor();
        $statement=null;
        $pdo=null;

        return $result;        
    }

    public function listBy(IEntity $entity,$fields,$by){
        $name = $entity->getEntityName();
        $clause = array();
        $vals=array();
        foreach($by as $prop){
            $vals[$prop] = $this->getValue($entity,$prop);
            array_push($clause,"{$prop}=:{$prop}");
        }
        $where = implode(" And ",$clause);
        $query = "Select uid,{$fields} From {$name} Where {$where} And active=1";
        $pdo = $this->provider->getPdo();
        $statement = $pdo->prepare($query);
        $statement->setFetchMode(PDO::FETCH_ASSOC);
        $statement->execute($vals);
        $result = Result::getInstance(200,$statement->fetchAll());
        $statement->closeCursor();
        $statement=null;
        $pdo=null;

        return $result;
    }

    public function iSeeDeadPeople(IEntity $entity){
        $name = $entity->getEntityName();
        $query = "Select uid From {$name} Where active=0";
        $pdo = $this->provider->getPdo();
        $statement = $pdo->query($query);
        $result = Result::getInstance(200,$statement->fetchAll(PDO::FETCH_ASSOC));
        $statement->closeCursor();
        $statement=null;
        $pdo=null;

        return $result;        
    }

    public function disable(IEntity $entity){
        $name = $entity->getEntityName();
        $id = $entity->getId();
        $query = "Update {$name} Set active=0 Where id=:id";
        $pdo = $this->provider->getPdo();
        $pdo->beginTransaction();
        $statement = $pdo->prepare($query);
        $result=null;
        try
        {
            $result = $statement->execute(array("id"=>$id))?
                            Result::getInstance(200,array("ok"=>"Id:{$id} disable")):
                            Result::getInstance(500,array("error"=>self::ERROR_MESSAGE));
            $pdo->commit();
        }
        catch(Exception $e)
        {
            $pdo->rollBack();
            $result = Result::getInstance(500,array("error"=>$e->getMessage()));
        }
        finally
        {
            $statement=null;
            $pdo=null;
        }

        return $result;
    }

    public function enable(IEntity $entity){
        $name=$entity->getEntityName();
        $id = $entity->getId();
        $query = "Update {$name} Set active=1 Where id=:id";
        $pdo = $this->provider->getPdo();
        $pdo->beginTransaction();
        $statement = $pdo->prepare($query);
        $result = null;
        try
        {
            $result = $statement->execute(array("id"=>$id))?
                        Result::getInstance(200,array("ok"=>"Id:{$id} enabled")):
                        Result::getInstance(500,array("error"=>self::ERROR_MESSAGE));
            $pdo->commit();
        }
        catch(Exception $e)
        {
            $pdo->rollBack();
            $result = Result::getInstance(500,array("error"=>$e->getMessage()));
        }
        finally
        {
            $statement=null;
            $pdo=null;
        }

        return $result;
    }
	
	public function delete(IEntity $entity)
	{
        $name = $entity->getEntityName();
        $id = $entity->getId();
        $query = "Delete From {$name} Where id=:id";
        $pdo = $this->provider->getPdo();
        $statement = $pdo->prepare($query);
        $result = $statement->execute(array("id"=>$id))?
                    Result::getInstance(200,array("ok"=>self::REMOVE_OK_MESSAGE)):
                    Result::getInstance(500,array("error"=>self::ERROR_MESSAGE));
        $statement = null;
        $pdo=null;
        return $result;
	}

    public function associate(IEntity $entity,IEntity $link){
        $nameEntity=$entity->getEntityName();
        $nameLink=$link->getEntityName();
        $idEntity = "id{$nameEntity}";
        $idLink = "id{$nameLink}";
        $entityId = $entity->getId();
        $linkId = $link->getId();
        $query="Insert Into {$nameEntity}{$nameLink} ({$idEntity},{$idLink}) Values (:{$idEntity},:{$idLink})";
        $pdo = $this->provider->getPdo();
        $pdo->beginTransaction();
        $statement = $pdo->prepare($query);
        $result=null;
        try
        {
            $result = $statement->execute(array($idEntity=>$entityId,$idLink=>$linkId))?
                            Result::getInstance(200,array("ok"=>self::REMOVE_OK_MESSAGE)):
                            Result::getInstance(500,array("error"=>self::ERROR_MESSAGE));
            $pdo->commit();
        }
        catch(Exception $e)
        {
            $pdo->rollBack();
            $result = Result::getInstance(500,array("error"=>$e->getMessage()));
        }
        finally
        {
            $pdo=null;
            $statement=null;
        }
        return $result;
    }

    public function deassociate(IEntity $entity, IEntity $link){
        $nameEntity=$entity->getEntityName();
        $nameLink = $link->getEntityName();
        $idEntity = "id{$nameEntity}";
        
        $query="Delete From {$nameEntity}{$nameLink} Where {$idEntity}=:{$idEntity}";
        $pdo = $this->provider->getPdo();
        $statement = $pdo->prepare($query);
        $result = $statement->execute(array($idEntity=>$entity->getId()))?
                        Result::getInstance(200,array("ok"=>self::REMOVE_OK_MESSAGE)):
                        Result::getInstance(500,array("error"=>self::ERROR_MESSAGE));

        $pdo=null;
        $statement=null;
        return $result;
    }

    public function getAssociate(IEntity $link,$uidOnly=0){
        $nameEntity=$entity->getEntityName();
        $nameLink =$entity->getEntityName();
        $idEntity = "id{$nameEntity}";
        $idLink = "id{$nameLink}";
        $f = $uidOnly?"uid":"*";

        $query="Select {$nameEntity}.{$f} From {$nameEntity} E";
        $query+=" Inner Join {$nameLink}{$nameEntity} L On E.id=L.{$idEntity}";
        $query+=" Where L.{$idLink}=:{$idLink}";

        $pdo = $this->provider->getPdo();
        $statement = $pdo->prepare($query);
        if ($uidOnly)
            $statement->setFetchMode(PDO::FETCH_ASSOC);    
        else
            $statement->setFetchMode(PDO::FETCH_CLASS,"\\model\\".$nameEntity);
        $result = $statement->execute(array($idLink=>$link->getId()))?
                        Result::getInstance(200,$statement->fetchAll()):
                        Result::getInstance(500,array("error"=>self::ERROR_MESSAGE));

        $pdo=null;
        $statement=null;
        return $result;
    }

    public function nameLike(IOnthos $onthos){
        $entity = $onthos->getEntityName();
        $name= $onthos->getName();
        $query = "Select uid,name From {$entity} Where name Like :name And active=1";
        $pdo = $this->provider->getPdo();
        $statement = $pdo->prepare($query);
        $statement->setFetchMode(PDO::FETCH_ASSOC);
        $result = $statement->execute(array("name"=>"%{$name}%"))?
                    Result::getInstance(200,$statement->fetchAll()):
                    Result::getInstance(500,array("error"=>self::ERROR_MESSAGE));

        $pdo=null;
        $statement=null;
        return $result;
    }

    public function descriptionLike(IObject $object){
        $entity = $object->getEntityName();
        $description = $object->getDescription();
        $query = "Select uid,description From {$entity} Where description Like :description And active=1";
        $pdo = $this->provider->getPdo();
        $statement = $pdo->prepare($query);
        $statement->setFetchMode(PDO::FETCH_ASSOC);
        $result = $statement->execute(array("description"=>"%{$description}%"))?
                        Result::getInstance(200,$statement->fetchAll()):
                        Result::getInstance(500,array("error"=>self::ERROR_MESSAGE));

        $pdo=null;
        $statement=null;
        return $result;
    }
}


interface IViewModel{
    public function getModelInstance($entry=null,$itself=true);
}

abstract class ViewModel implements IViewModel
{
    public static function fillModelWithUid(IEntity $entity,$uid){
        $entity->setUid($uid);
        return $entity;
    }

    public static function fillModelWithFields(IEntity $entity,$entry){
        foreach($entry as $prop=>$val){
            $method = "set".ucfirst($prop);
            $entity->$method($val);
        }
        return $entity;
    }

    public static function fillModelWithProperties(IEntity $entity,$entry){
        foreach($entity->getProperties() as $prop){
            $method = "set".ucfirst($prop);
            $entity->$method($entry[$prop]);
        }
        return $entity;
    }

    protected function fill(IEntity $entity,$entry,$itself=true){
        if (isset($entry["uid"]))
            return self::fillModelWithUid($entity,$entry["uid"]);
        else if ($itself)
            return self::fillModelWithProperties($entity,$entry);
        else 
            return self::fillModelWithFields($entity,$entry);
    }

    abstract public function getModelInstance($entry=null,$itself=true);
}


?>