<?php
namespace core\db
{
    use core\application\Configuration;
    use Exception;
	/**
	 * @author Arnaud NICOLAS <arno06@gmail.com>
	 * @version .1
	 * @package db
	 */
	class DBManager
	{
		/**
		 * @var array
		 */
		static private $handlers = array();

		/**
		 * @static
		 * @param string $pName
		 * @return InterfaceDatabaseHandler
		 */
		static public function get($pName = "default")
		{
			if(!array_key_exists($pName, self::$handlers))
			{
                if(!array_key_exists($pName, Configuration::$db))
                {
                    trigger_error("L'identifiant \"".$pName."\" ne correspond &agrave; aucun gestionnaire stocké.");
                    return null;
                }
                self::set($pName, Configuration::$db[$pName]);
			}
			return self::$handlers[$pName];
		}

		/**
		 * @static
		 * @param $pName
		 * @param $pInfo
		 */
		static public function set($pName, $pInfo)
		{
			if(isset(self::$handlers[$pName]))
				trigger_error("L'identifiant \"".$pName."\" est déj&agrave; utilisé. Impossible de stocker le gestionnaire créé.");
			$d = array("handler", "host", "user", "password", "name");
			foreach($d as $l)
			{
				if(!isset($pInfo[$l]))
					$pInfo[$l] = "";
			}
			try
			{
				$instance = new $pInfo["handler"]($pInfo["host"], $pInfo["user"], $pInfo["password"], $pInfo["name"]);
			}
			catch(Exception $e)
			{
				trigger_error("Une erreur est apparue lors de l'initialisation du gestionnaire \"".$pName."\". Merci de vérifier les informations saisie.", E_USER_ERROR);
				return;
			}
			self::$handlers[$pName] = $instance;
		}

		/**
		 * @static
		 * @return void
		 */
		static public function dispose()
		{
			foreach(self::$handlers as $name=>$instance)
			{
				unset($instance);
				unset(self::$handlers[$name]);
			}
			self::$handlers = null;
		}
	}
}
