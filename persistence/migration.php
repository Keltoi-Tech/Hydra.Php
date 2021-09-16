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

        private function executeBatch(array $toExec){
            $pdo = $this->provider->getPdo();
            foreach($toExec as $cmd){
                if ($cmd!="")$pdo->exec($cmd);
            }
            $pdo=null;
        }

        public function schemaAnalysis(IDefinition $definition):Result{
            $table = $definition->getTable();
            if ($this->tableExists($table)){
                $pdo=$this->provider->getPdo();
                $query = "select "
                        ."column_name "
                        ."from information_schema.Columns "
                        ."where table_name=:table "
                        ."and column_name not in "
                        ."('id','uid','creationDate','updateDate')";

                $statement = $pdo->prepare($query);
                $statement->execute([
                    "table"=>$table
                ]);

                $schema = array_column(
                    $statement->fetchAll(PDO::FETCH_ASSOC),
                    "column_name"
                );
                
                $commands = $definition->matchDb($schema);
                $statement->closeCursor();
                $statement=null;
                $pdo=null;

                if (!empty($commands["addColumns"]))$this->executeBatch($commands["addColumns"]);
                if (!empty($commands["addConstraint"]))$this->executeBatch($commands["addConstraint"]);
                if (!empty($commands["modifyColumns"]))$this->executeBatch($commands["modifyColumns"]);
                if (!empty($commands["dropColumns"]))$this->executeBatch($commands["dropColumns"]);

                return new Result(100,["ok"=>"done"]);
            }else return $this->create($definition);
        }

        private function tableExists(string $tableName):bool
        {
            $pdo = $this->provider->getPdo();
            $query = "select "
                        ."exists("
                            ."select version "
                            ."from information_schema.Tables "
                            ."where table_name = :name"
                        .") as tableExists";

            $statement = $pdo->prepare($query);
            $statement->execute(["name"=>$tableName]);
            $exists = $statement->fetchAll(PDO::FETCH_COLUMN,0)[0]==1;

            $statement->closeCursor();
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