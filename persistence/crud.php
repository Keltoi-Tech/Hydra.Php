<?php
namespace persistence;
use persistence\IProvider;
use hydra\{
    IEntity,
    IOnthos,
    IObject,
    Result
};
use PDO;
use Exception;
//ABSTRACTION
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

    protected function getPropertiesByComma(IEntity $entity)
    {
        return implode(",",$entity->getProperties());
    }

    protected function getParametersByComma(IEntity $entity)
    {
        $parameters=array();
        foreach($entity->getProperties() as $prop){
            array_push($parameters,":{$prop}");
        }

        return implode(",",$parameters);
    }

    protected function getKeyValByComma(IEntity $entity)
    {
        $parameters = array();
        foreach($entity->getProperties() as $prop){
            array_push($parameters,"{$prop}=:{$prop}");
        }
        return implode(",",$parameters);
    }

    protected function getKeyValuesBind(IEntity $entity)
    {
        $vals = array();
        foreach($entity->getProperties() as $prop){
            $method="get".ucfirst($prop);
            $vals[$prop]=$entity->$method();
        }
        
        return $vals;
    }

    protected function getValue(IEntity $entity, $field)
    {
        $method="get".ucfirst($field);
        return $entity->$method();
    }
    
	public function __destruct()
	{
        $this->provider = null;
	}
}

//CRUD
interface ICrud{
    public function insert(IEntity &$entity):Result;
    public function update(IEntity $entity):Result;
    public function get(IEntity &$entity):Result;
    public function read(IEntity &$entity):Result;
    public function getByUnique(IEntity &$entity,$uq):Result;
    public function list(IEntity $entity,$fields):Result;
    public function listBy(IEntity $entity,$fields,$by):Result;
    public function iSeeDeadPeople(IEntity $entity):Result;
    public function disable(IEntity $entity):Result;
    public function enable(IEntity $entity):Result;
    public function delete(IEntity $entity):Result;
    public function join(IEntity $entity,IEntity $link):Result;
    public function unjoin(IEntity $entity,IEntity $link):Result;
    public function getJoiningEntity(IEntity $entity, IEntity $link, bool $uidOnly):Result;
    public function getJoiningLink(IEntity $entity, IEntity $link, bool $uidOnly):Result;
    public function nameLike(IOnthos $onthos):Result;
    public function nameIs(IOnthos &$onthos):Result;
    public function getOnthosByUid(IOnthos &$onthos):Result;
    public function descriptionLike(IObject $object):Result;
    public function descriptionIs(IObject &$object):Result;
    public function getObjectByUid(IObject &$object):Result;
}

class Crud extends EntityCrud implements ICrud
{
    const ERROR_MESSAGE="Database request fail, verify entity integrity";
    const REMOVE_OK_MESSAGE = "Register successfully Removed";

	protected function __construct(IProvider $provider)
	{
		parent::__construct($provider);
	}
	
