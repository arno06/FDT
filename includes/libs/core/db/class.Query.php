<?php
namespace core\db
{
    use Exception;
    use core\application\Configuration;

    /**
     * @author Arnaud NICOLAS <arno06@gmail.com>
     * @version .4
     * @package db
     * @subpackage query
     */
    class Query
    {
        /**
         * @var String
         */
        const LIKE 				= 	" LIKE ";
        /**
         * @var String
         */
        const EQUAL				= 	" = ";
        /**
         * @string
         */
        const NOT_EQUAL         =   " != ";
        /**
         * @var String
         */
        const UPPER 			= 	" > ";
        /**
         * @var String
         */
        const UPPER_EQUAL		=	" >= ";
        /**
         * @var String
         */
        const LOWER 			= 	" < ";
        /**
         * @var String
         */
        const LOWER_EQUAL		=	" <= ";

        /**
         * @var String
         */
        const IS				=	" IS ";
        /**
         * @var String
         */
        const IS_NOT			=	" IS NOT ";
        /**
         * @var String
         */
        const JOIN				=	" JOIN ";
        /**
         * @var String
         */
        const JOIN_NATURAL		=	" NATURAL JOIN ";
        /**
         * @var String
         */
        const JOIN_INNER 		= 	" INNER JOIN ";
        /**
         * @var String
         */
        const JOIN_OUTER_FULL 	= 	" FULL OUTER JOIN ";
        /**
         * @var String
         */
        const JOIN_OUTER_LEFT 	= 	" LEFT OUTER JOIN ";
        /**
         * @var String
         */
        const JOIN_OUTER_RIGHT 	= 	" RIGHT OUTER JOIN ";
        /**
         * @var String
         */
        const JOIN_CROSS 		= 	" CROSS JOIN ";
        /**
         * @var String
         */
        const JOIN_UNION 		= 	" UNION JOIN ";
        /**
         * @var String
         */
        const IN                =   " IN ";
        /**
         * @var String
         */
        const MATCH             =   " MATCH ";

        /**
         * @var array
         */
        static private $specials = array(
            "NOW()",
            "NULL"
        );

        /**
         * Méthode d'execution d'une requêtes SQL
         * @param  String $pQuery
         * @param  String $pHandler
         * @param  bool   $pRaw
         * @return array|resource|null|false
         */
        static public function execute($pQuery, $pHandler = "default", $pRaw = false)
        {
            if(!is_string($pQuery))
                return null;
            $dbHandler = DBManager::get($pHandler);
            if(!$dbHandler)
                return false;
            $raw = $pRaw||!preg_match("/^(select|show|describe|explain)/i", $pQuery, $matches);
            return $dbHandler->execute($pQuery, $raw);
        }

        /**
         * Méthode de création d'une requête SQL SELECT
         * @param String $pFields
         * @param String $pTables
         * @return QuerySelect
         */
        static public function select($pFields, $pTables)
        {
            return new QuerySelect($pFields, $pTables);
        }

        /**
         * @static
         * @param $pTable
         * @param null|QueryCondition $pCondition
         * @param String $pHandler
         * @return int
         */
        static public function count($pTable, QueryCondition $pCondition = null, $pHandler = "default")
        {
            $q = Query::select("count(1) as nb", $pTable)->setCondition($pCondition)->execute($pHandler);
            return $q[0]["nb"];
        }

        /**
         * Méthode de création d'une condition SQL indépendante (instructions WHERE, ORDER BY, LIMIT...)
         * @return QueryCondition
         */
        static public function condition()
        {
            return new QueryCondition();
        }

        /**
         * Méthode de création d'une requête 'INSERT' d'insertion d'une tuple
         * @param array $pValues
         * @return QueryInsert
         */
        static public function insert($pValues)
        {
            return new QueryInsert($pValues, QueryInsert::UNIQUE);
        }

        /**
         * Méthode de création d'une requête 'INSERT' d'insertion de N tuples
         * @param array $pValues
         * @return QueryInsert
         */
        static public function insertMultiple($pValues)
        {
            return new QueryInsert($pValues, QueryInsert::MULTIPLE);
        }

        /**
         * @static
         * @param array $pValues
         * @return QueryReplace
         */
        static public function replace($pValues)
        {
            return new QueryReplace($pValues, QueryInsert::UNIQUE);
        }

        /**
         * @static
         * @param array $pValues
         * @return QueryReplace
         */
        static public function replaceMultiple($pValues)
        {
            return new QueryReplace($pValues, QueryInsert::MULTIPLE);
        }

