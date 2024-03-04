<?php
namespace core\data
{
	/**
	 * Interface permettant la mise en place de différent format de données
	 *
	 * @author Arnaud NICOLAS <arno06@gmail.com>
	 * @version .1
	 * @package data
	 */
	interface InterfaceData
	{

		/**
		 * Méthode d'encodage d'un tableau en données formatées vers le format spécifique
		 * @param array $pArray		Tableau des données
		 * @return String
		 */
		static function encode(array $pArray);

		/**
		 * Méthode de récupération d'un tableau associatif multidimensionnel &agrave; partir d'une chaine de caract&egrave;res
		 * @param String $pString		Contenu au format spécifique
		 * @return array
		 */
		static function decode($pString);

		/**
		 * Méthode de chargement de décodage d'un fichier au format spécifique
		 * @param String $pFile
		 * @return array
		 */
		static function import($pFile);
	}
}
