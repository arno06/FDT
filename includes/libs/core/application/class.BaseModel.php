<?php
namespace core\application
{
	use core\db\Query;
	use core\db\DBManager;
	use core\db\QuerySelect;
	use core\db\QueryCondition;
    use core\models\InterfaceBackModel;

    /**
	 * Class devant servir de model de base pour l'ensemble des models de l'application
	 *
	 * @author Arnaud NICOLAS <arno06@gmail.com>
	 * @version .5
	 * @package application
	 */
	class BaseModel implements InterfaceBackModel
	{
		/**
		 * Nom du champs servant de clé primaire
		 * @var String
		 */
		public $id;

		/**
		 * Nom de la table à cibler
		 * @var String
		 */
		protected $table;

		/**
         * Identifiant du gestionnaire de la base de données à utiliser pour exécuter les requêtes du model
		 * @var String
		 */
		protected $handler = "default";

		/**
         * Tableau contenant les informations de jointures à ajout systématiquement sur les SELECT
		 * @var array
		 */
		private $joins = [];

		/**
		 * @param string $pTable    Nom de la table par défaut du model
		 * @param string $pId       Nom du champ de clé primaire
		 */
		public function __construct($pTable, $pId)
		{
			$this->table = $pTable;
			$this->id = $pId;
		}

		/**
		 * Méthode d'insertion de données dans la table du model
		 * Renvoie le resultat de la requête
		 * @param array $pValues				Tableau associatif des données &agrave; insérer
		 * @return resource
		 */
		public function insert(array $pValues)
		{
			return Query::insert($pValues)->into($this->table)->execute($this->handler);
		}


        /**
         * Méthode d'exécution d'une requête REPLACE sur la table du model
         * @param array $pValues            Tableau associatif contenant les valeurs à enregistrer
         * @return array|resource
         */
		public function replace(array $pValues)
		{
			return Query::replace($pValues)->into($this->table)->execute($this->handler);
		}

		/**
		 * Méthode d'insertion multiple d'entrées pour un même model
		 * @param array $pValues				Tableau multi-dimensionnel contenant les données &agrave; insérer
		 * @return resource
		 */
		public function insertMultiple(array $pValues)
		{
			return Query::insertMultiple($pValues)->into($this->table)->execute($this->handler);
		}


		/**
		 * Méthode de modification d'une ou plusieurs entrées dans la table du modèle en cours
		 * @param array              $pValues			Tableau associatif contenant les données
		 * @param QueryCondition     $pCondition			Condition permettant de cibler la modification
		 * @param Boolean $escape
		 * @return resource
		 **/
		public function update(array $pValues, $pCondition = null, $escape = true)
		{
			return Query::update($this->table)->values($pValues, $escape)->setCondition($pCondition)->execute($this->handler);
		}

		/**
		 * Méthode de récupération d'une tuple particulière de la table en fonction de la valeur de sa clé primaire
		 * Renvoie un tableau associatif des données correspondant au résultat de la requête
		 * @param string $pId				Valeur de clé primaire &agrave; cibler
		 * @param string $pFields           Liste des champs à sélectionner lors de la requête à la base
		 * @return array
		 */
		public function getTupleById($pId, $pFields = "*")
		{
			return $this->one(Query::condition()->andWhere($this->id, Query::EQUAL, $pId), $pFields);
		}

		/**
		 * Méthode permettant de générer facilement une requête d'update &agrave; partir de valeur de clé primaire et d'un tableau associatif des valeurs
		 * @param String $pId		Valeur de clé primaire &agrave; cibler
		 * @param array $pValues	Tableau associatif des champs et de leurs nouvelles valeurs
		 * @return resource
		 */
		public function updateById($pId, array $pValues)
		{
			return $this->update($pValues, Query::condition()->andWhere($this->id, Query::EQUAL, $pId));
		}


		/**
		 * Méthode de suppression d'une typle en fonction de la valeur de sa clé primaire
		 * @param String $pId				Valeur de clé primaire &agrave; cibler
		 * @return resource
		 */
		public function deleteById($pId)
		{
			return $this->delete(Query::condition()->andWhere($this->id, Query::EQUAL, $pId));
		}

		/**
		 * Méthode permettant la suppression d'une ou plusieurs entrées
		 * @param QueryCondition     $pCondition			Condition permettant de cibler l'entrée cible de la suppression
		 * @return resource
		 **/
		public function delete($pCondition)
		{
			return Query::delete()->from($this->table)->setCondition($pCondition)->execute();
		}