        /**
         * Méthode de création d'une requête DELETE
         * @return QueryDelete
         */
        static public function delete()
        {
            return new QueryDelete();
        }

        /**
         * Méthode de création d'une requête UPDATE
         * @param String $pTable
         * @return QueryUpdate
         */
        static public function update($pTable)
        {
            return new QueryUpdate($pTable);
        }

        /**
         * Méthode de création d'une requête DROP TABLE
         * @param  $pTable
         * @return QueryDrop
         */
        static public function drop($pTable)
        {
            return new QueryDrop($pTable);
        }

        /**
         * Méthode de création d'une requête TRUNCATE TABLE
         * @param  $pTable
         * @return QueryTruncate
         */
        static public function truncate($pTable)
        {
            return new QueryTruncate($pTable);
        }

        /**
         * @static
         * @param string $pTable
         * @param string $pStorageEngine
         * @param string $pCollation
         * @return QueryCreate
         */
        static public function create($pTable, $pStorageEngine = "InnoDB", $pCollation = "latin1_swedish_ci")
        {
            return new QueryCreate($pTable, $pStorageEngine, $pCollation);
        }

        /**
         * @static
         * @param  $pTable
         * @return QueryAlter
         */
        static public function alter($pTable)
        {
            return new QueryAlter($pTable);
        }

        /**
         * @static
         * @var String $pHandler
         * @return string
         */
        static public function getError($pHandler = "default")
        {
            return DBManager::get($pHandler)->getError();
        }

        /**
         * @static
         * @var String $pHandler
         * @return int
         */
        static public function getErrorNumber($pHandler = "default")
        {
            return DBManager::get($pHandler)->getErrorNumber();
        }

        /**
         * Méthode d'échappement d'une valeur (simple quote, double quote...)
         * @param String $pValue
         * @param Boolean $escape
         * @return String
         */
        static public function escapeValue($pValue, $escape = true)
        {
            if (!$escape)
                return $pValue;
            if(!$pValue){
                return "''";
            }
            elseif(!in_array(strtoupper($pValue), self::$specials))
            {
                switch(self::$mode)
                {
                    case "sqlite":
                        return "'".\SQLite3::escapeString($pValue)."'";
                    default:
                        return "'".addslashes($pValue)."'";
                }
            }
            else
                return strtoupper($pValue);
        }

        static public $mode = "sql";
    }

    /**
     * @author Arnaud NICOLAS <arno06@gmail.com>
     * @version .1
     * @package db
     * @subpackage query
     */
    class BaseQuery
    {
        /**
         * Nom de la table
         * @var String
         */
        protected $table;


        /**
         * @param string $pTable
         */
        public function __construct($pTable)
        {
            $this->table = $pTable;
        }

        /**
         * @throws Exception
         * @return string
         */
        public function get()
        {
            throw new Exception("La méthode 'get' doit être surchargée.");
        }

        /**
         * @param String $pHandler
         * @param bool   $pRaw
         * @return array|resource
         */
        public function execute($pHandler = "default", $pRaw = false)
        {
            return Query::execute($this->get(), $pHandler, $pRaw);
        }
    }

    /**
     * @author Arnaud NICOLAS <arno06@gmail.com>
     * @version .1
     * @package db
     * @subpackage query
     */
    class QueryCondition
    {
        /**
         * @var array
         */
        private $ands = array();
        /**
         * @var array
         */
        private $or = array();
        /**
         * @var array
         */
        private $havingAnds =array();
        /**
         * @var array
         */
        private $havingOr =array();
        /**
         * @var array
         */
        private $existAnds = array();
        /**
         * @var array
         */
        private $existOr = array();
        /**
         * @var String
         */
        private $order = "";
        /**
         * @var String
         */
        private $limit = "";
        /**
         * @var String
         */
        private $group = "";

        /**
         * @param QuerySelect $pQuery
         * @return QueryCondition
         */
        public function andExists(QuerySelect $pQuery)
        {
            array_push($this->existAnds, "EXISTS (".$pQuery->get().")");
            return $this;
        }

        /**
         * @param QuerySelect $pQuery
         * @return QueryCondition
         */
        public function orExists(QuerySelect $pQuery)
        {
            array_push($this->existOr, "EXISTS (".$pQuery->get().")");
            return $this;
        }

