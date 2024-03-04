<?php
namespace core\application
{
	use core\utils\Stack;

	/**
	 * Class Dictionary - Permet la gestion d'un fichier de langue global à l'application
	 *
	 * @author Arnaud NICOLAS <arno06@gmail.com>
	 * @version 1.0
	 * @package application
	 */
	class Dictionary extends Singleton
	{
		/**
		 * Tableau des alias
		 * @var array
		 */
		private $table_alias;

		/**
		 * Tableau des termes
		 * @var array
		 */
		private $table_terms;

		/**
		 * Tableau des infos de SEO (Search Engine Optimisation)
		 * @var array
		 */
		private $table_seo;

		/**
		 * Variable de la langue en cours
		 * @var String
		 */
		static public $langue;

		/**
		 * Undefined data
		 */
		const UNDEFINED = "Undefined";

		/**
		 * @param  $pInstance
		 */
		public function __construct($pInstance)
		{
			if(!$pInstance instanceOf PrivateClass)
				trigger_error("Il est interdit d'instancier un objet de type <i>Singleton</i> - Merci d'utiliser la méthode static <i>".__CLASS__."::getInstance()</i>", E_USER_ERROR);
		}

		/**
		 * Méthode de récupération d'un terme se trouvant dans le fichier de langue
		 * Le paramètre attendu correspond à la concaténation des différents identifiants de niveau d'accès
		 * @param string $pId
		 * @return String
		 */
		static public function term($pId)
		{
			$i = self::getInstance();
			$value = Stack::get($pId, $i->table_terms);
            if(!$value){
                return self::UNDEFINED;
            }
            $re = "/\{([a-z\.]+)\}/";
			while(preg_match($re, $value, $matches))
			{
				$value = str_replace($matches[0], self::term($matches[1]), $value);
			}
			if(empty($value))
				$value = self::UNDEFINED;
			return $value;
		}


		/**
		 * Méthode de récupération de l'ensemble des termes disponibles via le fichier de langue
		 * @return array
		 */
		static public function terms()
		{
			$i = self::getInstance();
			return $i->table_terms;
		}


		/**
		 * Méthode de récupération des informations de SEO pour un controller et un action donné
		 * @param String $pController		Nom du controller
		 * @param String $pAction			Nom de l'action
		 * @return array
		 */
		static public function seoInfos($pController, $pAction)
		{
			$i = self::getInstance();
			if(!isset($i->table_seo[$pController])||!isset($i->table_seo[$pController][$pAction]))
				return null;
			return $i->table_seo[$pController][$pAction];
		}

		/**
		 * Méthode de récupération du vrai nom d'un controller ou d'une action à partir de son alias dans la langue en cours
		 * Renvoi la valeur du controller tel qu'il existe
		 * @param String $pValue		Valeur de l'alias
		 * @return String
		 */
		static public function getAliasFrom($pValue)
		{
			$i = self::getInstance();
			if(isset($i->table_alias[$pValue]))
				return $i->table_alias[$pValue];
			return $pValue;
		}

		/**
		 * Méthode de récupération de l'alias dans la langue actuelle pour un controller ou une action
		 * @param String $pValue		Valeur dont on souhaite récupérer l'alias
		 * @return String
		 */
		static public function getAliasFor($pValue)
		{
			$i = self::getInstance();
			if($return = array_search($pValue, $i->table_alias))
				return $return;
			return $pValue;
		}

		/**
		 * Méthode de définition de l'objet Dictionary en fonction des paramètres
		 * @param String $pLanguage		Langue en cours - fr/en/de
		 * @param array $pTerms			Tableau des termes accessibles de manière global à l'application
		 * @param array $pSeo			Tableau des informations relatives à la SEO (balise "title" et "description")
		 * @param array $pAlias			Tableau des alias pour la gestion de la réécriture d'url dynamique
		 * @return void
		 */
		static public function defineLanguage($pLanguage, array $pTerms, array $pSeo, array $pAlias)
		{
			if(empty($pLanguage))
				trigger_error("Impossible de définir le <b>Dictionary</b>, <b>langue</b> non renseignée.", E_USER_ERROR);
			self::$langue = $pLanguage;
			$i = self::getInstance();
			$i->table_alias = $pAlias;
			$i->table_terms =$pTerms;
			$i->table_seo = $pSeo;
		}
	}
}
