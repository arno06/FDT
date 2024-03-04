<?php

namespace core\db{

    /**
     * Class DataManager
     * @package core\db
     */
    class DataManager{

        /**
         * @var array
         */
        private $tables = array();

        /**
         * @var array
         */
        private $foreign_values = array();

        /**
         * @param string $pTableName
         * @param string $pIdName
         * @param string $pSelectField
         * @return DataManagerSource
         */
        public function addSource($pTableName, $pIdName, $pSelectField = null){
            $ins = new DataManagerSource($pTableName, $pIdName, $pSelectField??$pTableName.".".$pIdName);
            $this->tables[] = $ins;
            return $ins;
        }

        /**
         * @param string $pRef
         * @param mixed $pValue
         */
        public function addForeignValue($pRef, $pValue){
            $pValue = Query::escapeValue($pValue);
            if(!isset($this->foreign_values[$pRef])){
                $this->foreign_values[$pRef] = array();
            }
            if(in_array($pValue, $this->foreign_values[$pRef])||is_null($pRef)){
                return;
            }
            $this->foreign_values[$pRef][] = $pValue;
        }

        /**
         * @param string $pHandler
         * @return array
         */
        public function export($pHandler = "default"){
            $tables = array();
            $schema = array();
            /** @var DataManagerSource $source */
            foreach($this->tables as $source){
                $schema[] = $source->toArray();
                $cond = Query::condition();
                list($tableField, $field) = explode(".", $source->getSelectField());
                if(empty($this->foreign_values[$source->getSelectField()])){
                    trigger_error('Empty foreign values for table '.$source->getTable(), E_USER_WARNING);
                    continue;
                }
                $cond->andWhere($field, Query::IN, "(".implode(', ', $this->foreign_values[$source->getSelectField()]).")", false);
                $data = Query::select('*', $source->getTable())->setCondition($cond)->execute($pHandler);

                $tables[$source->getTable()] = $data;

                foreach($data as $datum){
                    if(!isset($datum[$source->getId()])){
                        continue;
                    }
                    $this->addForeignValue($source->getTable().'.'.$source->getId(), $datum[$source->getId()]);
                }

                if($source->hasForeignKeys()){
                    $keys = $source->getForeignKeys();
                    foreach($data as $datum){
                        foreach($keys as $name=>$alias){
                            $this->addForeignValue($alias, $datum[$name]);
                        }
                    }
                }
            }

            return array(
                "schema"=>$schema,
                "tables"=>$tables
            );

        }

        /**
         * @param array $pData
         * @param null $pDomain
         * @param string $pHandler
         */
        public function import($pData, $pDomain = null, $pHandler = "default"){

            $schema = $pData['schema'];
            $data = $pData['tables'];
            $dependencies = [];
            $ordered = [];
            $ids = array();

            foreach($schema as &$infos){
                $neededTables = [];
                $table = $infos['table'];
                $select_field = explode('.', $infos['select_field']);
                if($select_field[0]!= $table){
                    $neededTables[] = $select_field[0];
                }
                foreach($infos['foreign_keys'] as $ref_field=>$fk){
                    $f = explode(".", $fk);
                    if($f[0] == $table){

                        usort($data[$table], function($pA, $pB) use ($ref_field){
                            if(is_null($pA[$ref_field])){
                                return -1;
                            }
                            if(is_null($pB[$ref_field])){
                                return 1;
                            }
                            return $pA[$ref_field] - $pB[$ref_field];
                        });

                        continue;
                    }
                    if(in_array($f[0], $neededTables)){
                        continue;
                    }
                    $neededTables[] = $f[0];
                }
                $infos['needed'] = $neededTables;
                $dependencies[$table] = $infos;

                $in = in_array($table, $ordered);
                foreach($infos['needed'] as $ta){
                    if(in_array($ta, $ordered)){
                        continue;
                    }
                    if($in){
                        array_unshift($ordered, $ta);
                    }else{
                        array_push($ordered, $ta);
                    }
                }
                if(!$in){
                    $ordered[] = $table;
                }
            }

            foreach($ordered as $tableName){
                if(!isset($dependencies[$tableName])){
                    continue;
                }
                $info = $dependencies[$tableName];
                $id = $info["id"];
                $select_id = $info["select_field"];
                $name = $info["table"];

                if(!isset($data[$name])){
                    continue;
                }

                $insert = $data[$name];

                if(!preg_match('/^'.$name.'\.(.+)$/', $select_id, $matches)){
                    if(!isset($info["foreign_keys"])){
                        $info["foreign_keys"] = array();
                    }
                    $d = explode(".", $select_id);
                    $info["foreign_keys"][$d[1]] = $select_id;
                }

                if(!isset($ids[$name.".".$id])){
                    $ids[$name.".".$id] = array();
                }

                foreach($insert as $item){
                    foreach($item as $f=>$v){
                        if(is_null($item[$f])||empty($v)){
                            unset($item[$f]);
                        }
                    }
                    $old_id = false;
                    if(isset($item[$id])){
                        $old_id = $item[$id];
                        unset($item[$id]);
                    }

                    if(isset($info["foreign_keys"])){
                        $keys = $info["foreign_keys"];
                        foreach($keys as $field=>$ref){
                            if(!isset($item[$field]) || (empty($item[$field])||is_null($item[$field]))){
                                unset($item[$field]);
                                continue;
                            }
                            if(isset($item[$field]) && isset($ids[$ref])){
                                if(isset($ids[$ref][$item[$field]])){
                                    $item[$field] = $ids[$ref][$item[$field]];
                                }
                            }
                        }
                    }

                    if(Query::insert($item)->into($name)->execute($pHandler)){
                        if($old_id){
                            $ids[$name.".".$id][$old_id] = DBManager::get($pHandler)->getInsertId();
                        }
                    }else{
                        foreach($ids as $name=>$values){
                            list($table, $id) = explode(".", $name);
                            foreach($values as $old=>$new){
                                Query::delete()->from($table)->where($id, Query::EQUAL, $new)->execute($pHandler);
                            }
                        }
                        trigger_error('Une erreur est apparue lors d\'une insertion pour la table '.$name, E_USER_ERROR);
                    }
                }

            }

            if(!is_null($pDomain) && isset($data["main_uploads"])){
                foreach($data["main_uploads"] as $datum){
                    $file = $pDomain."/".$datum["path_upload"];
                    file_put_contents($datum["path_upload"], file_get_contents($file));
                }
            }

            return true;
        }