        /**
         * @param QuerySelect $pQuery
         * @return void
         */
        public function andNotExists(QuerySelect $pQuery)
        {
            array_push($this->existAnds, "NOT EXISTS (".$pQuery->get().")");
        }

        /**
         * @param QuerySelect $pQuery
         * @return QueryCondition
         */
        public function orNotExists(QuerySelect $pQuery)
        {
            array_push($this->existOr, "NOT EXISTS (".$pQuery->get().")");
            return $this;
        }

        /**
         * Méthode d'ajout d'une condition 'OR' à l'instance de condition en cours
         * @param $pField
         * @param $pType
         * @param $pValue
         * @param bool $pEscape
         * @return $this
         */
        public function orWhere	($pField, $pType, $pValue, $pEscape = true)
        {
            if($pEscape)
                $pValue = Query::escapeValue($pValue);
            array_push($this->or, $pField.$pType.$pValue);
            return $this;
        }

        /**
         * Méthode d'ajout d'une condition 'AND' à l'instance de condition en cours
         * @param String $pField
         * @param String $pType
         * @param String $pValue
         * @param bool   $pEscape
         * @return QueryCondition
         */
        public function andWhere($pField, $pType, $pValue, $pEscape = true)
        {
            if($pEscape)
                $pValue = Query::escapeValue($pValue);
            if ($pType == Query::MATCH)
                array_push($this->ands, " MATCH(".$pField.") AGAINST (".$pValue.")");
            else
                array_push($this->ands, $pField.$pType.$pValue);
            return $this;
        }


        public function andMatch($pField, $pValue)
        {
            $temp = trim($pValue);
            if (empty($temp)) return $this;

            $keywords = explode(" ", $pValue);
            $against = "";
            for($i = 0, $max = count($keywords) ; $i < $max ; $i++)
                if (!empty($keywords[$i]))
                {
                    $against .= "+".$keywords[$i];
                    if ($i < $max-1) $against .= " ";
                }

            if ($pValue[strlen($pValue)-1] != " ")
                $against .= "*";
            array_push($this->ands, " MATCH(".$pField.") AGAINST(".Query::escapeValue($against)." IN BOOLEAN MODE)");
            return $this;
        }


        /**
         * Méthode d'ajout d'un HAVING 'OR' à l'instance de condition en cours
         * @param String $pField
         * @return QueryCondition
         */
        public function orHaving($pField)
        {
            array_push($this->havingOr, Query::escapeValue($pField));
            return $this;
        }

        /**
         * Méthode d'ajout d'un HAVING 'AND' à l'instance de condition en cours
         * @param String $pField
         * @return QueryCondition
         */
        public function andHaving($pField)
        {
            array_push($this->havingAnds, Query::escapeValue($pField));
            return $this;
        }

        /**
         * Méthode d'ajout d'un 'GROUP BY'
         * @param String $pField
         * @return QueryCondition
         */
        public function groupBy($pField)
        {
            $this->group = " GROUP BY ".$pField;
            return $this;
        }

        /**
         * Méthode d'ajout d'une instance existante d'une condition dans un 'AND' dans l'instance de la condition en cours
         * @param QueryCondition $pCondition
         * @return QueryCondition
         */
        public function andCondition(QueryCondition $pCondition)
        {
            $condition = $pCondition->getWhere();
            if(!empty($condition))
                array_push($this->ands, "(".preg_replace("/^ WHERE /i","",$condition).")");
            return $this;
        }

        /**
         * Méthode d'ajout d'une instance existante d'une condition dans un 'OR' dans l'instance de la condition en cours
         * @param QueryCondition $pCondition
         * @return QueryCondition
         */
        public function orCondition(QueryCondition $pCondition)
        {
            array_push($this->or, "(".preg_replace("/^ WHERE /i","",$pCondition->getWhere()).")");
            return $this;
        }

        /**
         * Méthode d'ajout d'un 'ORDER BY'
         * @param String $pField
         * @param String $pType		ASC|DESC
         * @return QueryCondition
         */
        public function order($pField, $pType = "ASC")
        {
            if($this->order=="")
                $this->order = " ORDER BY ".$pField." ".$pType;
            else
                $this->order .= ", ".$pField." ".$pType;
            return $this;
        }

        /**
         * Méthode d'ajout d'une LIMIT
         * @param Int $pFirst
         * @param Int $pNumber
         * @return QueryCondition
         */
        public function limit($pFirst, $pNumber)
        {
            $this->limit = " LIMIT ".$pFirst.",".$pNumber;
            return $this;
        }

