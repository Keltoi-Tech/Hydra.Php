<?php
    namespace persistence;
    use hydra\Result;
    use DateInterval;
    use PDO;

    class Migration{
        private $provider;
        public function __construct(IProvider $provider){
            $this->provider = $provider;
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

        public function create(IDefinition $definition):Result
        {
            $result=Result::getInstance(404,["error"=>"not found"]);
            $pdo = $this->provider->getPdo();
            try{
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $pdo->exec($definition->create());
                $result = new Result(100,
                [
                    "ok"=>"Table {$definition->getTable()} sucessfull created"
                ]);
            }
            catch(PDOException $ex)
            {
                $result = new Result(400,[
                    "error"=>"Error on create table {$definition->getTable()}: {$ex->getMessage()}"
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