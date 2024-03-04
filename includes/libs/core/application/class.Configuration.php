<?php
namespace core\application
{

    use core\utils\Stack;

    /**
	 * Class Configuration
	 * Sert de référence aux propriétés de configuration du framework
	 *
	 * @author Arnaud NICOLAS <arno06@gmail.com>
	 * @version 1.1
	 * @package application
	 */
	abstract class Configuration
	{
		/**
         * Définit les applications disponibles ainsi que leurs configurations associées
		 * @var array
		 */
		static public $applications;

		/**
		 * @var string
		 */
		static public $global_encoding = "UTF-8";

		/**
		 * Définit si Query génère automatiquement des requêtes Explain sur les Select
		 * @var bool
		 */
		static public $global_explainOnSelect = true;

        /**
		 * Définit l'email de contact du site
		 * @var string
		 */
		static public $global_emailContact = "";

		/**
		 * Nom attribué à la session de l'application
		 * @var String
		 */
		static public $global_session = "fw_php";

		/**
		 * Tableau des permissions disponibles sur le site
		 * @var array
		 */
		static public $global_permissions = array();

        /**
         * @var bool
         */
        static public $global_debug = false;

		/**
		 * Domaine du serveur
		 * @var string
		 */
		static public $server_domain;

		/**
		 * Dossier de base dans lequel se trouve le framework
		 * @var string
		 */
		static public $server_folder;

		/**
		 * URL du serveur (concaténation du domaine et du dossier)
		 * @var string
		 */
		static public $server_url;

		/**
		 * Définit l'adresse du serveur smtp
		 * @var string
		 */
		static public $server_smtp = "";

		/**
		 * Stock les informations des SGBD
		 * @var array
		 */
		static public $db = array(
			"default"=>array(
				"host"=>"localhost",
				"user"=>"root",
				"password"=>"",
				"name"=>"fwphp",
				"handler"=>"\\core\\data\\handler\\MysqliHandler"
			)
		);

		/**
		 * @var string
		 */
		static public $authentication_tableName = "%s_users";

		/**
		 * @var string
		 */
		static public $authentication_tableId = "id_user";

		/**
		 * @var string
		 */
		static public $authentication_fieldPassword = "password_user";

		/**
		 * @var string
		 */
		static public $authentication_fieldLogin = "login_user";

		/**
		 * @var string
		 */
		static public $authentication_fieldPermissions = "permissions_user";

        /**
         * @var array
         */
        static private $_extra;

        /**
         * @param array $pExtra
         */
        static public function setExtra($pExtra)
        {
            self::$_extra = $pExtra;
        }

        /**
         * @param string $pId
         * @return mixed
         */
        static public function extra($pId)
        {
            return Stack::get($pId, self::$_extra);
        }
	}
}