        /**
         * Méthode de génération de la condition
         * @return String
         */
        public function get()
        {
            return $this->getWhere().$this->group.$this->getHaving().$this->order.$this->limit;
        }

        /**
         * Méthode de génération de la section WHERE de l'instance de la condition en cours
         * @return string
         */
        public function getWhere()
        {
            $where = "";
            $ands = implode(" AND ", $this->ands);
            $or = implode(" OR ", $this->or);
            $existAnds = implode(" AND ", $this->existAnds);
            $existOr = implode(" OR ", $this->existOr);
            if(!empty($ands))
                $where .= " WHERE ".$ands;
            if(!empty($or))
            {
                if(empty($ands))
                    $where .= " WHERE ".$or;
                else
                    $where .= " OR ".$or;
            }
            if(!empty($existAnds))
            {
                if(empty($where))
                    $where .= " WHERE ".$existAnds;
                else
                    $where .= " AND ".$existAnds;
            }
            if(!empty($existOr))
            {
                if(empty($where))
                    $where .= " WHERE ".$existOr;
                else
                    $where .= " AND ".$existOr;
            }
            return $where;
        }

        /**
         * @return string
         */
        public function getHaving()
        {
            $having = "";
            $ands = implode(" AND ", $this->havingAnds);
            $or = implode(" OR ", $this->havingOr);
            if(!empty($ands))
                $having .= " HAVING ".$ands;
            if(!empty($or))
            {
                if(empty($ands))
                    $having .= " HAVING ".$or;
                else
                    $having .= " OR ".$or;
            }
            return $having;
        }
    }

    /**
     * @author Arnaud NICOLAS <arno06@gmail.com>
     * @version .1
     * @package db
     * @subpackage query
     */
    class QueryWithCondition extends BaseQuery
    {
        /**
         * @var QueryCondition
         */
        protected $condition;


        /**
         * Méthode de définition de la condition d'une requête SELECT
         * @param QueryCondition $pConditionInstance
         * @return QueryWithCondition
         */
        public function setCondition($pConditionInstance)
        {
            if(!$pConditionInstance instanceof QueryCondition)
                return $this;
            $this->condition = $pConditionInstance;
            return $this;
        }

        /**
         * @param $pField
         * @return QueryWithCondition
         */
        public function having($pField)
        {
            $this->getCondition()->andHaving($pField);
            return $this;
        }

        /**
         * @param $pField
         * @return QueryWithCondition
         */
        public function andHaving($pField)
        {
            $this->getCondition()->andHaving($pField);
            return $this;
        }

        /**
         * @param $pField
         * @return QueryWithCondition
         */
        public function orHaving($pField)
        {
            $this->getCondition()->orHaving($pField);
            return $this;
        }

        /**
         * Méthode d'ajout d'une condition 'WHERE' à la requête SELECT en cours
         * @param String $pField
         * @param String $pType
         * @param String $pValue
         * @param bool $pEscape
         * @return QueryWithCondition
         */
        public function where($pField, $pType, $pValue, $pEscape = true)
        {
            $this->getCondition()->andWhere($pField, $pType, $pValue, $pEscape);
            return $this;
        }

        /**
         * Méthode d'ajout d'une condition 'AND' à la requête SELECT en cours
         * @param String $pField
         * @param String $pType
         * @param String $pValue
         * @param bool $pEscape
         * @return QueryWithCondition
         */
        public function andWhere($pField, $pType, $pValue, $pEscape = true)
        {
            $this->getCondition()->andWhere($pField, $pType, $pValue, $pEscape);
            return $this;
        }

        /**
         * Méthode d'ajout d'une condition 'OR' à la requête SELECT en cours
         * @param String $pField
         * @param String $pType
         * @param String $pValue
         * @return QueryWithCondition
         */
        public function orWhere($pField, $pType, $pValue)
        {
            $this->getCondition()->orWhere($pField, $pType, $pValue);
            return $this;
        }

        /**
         * @param QuerySelect $pQuery
         * @return QueryWithCondition
         */
        public function andExists(QuerySelect $pQuery)
        {
            $this->getCondition()->andExists($pQuery);
            return $this;
        }

        /**
         * @param QuerySelect $pQuery
         * @return QueryWithCondition
         */
        public function orExists(QuerySelect $pQuery)
        {
            $this->getCondition()->orExists($pQuery);
            return $this;
        }

