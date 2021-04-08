<?php
namespace persistence;
use concept\{
    IEntity,
    IOnthos,
    IObject
};
use PDO;

//ENTITY BASE CLASS
abstract class Entity{
    protected $id;
    protected $uid;
    protected $db;

    protected function __construct(array $db){
        $this->db = $db;
    }

    public function getId():int{
        return $this->id;
    }
    public function setId(int $id){
        $this->id = $id;
    }       
    public function getUid():string{
        return $this->uid;
    }
    public function setUid(string $uid){
        $this->uid = $uid;
    }       

    public function getDB():array{
        return $this->db;
    }

    public function getProperties():array{
        return array_keys($this->db);
    }
}

//RESULT ABSTRACTION
class Result {
    private $status;
    private $info;
    function __construct($status,$info)
    {
        $this->status = $status;
        $this->info = $info;
    }

    public function getStatus():int{
        return $this->status;
    }

    public function getInfo($field=null){
        return isset($field)?$this->info[$field]:$this->info;
    }

    public function assert(int $statusCode):bool{
        return $this->status===$statusCode;
    }

    public function setInfoEntity($name,$val){
        $call = "set".ucfirst($name);
        $this->info->$call($val);
    }

    public static function getInstance($status,$info):Result{
        return new Result($status,$info);
    }
}
//DATA PROVIDER
interface IProvider{
    public function getPdo():PDO;
    public function getCall():string;
    public function getHash():string;
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
    private $issuer;
	
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
        $this->issuer = $obj->issuer;
	}

    public function getHash():string
    {
        return hash("sha256",$this->issuer);
    }

    public function getPdo():PDO
    {
        $dsn = "{$this->dbms}:host={$this->host};dbname={$this->database};charset=utf8";
        return new PDO($dsn,$this->login,$this->password);
    }
	
	public function getCall():string
	{
		return ($this->mustCallVerb)?$this->callVerb. ' ':'';
	}

    public static function getInstance($filePath):Provider
    {
        return new Provider($filePath);
    }
}


//OPERATION
//DATA TOOLS FOR CRUD
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
    public function getByUnique(IEntity &$entity,$uq):Result;
    public function list(IEntity $entity,$fields):Result;
    public function listBy(IEntity $entity,$fields,$by):Result;
    public function iSeeDeadPeople(IEntity $entity):Result;
    public function disable(IEntity $entity):Result;
    public function enable(IEntity $entity):Result;
    public function delete(IEntity $entity):Result;
    public function associate(IEntity $entity,IEntity $link):Result;
    public function deassociate(IEntity $entity,IEntity $link):Result;
    public function getAssociate(IEntity $entity, IEntity $link):Result;
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
        $entity = $statement->fetch();
        $result = ($entity->getId()===0)?
                    Result::getInstance(404,["error"=>"Id:{$entity->getId()} not found"]):
                    Result::getInstance(100,["ok"=>"continue"]);
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

    public function associate(IEntity $entity,IEntity $link):Result
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
                            Result::getInstance(200,["ok"=>self::REMOVE_OK_MESSAGE]):
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

    public function deassociate(IEntity $entity, IEntity $link):Result
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

    public function getAssociate(IEntity $link,$uidOnly=0):Result
    {
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
        $description= $object->getDescription();
        $query = "Select id,description From {$entity} Where uid=:uid And active=1";
        
        $pdo = $this->provider->getPdo();
        $statement = $pdo->prepare($query);
        $statement->execute(["uid"=>$object->getUid()]);
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
}

//VIEWMODEL ABSTRACTION CLASS
interface IViewModel{
    public function toModel(array $entry=null,bool $itself=true);
}
abstract class ViewModel implements IViewModel
{
    protected $name;
    protected function __construct($name)
    {
        $this->name = $name;
    }

    public static function fillModelWithUid(IEntity $entity,string $uid)
    {
        $entity->setUid($uid);
        return $entity;
    }

    public static function fillModelWithFields(IEntity $entity,array $entry)
    {
        foreach($entry as $prop=>$val){
            $method = "set".ucfirst($prop);
            $entity->$method($val);
        }
        return $entity;
    }

    public static function fillModelWithProperties(IEntity $entity,array $entry)
    {
        foreach($entity->getProperties() as $prop){
            $method = "set".ucfirst($prop);
            $entity->$method($entry[$prop]);
        }
        return $entity;
    }

    protected function fill(IEntity $entity,array $entry,bool $itself=true)
    {
        if (isset($entry["uid"]))
            return self::fillModelWithUid($entity,$entry["uid"]);
        else if ($itself)
            return self::fillModelWithProperties($entity,$entry);
        else 
            return self::fillModelWithFields($entity,$entry);
    }


    public function toModel(array $entry=null,bool $itself=true)
    {
        $entity = new $this->name();
        return isset($entry)?$this->fill($entity,$entry,$itself):$entity;
    }
}

//VIEWSET ABSTRACTION CLASS
abstract class ViewSet{
    protected $valid;

    protected function __construct(Result $valid)    
    {
        $this->valid= $valid;
    }

    protected function __destruct()
    {
        $this->valid = null;
    }

    public function getValid():Result
    {
        return $this->valid;
    }

    protected function verifyKey($key){
        $payload = $this->valid->getInfo();
        return array_key_exists($key,$payload)?
                new Result(100,["ok"=>"continue"]):
                new Result(403,["error"=>"Provided credentials has no association with provided app"]);
    }

    protected function getPayload($field=null)
    {
        return $this->valid->getInfo($field);
    }

    public function authorize()
    {
        return $this->valid->assert(100);
    }  
}

abstract class Validation{
    protected $entry;
    protected $viewModel;

    protected function __construct(array $entry, IViewModel $viewModel){
        $this->entry = $entry;
        $this->viewModel= $viewModel;
    }

    protected function validate_uid(){
        return (strlen($this->entry["uid"])===36)?
                    new Result(100,null):
                    new Result(400,["error"=>"Invalid guid format"]);
    }

    protected function validate_id(){
        return ($this->entry["id"]>0)?
            new Result(100,null):
            new Result(400,["error"=>"Invalid id format"]);
    }

    public function pass(){
        return $this->viewModel->toModel();
    }

    public function run(array $fields):Result{
        foreach($fields as $field){
            $method= "validate_{$field}";
            $assert100 = $this->$method();
            if (!$assert100->assert(100))return $assert100;
        }

        $var_model=[];
        foreach($fields as $field){
            $var_model[$field] = $this->entry[$field];
        }

        return new Result(100,[
            "model"=>$this->viewModel->toModel($var_model,false)
        ]);
    }
}

?>