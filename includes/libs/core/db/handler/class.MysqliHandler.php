<?php
namespace core\db\handler
{

    use core\db\InterfaceDatabaseHandler;
    use core\tools\debugger\Debugger;
    use mysqli_result;
    use mysqli;

    /**
     * Couche d'abstraction à la base de données (type mysql improved)
     *
     * @author Arnaud NICOLAS <arno06@gmail.com>
     * @version 1.2
     * @package core\db\handler
     */
    class MysqliHandler implements InterfaceDatabaseHandler
    {
        /**
         * @var string[]
         */
        static private $specials = array(
            "NOW()",
            "NULL"
        );

        /**
         * Chemin d'acc&egrave;s à la base de données
         * @var String
         */
        protected $host;


        /**
         * Nom d'utilisateur
         * @var String
         */
        protected $user;


        /**
         * Mot de passe d'acc&egrave;s à la base de données
         * @var String
         */
        protected $mdp;


        /**
         * Nom de la base de données
         * @var String
         */
        protected $bdd;


        /**
         * Instance mysqli
         * @var mysqli
         */
        private $mysqliInstance;


        /**
         * @param $pHost
         * @param $pUser
         * @param $pPassword
         * @param $pName
         */
        public function __construct($pHost, $pUser, $pPassword, $pName)
        {
            $this->host = $pHost;
            $this->user = $pUser;
            $this->mdp = $pPassword;
            $this->bdd = $pName;
            $this->connect();
        }

        public function __destruct()
        {
            $this->close();
        }


        /**
         *
         */
        protected function close()
        {
            if($this->mysqliInstance->connect_error)
                return;
            try{
                $store_result = $this->mysqliInstance->store_result();
                if($store_result)
                    $store_result->free();
            }catch(\Exception $e){}
            $this->mysqliInstance->close();
        }


        /**
         *
         */
        protected function connect()
        {
            $this->mysqliInstance = new mysqli($this->host, $this->user, $this->mdp, $this->bdd);
            if($this->mysqliInstance->connect_error)
                trigger_error("Connexion au serveur de gestion de base de données impossible (".$this->bdd.")", E_USER_ERROR);
        }


        /**
         * @return string
         */
        public function getError()
        {
            return $this->mysqliInstance->error;
        }


        /**
         * @return int
         */
        public function getErrorNumber()
        {
            return $this->mysqliInstance->errno;
        }


        /**
         * Méthode de récupération de la clé primaire générée à la suite d'une insertion
         * @return Number
         */
        public function getInsertId()
        {
            return $this->mysqliInstance->insert_id;
        }


        /**
         * Méthode permettant de centraliser les commandes à effectuer avant l'excécution d'une requête
         * @param String $pQuery				Requête à excécuter
         * @param bool   $pRaw
         * @return array|bool|mysqli_result
         */
        public function execute($pQuery, $pRaw = false)
        {
            Debugger::query($pQuery, "db", $this->bdd);
            try{
                $result = $this->mysqliInstance->query($pQuery);
            }
            catch(\Exception $e){
                $result = false;
            }
            if(!$result){
                trigger_error("Une erreur est apparue lors de la requête <b>".$pQuery."</b><br/><a href='https://www.google.com/search?q=mysql+error+".$this->getErrorNumber()."' target='_blank'>Error ".$this->getErrorNumber()."</a> : <i>".$this->getError()."</i>", E_USER_WARNING);
                return false;
            }
            if($pRaw){
                return $result;
            }
            $return = array();
            while($data = $result->fetch_assoc())
            {
                $return[] = $data;
            }
            $result->free();
            return $return;
        }


        /**
         * ToString()
         * @return String
         */
        public function __toString()
        {
            return '[Object MysqliHandler database="'.$this->bdd.'" user="'.$this->user.'"]';
        }


        /**
         * @param string $pString
         * @return string
         */
        public function escapeValue($pString)
        {
            if(!in_array(strtoupper($pString), self::$specials))
                return "'".$this->mysqliInstance->escape_string($pString)."'";
            return strtoupper($pString);
        }
    }
}