        /**
         * @param QuerySelect $pQuery
         * @return QueryWithCondition
         */
        public function andNotExists(QuerySelect $pQuery)
        {
            $this->getCondition()->andNotExists($pQuery);
            return $this;
        }

        /**
         * @param QuerySelect $pQuery
         * @return QueryWithCondition
         */
        public function orNotExists(QuerySelect $pQuery)
        {
            $this->getCondition()->orNotExists($pQuery);
            return $this;
        }


        /**
         * Méthode d'ajout d'une condition imbriquée dans la condition de la requête en cours
         * @param QueryCondition $pCondition
         * @return QueryWithCondition
         */
        public function andCondition(QueryCondition $pCondition)
        {
            $this->getCondition()->andCondition($pCondition);
            return $this;
        }


        /**
         * @param QueryCondition $pCondition
         * @return QueryWithCondition
         */
        public function orCondition(QueryCondition $pCondition)
        {
            $this->getCondition()->orCondition($pCondition);
            return $this;
        }


        /**
         * Méthode d'ajout d'un 'ORDER BY'
         * @param String $pField
         * @param String $pType		ASC|DESC
         * @return QueryWithCondition
         */
        public function order($pField, $pType = "ASC")
        {
            $this->getCondition()->order($pField, $pType);
            return $this;
        }


        /**
         * Méthode d'ajout d'une LIMIT
         * @param Int $pFirst
         * @param Int $pNumber
         * @return QueryWithCondition
         */
        public function limit($pFirst, $pNumber)
        {
            $this->getCondition()->limit($pFirst, $pNumber);
            return $this;
        }


        /**
         * Méthode d'ajout d'un 'GROUP BY'
         * @param String $pField
         * @return QueryWithCondition
         */
        public function groupBy($pField)
        {
            $this->getCondition()->groupBy($pField);
            return $this;
        }


        /**
         * @return QueryCondition
         */
        protected function getCondition()
        {
            if(!$this->condition)
                $this->condition = Query::condition();
            return $this->condition;
        }
    }

    /**
     * @author Arnaud NICOLAS <arno06@gmail.com>
     * @version .1
     * @package db
     * @subpackage query
     */
    class QuerySelect extends QueryWithCondition
    {
        /**
         * @var array
         */
        private $tables = array();
        /**
         * @var array
         */
        private $fields = array();
        /**
         * @var String
         */
        private $joins = "";

        /**
         * @var QuerySelect[]
         */
        private $query_union;

        /**
         * Constructor
         * @param String $pFields
         * @param String $pTables
         */
        public function __construct($pFields, $pTables)
        {
            $this->addFrom($pFields, $pTables);
        }

        /**
         * @param $pTable
         * @param string $pType
         * @param string $pOn
         * @return QuerySelect
         */
        public function join($pTable, $pType = " NATURAL JOIN ", $pOn = "")
        {
            if(!empty($pOn))
                $pOn = "ON ".$pOn;
            $this->joins .= $pType.$pTable." ".$pOn;
            return $this;
        }

        /**
         * Méthode d'ajout d'une table et de champs au SELECT en cours
         * @param String $pFields
         * @param String $pTables
         * @return QuerySelect
         */
        public function addFrom($pFields, $pTables)
        {
            if(!in_array($pTables, $this->tables))
                array_push($this->tables, $pTables);
            if(!in_array($pFields, $this->fields))
                array_push($this->fields, $pFields);
            return $this;
        }

        /**
         * @param QuerySelect $pQuery
         * @return QuerySelect
         */
        public function union(QuerySelect $pQuery)
        {
            if(!isset($this->query_union))
                $this->query_union = array();
            $this->query_union[] = $pQuery;
            return $this;
        }

        /**
         * Méthode de génération de la requête
         * @param Boolean $pSemicolon
         * @return String
         */
        public function get($pSemicolon = true)
        {
            $field = implode(",", $this->fields);
            $table = implode(",", $this->tables);
            $joins = $this->joins." ";
            $condition = $this->getCondition()->get();
            $union = "";
            if(isset($this->query_union)&&!empty($this->query_union))
            {
                foreach($this->query_union as $q)
                    $union .= " UNION ".preg_replace("/;$/", "", $q->get());
            }
            $str = "SELECT " . $field . " FROM " . $table . " " . $joins . $condition . $union;
            if ($pSemicolon) $str .= ";";
            return $str;
        }

