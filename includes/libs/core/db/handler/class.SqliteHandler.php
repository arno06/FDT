<?php
namespace core\db\handler
{
	use core\db\InterfaceDatabaseHandler;
	use core\tools\debugger\Debugger;
	use \SQLite3;
	use \SQLite3Result;

	/**
	 * Couche d'abstraction à la base de données (type sqlite)
	 *
	 * @author Arnaud NICOLAS <arno06@gmail.com>
	 * @version .4
	 * @package core\db\handler
	 */
	class SqliteHandler implements InterfaceDatabaseHandler
	{
        /**
         * @var array
         */
        static private $specials = array(
            "NOW()",
            "NULL"
        );

		/**
		 * Instance SQLite3 - natif php5
		 * @var SQLite3
		 */
		protected $sqlite;

		/**
		 * Chemin d'accès à la base de données
		 * @var String
		 */
		protected $host;

		/**
		 * Nom d'utilisateur
		 * @var String
		 */
		protected $user;

		/**
		 * Mot de passe d'accès à la base de données
		 * @var String
		 */
		protected $mdp;

		/**
		 * Nom de la base de données
		 * @var String
		 */
		protected $bdd;

		/**
		 * @var Int
		 */
		public $lastId;


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

		/**
		 * Destructor
		 * Clos la connexion en cours avec la base
		 * @return void
		 */
		public function __destruct()
		{
			$this->close();
		}

		/**
		 * Méthode de connexion à la base
		 * Stop l'exécution de l'application si la base n'est pas accessible
		 * @return void
		 */
		protected function connect()
		{
			if(!$this->sqlite = new SQLite3($this->host, SQLITE3_OPEN_READWRITE))
				trigger_error("Connexion au serveur de gestion de base de données impossible", E_USER_ERROR);
		}

		/**
		 * Méthode permettant de centraliser les commandes à effectuer avant l'excécution d'une requête
		 * @param String $pQuery				Requête à excécuter
         * @param bool   $pRaw
		 * @return resource
		 */
		public function execute($pQuery, $pRaw = false)
		{
			Debugger::query($pQuery, "db", $this->bdd);
            if($pRaw){
                return $this->sqlite->exec($pQuery);
            }
            $result = $this->sqlite->query($pQuery);
            if(!$result){
                trigger_error("Une erreur est apparue lors de la requête <b>".$pQuery."</b>", E_USER_WARNING);
                return false;
            }
            $return = array();
            while($data = $result->fetchArray(SQLITE3_ASSOC))
            {
                array_push($return, $data);
            }
            return $return;
		}

		/**
		 * Méthode de récupération de la clé primaire générée à la suite d'une insertion
		 * @return Number
		 */
		public function getInsertId()
		{
			return $this->sqlite->lastInsertRowID();
		}

		/**
		 * Méthode permettant de clore la connexion établie avec la base de données
		 * @return void
		 **/
		protected function close()
		{
			$this->sqlite->close();
		}

		/**
		 * toString()
		 * @return String
		 */
		public function toString()
		{
			return "Object SqliteHandler";
		}

		/**
		 * @return int
		 */
		public function getErrorNumber()
		{
            trigger_error("SqliteHandler::getErrorNumber not implemented yet.", E_USER_WARNING);
		}

		/**
		 * @return string
		 */
		public function getError()
		{
            trigger_error("SqliteHandler::getError not implemented yet.", E_USER_WARNING);
		}

        /**
         * Méthode d'échappement de la valeur
         * @param string $pString
         * @return string
         */
        public function escapeValue($pString)
        {
            if(!in_array(strtoupper($pString), self::$specials))
                return "'".SQLite3::escapeString($pString)."'";
            return strtoupper($pString);
        }
    }

}
