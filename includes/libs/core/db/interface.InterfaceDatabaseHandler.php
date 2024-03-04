<?php
namespace core\db
{

	/**
	 * Interface pour les gestionnaires de base de données
	 *
	 * @author Arnaud NICOLAS <arno06@gmail.com>
	 * @version .5
	 * @package db
	 */
	interface InterfaceDatabaseHandler
	{
		/**
		 * Méthode d'execution d'une Requêtes SQL
		 * @param String $pQuery				Requêtes SQL brute
         * @param bool   $pRaw                  Détermine si le gestionnaire doit renvoyer un tableau ou le résultat brute
		 * @return bool|array|resource
		 */
		public function execute($pQuery, $pRaw = false);

		/**
		 * Méthode de récupération de lé clé primaire venant d'être générée par la base de données
		 * @return int
		 */
		public function getInsertId();

		/**
		 * @abstract
		 * @return int
		 */
		public function getErrorNumber();

		/**
		 * @abstract
		 * @return string
		 */
		public function getError();

        /**
         * Méthode d'échappement des caractères spéciaux
         * @param string $pString
         * @return string
         */
        public function escapeValue($pString);
	}
}