        /**
         * @param string $pHandler
         * @param bool   $pRaw
         * @return array|resource
         */
        public function execute($pHandler = "default", $pRaw = false)
        {
            $result = Query::execute($this->get(), $pHandler, $pRaw);
            if (Configuration::$global_explainOnSelect === true)
                $this->explain($pHandler);
            return $result;
        }

        /**
         * @Author Alain LEE - alee@cbi-multimedia.com
         * @param string $pHandler
         * @return array|resource
         */
        public function explain($pHandler = "default")
        {
            $query = $this->get();
            $result = Query::execute("EXPLAIN ".$query, $pHandler);
            $useKey = true;
            $useTemporary = false;
            $useFileSort = false;
            $tableWithNoIndex = array();
            $errors = array();
            if (is_array($result))
                foreach($result as $row)
                {
                    if (!empty($row['type']) && $row['type'] == "ALL" && $row['select_type'] != "UNION RESULT")
                    {
                        $tableWithNoIndex[] = $row['table'];
                        $useKey = false;
                    }
                    if (!empty($row['Extra']))
                    {
                        if (stripos($row['Extra'], "Using temporary") !== false)
                            $useTemporary = true;
                        if (stripos($row['Extra'], "Using filesort") !== false)
                            $useFileSort = true;
                    }
                }

            if ((sizeof($result) > 1 || stripos($query, " WHERE ") !== false) && !$useKey)
                foreach($tableWithNoIndex as $t)
                    $errors[] = "La table <b>".$t."</b> n'utilise pas d'index";

            if ($useTemporary)
                $errors[] = "Utilisation d'une table temporaire";

            if ($useFileSort)
                $errors[] = "Un tri nécessite un deuxi&egrave;me passage dans les résultats et peut ralentir la requête";

            if (!empty($errors))
            {
                $displayError = "La requête \"<b>".$query."</b>\" peut présenter des lenteurs :";
                foreach($errors as $e)
                    $displayError .= "<br/>- ".$e;
                trigger_error($displayError, E_USER_WARNING);
            }

            return $result;
        }
    }

    /**
     * @Author Alain LEE - alee@cbi-multimedia.com
     * @version 0.1
     * @package db
     * @subpackage query
     */
    class QueryReplace extends QueryInsert
    {
        /**
         * Méthode de génération de la requête
         * @return String
         */
        public function get()
        {
            $values = implode(",", $this->values);
            return 'REPLACE INTO '.$this->table.' '.$this->fields.' VALUES '.$values.';';
        }
    }

    /**
     * @author Arnaud NICOLAS <arno06@gmail.com>
     * @version .1
     * @package db
     * @subpackage query
     */
    class QueryUpdate extends QueryWithCondition
    {
        /**
         * Tableau des valeurs à mettre-à-jour
         * @var array
         */
        private $values = array();

        /**
         * Méthode de définition des champs à mettre-à-jour
         * @param array $pValues
         * @param bool $pEscape
         * @return QueryUpdate
         */
        public function values($pValues, $pEscape = true)
        {
            foreach($pValues as $field=>$value)
                array_push($this->values, $field."=".Query::escapeValue($value, $pEscape));
            return $this;
        }

        /**
         * Méthode de génération de la méthode
         * @return String
         */
        public function get()
        {
            $values = implode(",", $this->values);
            $condition = $this->getCondition()->get();
            return "UPDATE ".$this->table." SET ".$values.$condition.";";
        }
    }

    /**
     * @author Arnaud NICOLAS <arno06@gmail.com>
     * @version .1
     * @package db
     * @subpackage query
     */
    class QueryInsert extends BaseQuery
    {
        /**
         * @var String
         */
        const UNIQUE = "UNIQUE";
        /**
         * @var String
         */
        const MULTIPLE = "MULTIPLE";

        /**
         * Chaine de caract&egrave;res des champs de la table à remplir
         * @var String
         */
        protected $fields = "";
        /**
         * Tableau de chaines de caract&egrave;res des valeurs à insérer
         * @var array
         */
        protected $values = array();

        /**
         * Constructor
         * @param array $pValues
         * @param String $pType
         */
        public function __construct($pValues, $pType = "")
        {
            switch($pType)
            {
                case QueryInsert::MULTIPLE:
                    $this->setFields($pValues[0]);
                    $this->setValues($pValues);
                    break;
                case QueryInsert::UNIQUE:
                default:
                    $this->setFields($pValues);
                    $this->setValues(array($pValues));
                    break;
            }
        }

