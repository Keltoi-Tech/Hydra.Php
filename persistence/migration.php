<?php
    namespace persistence;
    use hydra\Result;
    use token\{HS256Jwt,ObjectToken};

    class Migration{
        private $provider;
        public function __construct(IProvider $provider){
            $this->provider = $provider;
        }

        public function request($op):Result{
            $secret = $this->provider->getHash();
            $expire = date_create();
            $expire->add(new DateInterval('PT11S'));
            $now = date_create();
            $jwt = HS256Jwt::getInstance($secret);
            $token = $jwt->getToken(
                ObjectToken::getInstance([
                    "alg"=>"HS256",
                    "typ"=>"JWT"
                ]),
                ObjectToken::getInstance([
                    "iss"=>"Keltoi",
                    "iat"=>intval(date_format($now,"U")),
                    "exp"=>intval(date_format($expire,"U")),
                    "sub"=>hash("sha256",$op)
                ])
            );
            $jwt=null;
            return new Result(100,["token"=>$token]);
        }

        public function schemaAnalysis(IDefinition $definition):Result{
            /*if ($this->tableExists($definition->getTable())){

            else return $this->create($definition)*/
        }

        private function tableExists(string $tableName):bool
        {
            $pdo = $this->provider->pdo();
            $query = "select "
                     ."exists ("
                        ."select 1 "
                        ."from information_schema.columns "
                        ."where table_name=:name) "
                    ."as existsTable";

            $statement = $pdo->prepare($query);
            $statement->execute(["name"=>$definition->getTable()]);
            $exists = $statement->fetchAll(PDO::FETCH_COLUMN,0)[0]==1;

            $statement=null;
            $pdo=null;

            return $exists;
        }

        public function create(IDefinition $defintion):Result
        {
            $result=Result::getInstance(404,["error"=>"not found"]);

            $pdo = $this->provider->getPdo();
            try{
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $pdo->exec($defintion->create());
                $result = new Result(100,
                [
                    "ok"=>"Table {$definition->getName()} sucessfull created"
                ]);
            }
            catch(PDOException $ex)
            {
                $result = new Result(400,[
                    "error"=>"Error on create table {$defintion->getName()}: {$ex->getMessage()}"
                ]);
            }
            finally
            {
                $pdo=null;
            }

            return $result;
        }
    }
?>