	public function insert(IEntity &$entity):Result
	{
        $entity->newUid();
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
                Result::getInstance(100,["ok"=>"continue"]):
                Result::getInstance(500,["error"=>self::ERROR_MESSAGE]);
            $pdo->commit();
        }
        catch (Exception $e)
        {  
            $pdo->rollBack();
            $result = Result::getInstance(500,["error"=>$e->getMessage()]);
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
	
	public function update(IEntity $entity):Result
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
                Result::getInstance(200,["ok"=>"Command executed successfully"]):
                Result::getInstance(500,["error"=>self::ERROR_MESSAGE]);
            $pdo->commit();
        }
        catch(Exception $e)
        {
            $pdo->rollBack();
            $result = Result::getInstance(500,["error"=>$e->getMessage()]);
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
	
	public function get(IEntity &$entity):Result
	{
        $name = $entity->getEntityName();
        $query = "Select * From {$name} Where id=:id";
        $pdo = $this->provider->getPdo();
        $statement = $pdo->prepare($query);
        $statement->setFetchMode(PDO::FETCH_CLASS,"\\model\\".$name);
        $statement->execute(["id"=>$entity->getId()]);
        $o = $statement->fetch();
        if ($o===false) $result = Result::getInstance(404,["error"=>"{$name} not found"]);
        else{
            $entity=$o;
            $result = Result::getInstance(100,["ok"=>"continue"]);
        }
        
        $statement->closeCursor();
        $statement=null;
        $pdo=null;

        return $result;
	}

    public function read(IEntity &$entity):Result
    {
        $name = $entity->getEntityName();
        $query = "Select * From {$name} Where uid=:uid";
        $pdo = $this->provider->getPdo();
        $statement = $pdo->prepare($query);
        $statement->setFetchMode(PDO::FETCH_CLASS,"\\model\\".$name);
        $statement->execute([
            "uid"=>$entity->getUid()
        ]);
        $o = $statement->fetch();
        if ($o===false) $result = Result::getInstance(404,["error"=>"{$name} not found"]);
        else{
            $entity=$o;
            $result = Result::getInstance(100,["ok"=>"continue"]);
        }

        $statement->closeCursor();
        $statement=null;
        $pdo=null;

        return $result;
    }

    public function getByUnique(IEntity &$entity,$uq):Result
	{
        $name = $entity->getEntityName();
        $method= "get".ucfirst($uq);
        $query = "Select id From {$name} Where {$uq}=:{$uq}";
        $pdo = $this->provider->getPdo();
        $statement = $pdo->prepare($query);
        $statement->execute(["{$uq}"=>$entity->$method()]);
        $id = $statement->fetchColumn();
        $entity->setId($id);
        $result = ($id===false)?
                    Result::getInstance(404,["error"=>"{$name} not found"]):
                    Result::getInstance(100,["ok"=>"continue"]);
        $statement->closeCursor();
        $statement=null;
        $pdo=null;

        return $result;
	}

    public function list(IEntity $entity,$fields):Result
    {
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

    public function listBy(IEntity $entity,$fields,$by):Result
    {
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

    public function iSeeDeadPeople(IEntity $entity):Result
    {
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

    public function disable(IEntity $entity):Result
    {
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
                            Result::getInstance(200,["ok"=>"Id:{$id} disable"]):
                            Result::getInstance(500,["error"=>self::ERROR_MESSAGE]);
            $pdo->commit();
        }
        catch(Exception $e)
        {
            $pdo->rollBack();
            $result = Result::getInstance(500,["error"=>$e->getMessage()]);
        }
        finally
        {
            $statement=null;
            $pdo=null;
        }

        return $result;
    }

    public function enable(IEntity $entity):Result
    {
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
                        Result::getInstance(200,["ok"=>"Id:{$id} enabled"]):
                        Result::getInstance(500,["error"=>self::ERROR_MESSAGE]);
            $pdo->commit();
        }
        catch(Exception $e)
        {
            $pdo->rollBack();
            $result = Result::getInstance(500,["error"=>$e->getMessage()]);
        }
        finally
        {
            $statement=null;
            $pdo=null;
        }

        return $result;
    }
	
	public function delete(IEntity $entity):Result
	{
        $name = $entity->getEntityName();
        $id = $entity->getId();
        $query = "Delete From {$name} Where id=:id";
        $pdo = $this->provider->getPdo();
        $statement = $pdo->prepare($query);
        $result = $statement->execute(array("id"=>$id))?
                    Result::getInstance(200,["ok"=>self::REMOVE_OK_MESSAGE]):
                    Result::getInstance(500,["error"=>self::ERROR_MESSAGE]);
        $statement = null;
        $pdo=null;
        return $result;
	}

    public function join(IEntity $entity,IEntity $link):Result
    {
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
                            Result::getInstance(200,["ok"=>"Joining done"]):
                            Result::getInstance(500,["error"=>self::ERROR_MESSAGE]);
            $pdo->commit();
        }
        catch(Exception $e)
        {
            $pdo->rollBack();
            $result = Result::getInstance(500,["error"=>$e->getMessage()]);
        }
        finally
        {
            $pdo=null;
            $statement=null;
        }
        return $result;
    }

    public function unjoin(IEntity $entity, IEntity $link): Result
    {
        $nameEntity=$entity->getEntityName();
        $nameLink = $link->getEntityName();
        $idEntity = "id{$nameEntity}";
        
        $query="Delete From {$nameEntity}{$nameLink} Where {$idEntity}=:{$idEntity}";
        $pdo = $this->provider->getPdo();
        $statement = $pdo->prepare($query);
        $result = $statement->execute(array($idEntity=>$entity->getId()))?
                        Result::getInstance(200,["ok"=>self::REMOVE_OK_MESSAGE]):
                        Result::getInstance(500,["error"=>self::ERROR_MESSAGE]);

        $pdo=null;
        $statement=null;
        return $result;
    }

    public function getJoiningEntity(IEntity $entity,IEntity $link,bool $uidOnly=false):Result
    {
        $nameEntity=$entity->getEntityName();
        $nameLink =$entity->getEntityName();
        $idEntity = "id{$nameEntity}";
        $idLink = "id{$nameLink}";
        $f = $uidOnly?"uid":"*";

        $query="Select {$nameEntity}.{$f} From {$nameEntity} E"
        ." Inner Join {$nameLink}{$nameEntity} L On E.id=L.{$idEntity}"
        ." Where L.{$idLink}=:{$idLink}";

        $pdo = $this->provider->getPdo();
        $statement = $pdo->prepare($query);
        $statement->setFetchMode(PDO::FETCH_ASSOC);
        $result = $statement->execute(array($idLink=>$link->getId()))?
                        Result::getInstance(200,$statement->fetchAll()):
                        Result::getInstance(500,["error"=>self::ERROR_MESSAGE]);

        $pdo=null;
        $statement=null;
        return $result;
    }

    public function getJoiningLink(IEntity $entity,IEntity $link,bool $uidOnly=false):Result
    {
        $nameEntity=$entity->getEntityName();
        $nameLink =$entity->getEntityName();
        $idEntity = "id{$nameEntity}";
        $idLink = "id{$nameLink}";
        $f = $uidOnly?"uid":"*";

        $query="Select {$nameLink}.{$f} From {$nameLink} L"
        ." Inner Join {$nameLink}{$nameEntity} E On L.id=E.{$idLink}"
        ." Where L.{$idEntity}=:{$idEntity}";

        $pdo = $this->provider->getPdo();
        $statement = $pdo->prepare($query);
        $statement->setFetchMode(PDO::FETCH_ASSOC);
        $result = $statement->execute(array($idLink=>$link->getId()))?
                        Result::getInstance(200,$statement->fetchAll()):
                        Result::getInstance(500,["error"=>self::ERROR_MESSAGE]);

        $pdo=null;
        $statement=null;
        return $result;
    }    

    public function nameLike(IOnthos $onthos):Result
    {
        $entity = $onthos->getEntityName();
        $name= $onthos->getName();
        $query = "Select uid,name From {$entity} Where name Like :name And active=1";
        $pdo = $this->provider->getPdo();
        $statement = $pdo->prepare($query);
        $statement->setFetchMode(PDO::FETCH_ASSOC);
        $result = $statement->execute(["name"=>"%{$name}%"])?
                    Result::getInstance(200,$statement->fetchAll()):
                    Result::getInstance(500,["error"=>self::ERROR_MESSAGE]);

        $pdo=null;
        $statement=null;
        return $result;
    }

    public function nameIs(IOnthos &$onthos):Result
    {
        $entity = $onthos->getEntityName();
        $name= $onthos->getName();
        $query = "Select id,uid From {$entity} Where name=:name And active=1";
        $pdo = $this->provider->getPdo();
        $statement = $pdo->prepare($query);
        $statement->execute(["name"=>$name]);
        $obj = $statement->fetch(PDO::FETCH_ASSOC);

        if ($obj===false)$result = Result::getInstance(500,["error"=>self::ERROR_MESSAGE]);
        else
        {
            $onthos->setUid($obj["uid"]);
            $onthos->setId($obj["id"]);
            $result = Result::getInstance(100,["ok"=>"continue"]);
        }                    

        $pdo=null;
        $statement=null;
        return $result;
    }

    public function getOnthosByUid(IOnthos &$onthos):Result
    {
        $entity = $onthos->getEntityName();
        $query = "Select id,name From {$entity} Where uid=:uid And active=1";
        
        $pdo = $this->provider->getPdo();
        $statement = $pdo->prepare($query);
        $statement->execute(["uid"=>$onthos->getUid()]);
        $obj = $statement->fetch(PDO::FETCH_ASSOC);

        if ($obj===false)$result = Result::getInstance(500,["error"=>self::ERROR_MESSAGE]);
        else
        {
            $onthos->setId($obj["id"]);
            $onthos->setName($obj["name"]);
            $result = Result::getInstance(100,["ok"=>"continue"]);
        }                    

        $pdo=null;
        $statement=null;
        return $result;
    }

    public function descriptionLike(IObject $object):Result
    {
        $entity = $object->getEntityName();
        $description = $object->getDescription();
        $query = "Select uid,description From {$entity} Where description Like :description And active=1";
        $pdo = $this->provider->getPdo();
        $statement = $pdo->prepare($query);
        $statement->setFetchMode(PDO::FETCH_ASSOC);
        $result = $statement->execute(["description"=>"%{$description}%"])?
                        Result::getInstance(200,$statement->fetchAll()):
                        Result::getInstance(500,["error"=>self::ERROR_MESSAGE]);

        $pdo=null;
        $statement=null;
        return $result;
    }

    public function descriptionIs(IObject &$object):Result
    {
        $entity = $object->getEntityName();
        $description= $object->getDescription();
        $query = "Select id,uid From {$entity} Where description=:description And active=1";
        $pdo = $this->provider->getPdo();
        $statement = $pdo->prepare($query);
        $statement->execute(["description"=>$description]);
        $obj = $statement->fetch(PDO::FETCH_ASSOC);
        
        if ($obj===false)$result = Result::getInstance(500,["error"=>self::ERROR_MESSAGE]);
        else
        {
            $object->setUid($obj["uid"]);
            $object->setId($obj["id"]);
            $result = Result::getInstance(100,["ok"=>"continue"]);
        }                    

        $pdo=null;
        $statement=null;
        return $result;
    }

    public function getObjectByUid(IObject &$object):Result
    {
        $entity = $object->getEntityName();
        $query = "Select id,description From {$entity} Where uid=:uid And active=1";
        
        $pdo = $this->provider->getPdo();
        $statement = $pdo->prepare($query);
        $statement->execute(["uid"=>$object->getUid()]);
        $obj = $statement->fetch(PDO::FETCH_ASSOC);

        if ($obj===false)$result = Result::getInstance(500,["error"=>self::ERROR_MESSAGE]);
        else
        {
            $object->setId($obj["id"]);
            $object->setUid($obj["description"]);
            $result = Result::getInstance(100,["ok"=>"continue"]);
        }                    

        $pdo=null;
        $statement=null;
        return $result;
    }
}
?>