        /**
         * Méthode de définition des champs de la table en fonction des clés du tableau de valeurs envoyées
         * @param array $pTuple
         * @return void
         */
        private function setFields($pTuple)
        {
            $f = array();
            foreach($pTuple as $field=>$value)
                array_push($f, $field);
            $this->fields = "(".implode(",", $f).")";
        }

        /**
         * Méthode de définition et d'échappement des valeurs à insérer
         * @param array $pTuples
         * @return void
         */
        private function setValues($pTuples)
        {
            $this->values = array();
            for($i = 0, $max = count($pTuples); $i<$max; $i++)
            {
                $pTuples[$i] = array_map("core\\db\\Query::escapeValue", $pTuples[$i]);
                array_push($this->values, "(".implode(",", $pTuples[$i]).")");
            }
        }

        /**
         * Méthode de définition du nom de la table dans laquelle insérer les valeurs
         * @param String $pTable
         * @return QueryInsert
         */
        public function into($pTable)
        {
            $this->table = $pTable;
            return $this;
        }

        /**
         * Méthode de génération de la requête
         * @return String
         */
        public function get()
        {
            $values = implode(",", $this->values);
            return "INSERT INTO ".$this->table." ".$this->fields." VALUES ".$values.";";
        }
    }

    /**
     * @author Arnaud NICOLAS <arno06@gmail.com>
     * @version .1
     * @package db
     * @subpackage query
     */
    class QueryDelete extends QueryWithCondition
    {

        /**
         * Constructor
         */
        public function __construct(){}

        /**
         * Méthode de définition de la table à cibler pour la suppression
         * @param String $pTable
         * @return QueryDelete
         */
        public function from($pTable)
        {
            $this->table = $pTable;
            return $this;
        }

        /**
         * Méthode de génération de la requête
         * @return String
         */
        public function get()
        {
            $condition = $this->getCondition()->get();
            return "DELETE FROM `".$this->table."`".$condition.";";
        }
    }


    class QueryTruncate extends BaseQuery
    {
        /**
         * Méthode de génération de la requête
         * @return String
         */
        public function get()
        {
            return "TRUNCATE TABLE '".$this->table."';";
        }
    }


    class QueryDrop extends BaseQuery
    {
        /**
         * Méthode de génération de la requête
         * @return String
         */
        public function get()
        {
            return "DROP TABLE `".$this->table."';";
        }
    }

    class QueryCreate extends QueryStructure
    {
        /**
         * @param string $pTable
         * @param $pStorageEngine
         * @param $pCollation
         */
        public function __construct($pTable, $pStorageEngine, $pCollation)
        {
            parent::__construct($pTable);
            $this->storage_engine = $pStorageEngine;
            $this->collation = $pCollation;
        }

        /**
         * @return string
         */
        public function get()
        {
            $query = "CREATE TABLE IF NOT EXISTS `".$this->table."` (";
            if(!empty($this->primary))
                $this->add_fields[] = "PRIMARY KEY (".implode(", ",$this->primary).")";
            $query.= implode(", ", $this->add_fields);
            $char = explode("_", $this->collation);
            $query .= ") ENGINE=".$this->storage_engine." CHARACTER SET ".$char[0]." COLLATE ".$this->collation;
            if($this->hasAI)
                $query .= " AUTO_INCREMENT=1";
            $query .= ";";
            return $query;
        }
    }

    class QueryAlter extends QueryStructure
    {
        /**
         * @var string
         */
        private $change_fields = "";

        /**
         * @var array
         */
        private $removed_fields = [];

        /**
         * @param $pName
         * @param $pType
         * @param string $pSize
         * @param string $pDefaultValue
         * @param bool $pNull
         * @param bool $pAI
         * @param string $pIndex
         * @param string $pComments
         * @param string $pWhere
         * @return $this|QueryStructure
         * @throws Exception
         */
        public function addField($pName, $pType, $pSize = "", $pDefaultValue = "", $pNull = false, $pAI = false, $pIndex = "", $pComments = "", $pWhere = "")
        {
            if(!empty($this->change_fields))
                throw new Exception("Impossible de faire appel à la commande SQL ADD lorsque la commande CHANGE est définie");
            parent::addField($pName, $pType, $pSize, $pDefaultValue, $pNull, $pAI, $pIndex, $pComments, $pWhere);
            return $this;
        }

