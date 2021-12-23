<?php
namespace persistence;
use persistence\IProvider;
use hydra\{
    IEntity,
    IOnthos,
    IObject,
    Result,
    Uuid
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

class Crud extends EntityCrud
{
    const ERROR_MESSAGE="Database request fail, verify entity integrity";
    const REMOVE_OK_MESSAGE = "Register successfully Removed";
    const NOT_FOUND = "No data found";

	protected function __construct(IProvider $provider)
	{
		parent::__construct($provider);
	}
	
	protected function insert(IEntity $entity):Result
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
                Result::getInstance(201,$entity):
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
	
	protected function update(IEntity $entity):Result
	{
        $name = $entity->getEntityName($entity);
        $keyValues = $this->getKeyValByComma($entity);

        $bind = $this->getKeyValuesBind($entity);
        $bind["id"]=$entity->getId();

        $query = "Update {$name} Set {$keyValues} Where id=:id";

        $pdo = $this->provider->getPdo();
        $pdo->beginTransaction();

        $statement = $pdo->prepare($query);

        $result=null;
        
        try{
            $result = $statement->execute($bind)?
                Result::getInstance(200,$entity):
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
	
	protected function get(IEntity $entity, bool $alive=true):Result
	{
        $name = $entity->getEntityName();
        $query = "Select * From {$name} Where id=:id and active = :active";
        $pdo = $this->provider->getPdo();

        $statement = $pdo->prepare($query);
        $statement->setFetchMode(PDO::FETCH_CLASS,"\\model\\".$name);
        $statement->execute([
            "id"=>$entity->getId(),
            "active"=>$alive?1:0
        ]);
        $o = $statement->fetch();

        $result = ($o===false)?
                        Result::getInstance(404,["error"=>self::NOT_FOUND]):
                        Result::getInstance(100,$o);
        
        $statement->closeCursor();
        $statement=null;
        $pdo=null;

        return $result;
	}

    protected function read(IEntity $entity,bool $alive=true):Result
    {
        $name = $entity->getEntityName();
        $query = "Select * From {$name} Where uid=:uid and active = :active";
        $pdo = $this->provider->getPdo();

        $statement = $pdo->prepare($query);
        $statement->setFetchMode(PDO::FETCH_CLASS,"\\model\\".$name);
        $statement->execute([
            "uid"=>$entity->getUid(),
            "active"=>$alive?1:0
        ]);
        $o = $statement->fetch();

        $result = ($o===false)?
            Result::getInstance(404,["error"=>self::NOT_FOUND]):
            Result::getInstance(100,$o);

        $statement->closeCursor();
        $statement=null;
        $pdo=null;

        return $result;
    }

    protected function list(IEntity $entity,bool $alive=true):Result
    {
        $name = $entity->getEntityName();
        $query = "Select * From {$name} Where active = :active";
        $pdo = $this->provider->getPdo();

        $statement = $pdo->prepare($query);
        $statement->setFetchMode(PDO::FETCH_CLASS,"\\model\\".$name);
        $statement->execute(["active"=>$alive?1:0]);

        $o = $statement->fetchAll();

        $result = 
            (count($o)===0)? 
                Result::getInstance(404,["error"=>self::NOT_FOUND]):
                Result::getInstance(200,$o);

        $statement->closeCursor();
        $statement=null;
        $pdo=null;

        return $result;
    }

    protected function disable(IEntity $entity):Result
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
                            Result::getInstance(200,["ok"=>"{$name} is disabled"]):
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

    protected function enable(IEntity $entity):Result
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
                        Result::getInstance(200,["ok"=>"{$name} is enabled"]):
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
	
	protected function delete(IEntity $entity):Result
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

    protected function join(IEntity $entity,IEntity $link):Result
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

    protected function unjoin(IEntity $entity, IEntity $link): Result
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
}
?>