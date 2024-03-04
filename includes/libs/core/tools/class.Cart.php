<?php
namespace core\tools
{
	use core\application\Singleton;
	use core\application\PrivateClass;

	/**
	 * Class Cart Permet de gérer un panier - cas d'une boutique en ligne
	 *
	 * @author Arnaud NICOLAS <arno06@gmail.com>
	 * @version .2
	 * @package core\tools
	 */
	class Cart extends Singleton
	{
		/**
		 * Nom de la variable de session gérant les items du panier
		 * @var String
		 */
		const SESSION_VAR_NAME = "cbi_panier";


		/**
		 * Constructor
		 * @param $pInstance
		 */
		public function __construct($pInstance)
		{
			if(!$pInstance instanceOf PrivateClass)
				trigger_error("Il est interdit d'instancier un objet de type <i>Singleton</i> - Merci d'utiliser la méthode static <i>".__CLASS__."::getInstance()</i>", E_USER_ERROR);
			if(!$_SESSION[self::SESSION_VAR_NAME]||!is_array($_SESSION[self::SESSION_VAR_NAME]))
				$_SESSION[self::SESSION_VAR_NAME] = array();
		}

		/**
		 * Méthode d'ajout d'un item au panier
		 * @param mixed $pId							Identifiant unique
		 * @param int $pPrice						Prix unitaire
		 * @param int $pQuantity						Quantité d'item de ce type
		 * @return void
		 */
		public function add($pId, $pPrice, $pQuantity=1, $pProperty=null)
		{
			if(!$pId || !$pPrice || $pQuantity<=0)
				return;
			if($_SESSION[self::SESSION_VAR_NAME][$pId])
			{
				$_SESSION[self::SESSION_VAR_NAME][$pId]["quantity"] += $pQuantity;
				$_SESSION[self::SESSION_VAR_NAME][$pId]["total"] = $pPrice * $_SESSION[self::SESSION_VAR_NAME][$pId]["quantity"];
			}
			else
				$_SESSION[self::SESSION_VAR_NAME][$pId] = array("quantity"=>$pQuantity, "price"=>$pPrice, "property"=>$pProperty, "total"=>($pPrice*$pQuantity));
		}

		/**
		 * Méthode de mise-é-jour de la quantité souhaitée pour un item donnée
		 * @param mixed $pId			Identifiant unique
		 * @param object $pQuantity		Nouvelle quantité souhaité
		 * @return void
		 */
		public function updateQuantity($pId, $pQuantity)
		{
			if(!$_SESSION[self::SESSION_VAR_NAME][$pId])
				return;
			$_SESSION[self::SESSION_VAR_NAME][$pId]["quantity"] = $pQuantity;
			$_SESSION[self::SESSION_VAR_NAME][$pId]["total"] = $pQuantity * $_SESSION[self::SESSION_VAR_NAME][$pId]['price'];
		}

		/**
		 * Méthode de suppression d'un item du panier
		 * @param mixed $pId					Identifiant unique
		 * @return void
		 */
		public function remove($pId)
		{
			unset($_SESSION[self::SESSION_VAR_NAME][$pId]);
		}

		/**
		 * Méthode de ré-initialisation du Panier
		 * @return void
		 */
		public function trash()
		{
			unset($_SESSION[self::SESSION_VAR_NAME]);
			$_SESSION[self::SESSION_VAR_NAME] = array();
		}

		/**
		 * Méthode de récupération des informations du panier
		 * Renvoie un tableau associatif : array("estimation"=>x, "countItems"=>y);
		 * @return array
		 */
		public function getResume()
		{
			return array("estimation"=>$this->getEstimation(),
				"countItems"=>$this->getCountItems());
		}

		/**
		 * Méthode de récupération du tableau des items se trouvant dans le panier
		 * @return array
		 */
		public function getItems()
		{
			return $_SESSION[self::SESSION_VAR_NAME];
		}

		/**
		 * Méthode d'estimation de la valeur du panier
		 * @return Number
		 */
		private function getEstimation()
		{
			$estimation = 0;
			$max = count($_SESSION[self::SESSION_VAR_NAME]);
			if(!$max)
				return $estimation;
			foreach($_SESSION[self::SESSION_VAR_NAME] as $id=>$datas)
				$estimation += $datas["quantity"] * $datas["price"];
			return $estimation;
		}

		/**
		 * Méthode de récupération du nombre d'items ajoutés au panier
		 * @return Number
		 */
		private function getCountItems()
		{
			$count = 0;
			$max = count($_SESSION[self::SESSION_VAR_NAME]);
			if(!$max)
				return $count;
			foreach($_SESSION[self::SESSION_VAR_NAME] as $id=>$datas)
				$count += $datas["quantity"];
			return $count;
		}

        /**
         * Méthode de récupération de quantité pour un produit particulier
         * @param $pId
         * @return mixed
         */
		static public function getQuantityById($pId)
		{
			return $_SESSION[self::SESSION_VAR_NAME][$pId]['quantity'];
		}
	}
}