        /**
         * @param $pName
         * @param $pType
         * @param string $pSize
         * @param string $pDefaultValue
         * @param bool $pNull
         * @param bool $pAI
         * @param string $pIndex
         * @param string $pComments
         * @return $this
         * @throws Exception
         */
        public function changeField($pName, $pType, $pSize = "", $pDefaultValue = "", $pNull = false, $pAI = false, $pIndex = "", $pComments = "")
        {
            if(!empty($this->change_fields))
                throw new Exception("Impossible de faire appel à la commande SQL CHANGE sur plusieurs champs simultamément");
            if(!empty($this->add_fields))
                throw new Exception("Impossible de faire appel à la commande SQL CHANGE lorsque la commande ADD est définie");
            $this->change_fields = "`".$pName."` ".$this->setupField($pName, $pType, $pSize, $pDefaultValue, $pNull, $pAI, $pIndex, $pComments);
            return $this;
        }

        /**
         * @param $pName
         * @return $this
         */
        public function removeField($pName){
            $this->removed_fields[] = "DROP COLUMN ".$pName;
            return $this;
        }

        /**
         * @return string
         */
        public function get()
        {
            $add = "";
            if(!empty($this->add_fields))
                $add = " ADD ".implode(", ", $this->add_fields);
            $change = "";
            if(!empty($this->change_fields))
                $change = " CHANGE ".$this->change_fields;
            $remove = "";
            if(!empty($this->removed_fields)){
                $remove = " ".implode(', ', $this->removed_fields);
            }
            return "ALTER TABLE `".$this->table."`".$add.$change.$remove.";";
        }
    }

    class QueryStructure extends BaseQuery
    {
        /**
         * @var array
         */
        protected $add_fields = array();

        /**
         * @var string
         */
        protected $storage_engine = "";

        /**
         * @var string
         */
        protected $collation = "";

        /**
         * @var array
         */
        protected $primary = array();

        /**
         * @var bool
         */
        protected $hasAI = false;

        /**
         * @param $pName
         * @param $pType
         * @param string $pSize
         * @param string $pDefaultValue
         * @param bool $pNull
         * @param bool $pAI
         * @param string $pIndex
         * @param string $pComments
         * @param string $pWhere
         * @return QueryStructure
         */
        public function addField($pName, $pType, $pSize = "", $pDefaultValue = "", $pNull = false, $pAI = false, $pIndex = "", $pComments = "", $pWhere = "")
        {
            $this->add_fields[] = $this->setupField($pName, $pType, $pSize, $pDefaultValue, $pNull, $pAI, $pIndex, $pComments, $pWhere);
            return $this;
        }

        /**
         * @throws Exception
         * @param $pName
         * @param $pType
         * @param string $pSize
         * @param string $pDefaultValue
         * @param bool $pNull
         * @param bool $pAI
         * @param string $pIndex
         * @param string $pComments
         * @param string $pWhere
         * @return string
         */
        protected function setupField($pName, $pType, $pSize = "", $pDefaultValue = "", $pNull = false, $pAI = false, $pIndex = "", $pComments = "", $pWhere = "")
        {
            $field_prop = array("`".$pName."`");
            if(preg_match('/(varchar)$/i', $pType) &&(empty($pSize) || !$pSize))
                throw new Exception("Le type 'varchar' requiert la définition d'une taille de champ.");
            if(preg_match("/(int|varchar)$/i", $pType) && !preg_match('/\([0-9]+\)/', $pType))
            {
                if(!$pSize)
                    $pSize = 11;
                $pType .= "(".$pSize.")";
            }
            $field_prop[] = $pType;
            if(!$pNull)
                $field_prop[] = "NOT NULL";
            else
                $field_prop[] = "NULL";
            if(!empty($pDefaultValue))
                $field_prop[] = "DEFAULT '".$pDefaultValue."'";
            if($pAI)
            {
                if($this->hasAI)
                    throw new Exception("Une table ne peut contenir qu'un seul champ avec incrémentation automatique.");
                $this->hasAI = true;
                $field_prop[] = "AUTO_INCREMENT";
            }
            if(!empty($pIndex)&&strtoupper($pIndex)!="PRIMARY")
                $field_prop[] = $pIndex;
            if(strtoupper($pIndex)=="PRIMARY")
                $this->primary[] = "`".$pName."`";

            if(!empty($pComments))
                $field_prop[] = "COMMENT '".$pComments."'";
            if(!empty($pWhere))
                $field_prop[] = $pWhere;
            return implode(" ", $field_prop);
        }
    }
}