        static public function upgradeSchema($pFromHandler, $pToHandler, $pExecute = false){
            $upgrades = [];
            $from = self::getTables($pFromHandler);
            $to = self::getTables($pToHandler);

            foreach($from as $tableName => $tableInfos){
                if(!isset($to[$tableName])){
                    $create = Query::execute('SHOW CREATE TABLE '.$tableName.';', $pFromHandler);
                    $create = preg_replace('/\sAUTO_INCREMENT=[0-9]+/', '', $create[0]['Create Table']);
                    $upgrades[] = $create.";";
                    continue;
                }
                $tableTo = $to[$tableName];

                foreach($tableInfos as $colName=>$col){
                    if(!isset($tableTo[$colName])){
                        $upgrades[] = Query::alter($tableName)->addField($colName, $col['Type'], "", $col['Default'], $col['Null']=="YES")->get();
                        continue;
                    }
                    $colTo = $tableTo[$colName];

                    foreach($col as $key=>$value){
                        if(!isset($colTo[$key]) && $key!="Default"){
                            trigger_error("Missing something for ".$colName.'.'.$key."(".$value.") in ".$tableName, E_USER_WARNING);
                            continue;
                        }
                        if($value != $colTo[$key]){
                            switch($key){
                                case "Type":
                                    trace("For ".$key." newVal=".$value." oldVal=".$colTo[$key]);
                                    $upgrades[] = Query::alter($tableName)->changeField($colName, $value, "", $col['Default'], $col['Null']=="YES")->get();
                                    break;
                                default:
                                    trigger_error("New value for ".$colName.'.'.$key." in ".$tableName." : old=".$colTo[$key]." new=".$value, E_USER_WARNING);
                                    break;
                            }
                        }
                    }
                }

                foreach($tableTo as $colName=>$col){
                    if(!isset($tableInfos[$colName])){
                        $upgrades[] = Query::alter($tableName)->removeField($colName)->get();
                    }
                }
            }

            foreach($to as $tableName => $tableInfos){
                if(!isset($from[$tableName])){
                    $upgrades[] = Query::drop($tableName)->get();
                }
            }

            if($pExecute){
                foreach($upgrades as $query){
                    Query::execute($query, $pToHandler);
                }
            }

            return implode(PHP_EOL, $upgrades);
        }

        static public function getTables($pHandler){
            $tables = [];
            $raw = Query::execute('SHOW TABLES;', $pHandler);
            foreach($raw as $item){
                $table_name = array_shift($item);
                $infos = Query::execute('DESCRIBE '.$table_name.';', $pHandler);
                $table = [];
                foreach($infos as $col){
                    $table[$col['Field']] = $col;
                }
                $tables[$table_name] = $table;
            }
            return $tables;
        }
    }

    /**
     * Class DataManagerSource
     * @package core\db
     */
    class DataManagerSource{

        /**
         * @var string
         */
        private $table;

        /**
         * @var string
         */
        private $id;

        /**
         * @var string
         */
        private $select_field;

        /**
         * @var array
         */
        private $foreign_keys = array();

        /**
         * DataManagerSource constructor.
         * @param string $pTableName
         * @param string $pId
         * @param string $pSelectField
         */
        public function __construct($pTableName, $pId, $pSelectField){
            $this->table = $pTableName;
            $this->id = $pId;
            $this->select_field = $pSelectField;
        }

        /**
         * @param string $pField
         * @param string $pForeignTable
         * @param string $pForeignField
         * @return $this
         */
        public function addForeignKey($pField, $pForeignTable, $pForeignField = null){
            $this->foreign_keys[$pField] = $pForeignTable.".".($pForeignField??$pField);
            return $this;
        }

        /**
         * @return string
         */
        public function getId(){
            return $this->id;
        }

        /**
         * @return string
         */
        public function getTable(){
            return $this->table;
        }

        /**
         * @return string
         */
        public function getSelectField(){
            return $this->select_field;
        }

        /**
         * @return array
         */
        public function getForeignKeys(){
            return $this->foreign_keys;
        }

        /**
         * @return bool
         */
        public function hasForeignKeys(){
            return !empty($this->foreign_keys);
        }

        /**
         * @return array
         */
        public function toArray(){
            return array(
                "table"=>$this->table,
                "id"=>$this->id,
                "select_field"=>$this->select_field,
                "foreign_keys"=>$this->foreign_keys
            );
        }
    }
}