		/**
		 * Méthode récupérant la valeur d'un champs spécifique
		 * @param String             $pField				Nom du champ
		 * @param QueryCondition     $pCondition			Condition permettant de cibler la selection
		 * @return String
		 **/
		public function getValue($pField, $pCondition)
		{
			$r = $this->one($pCondition, $pField);
			if(preg_match("/as\s([a-z0-9]+)/", $pField, $matches))
				$pField = $matches[1];
			return $r[$pField]??null;
		}

		/**
		 * Méthode de récupération de lé clé primaire venant d'être générée par la base de données
		 * @return int
		 */
		public function getInsertId()
		{
			return DBManager::get($this->handler)->getInsertId();
		}

		/**
		 * Méthode de récupération d'une valeur précise
		 * @param String $pField		Nom du champ &agrave; récupérer
		 * @param String $pId			Valeur de clé primaire
		 * @return String
		 */
		public function getValueById($pField, $pId)
		{
			return $this->getValue($pField, Query::condition()->andWhere($this->id, Query::EQUAL, $pId));
		}

		/**
		 * Méthode permettant de récupérer le nombre max de tuple présent dans une table
		 * @param QueryCondition $pCondition		Condition de la requete
		 * @return int
		 */
		public function count($pCondition)
		{
			return $this->getValue("count(" . $this->table .  "." . $this->id.") as nb", $pCondition);
		}

		/**
		 * Méthode d'ajout de jointure par défaut aux requêtes de type SELECT
		 * @param String $pTable        Nom de la table à cibler
		 * @param null $pType           Type de jointure à appliquer
		 * @param null $pOn             Condition de la jointure
		 * @return void
		 */
		protected function addJoinOnSelect($pTable, $pType = null, $pOn = null)
		{
			if(!$pType)
				$pType = Query::JOIN_NATURAL;
			$this->joins[] = array("table"=>$pTable, "type"=>$pType, "on"=>$pOn);
		}

		/**
		 * @param QuerySelect $pQuery       Instance de QuerySelect sur laquelle appliquer les jointures par défault
		 * @return QuerySelect
		 */
		protected function prepareJoin(QuerySelect $pQuery)
		{
			if(count($this->joins))
			{
				foreach($this->joins as $j)
					$pQuery->join($j["table"], $j["type"], $j["on"]);
			}
			return $pQuery;
		}

		/**
         * Méthode de récupération d'une entrée de la table $this->table
		 * @param null $pCond       Instance de QueryCondition
		 * @param string $pFields
		 * @return array
		 */
		public function one($pCond = null, $pFields = "*")
		{
			if(is_null($pCond))
				$pCond = Query::condition();
			$res = $this->all($pCond->limit(0, 1), $pFields);
			if(!isset($res[0]))
				return null;
			return $res[0];
		}


		/**
         * Méthode de récupération d'un ensemble d'entrée de la table $this->table
		 * @param null|QueryCondition $pCond        Instance de QueryCondition pour contraindre, limiter, ordonner et/ou grouper les résultats
		 * @param string $pFields                   Liste des champs à récupérer
		 * @return array|resource
		 */
		public function all($pCond = null, $pFields = "*")
		{
			return $this->prepareJoin(Query::select($pFields, $this->table))->setCondition($pCond)->execute($this->handler);
		}

		/**
		 * Méthode de génération d'un tableau d'inputs spécifique au formulaire du framework (class Form) à partir du schéma de la table
		 */
		public function generateInputsFromDescribe()
		{
			$result = Query::execute('DESCRIBE '.$this->table, $this->handler);
			$inputs = array();

			foreach($result as &$field)
			{
				$name = $field['Field'];
				switch($field['Type'])
				{
					case "date":
						$input = array(
							'tag'=>'datepicker',
							'attributes'
						);
						break;
					case "text":
						$input = array(
							'tag'=>'textarea'
						);
						break;
					default:
						$input = array(
							'tag'=>'input',
							'attributes'=>array(
								'type'=>'text'
							)
						);
						break;
				}
				$input['label']=$name;
				$inputs[$name] = $input;
			}
			$inputs['submit'] = array(
				'label'=>'',
				'tag'=>'input',
				'attributes'=>array(
					'type'=>'submit',
					'value'=>'Valider',
					'class'=>'button'
				)
			);
			return $inputs;
		}
	}
}
