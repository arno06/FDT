<?php
namespace core\data
{
	use core\system\File;
	use \Exception;

	/**
	 * Class de gestion des fichiers CSV
	 *
	 * @author Arnaud NICOLAS <arno06@gmail.com>
	 * @version .4
	 * @package data
	 */
	abstract class SimpleCSV implements InterfaceData
	{
		/**
		 * Caractère de séparation des champs
		 * @var String
		 */
		const SEPARATOR = ";";

		/**
		 * Méthode de conversion de données au format Tableau en chaine de caractères formatée en CSV
		 * @param array $pData				Données à convertir
         * @param bool $skipLabels
		 * @return String
		 */
		static public function encode(array $pData, $skipLabels = false)
		{
			if(!$pData)
				return "";
			$result = '';
			$libelles = array();
			$donnees = "";

			// if a unique entry is sent, put it in an envelope
			if (!isset($pData[0])) {
				$pData = array($pData);
			}

			for($i = 0, $max = count($pData); $i<$max;$i++)
			{
				foreach($pData[$i] as $champs=>$value)
				{
					if(!in_array($champs, $libelles))
						array_push($libelles, $champs);
				}
			}
			$data = array();
			$maxj = count($libelles);
			for($i = 0; $i<$max;$i++)
			{
				$d = array();
				for($j = 0;$j<$maxj;$j++)
				{
					if(isset($pData[$i][$libelles[$j]]))
					{
						$v = $pData[$i][$libelles[$j]];
						$v = str_replace('"', '""', $v);
						if(preg_match("/(\r|\n|".self::SEPARATOR.")/", $v, $matches))
							$v = '"'.$v.'"';
						$d[$libelles[$j]] = $v;
					}
					else
						$d[$libelles[$j]] = "";
				}
				$data[] = $d;
			}
			for($i=0, $max = count($data); $i<$max; $i++)
			{
				$ligne = "";
				$ct = 0;
				foreach($data[$i] as $value)
					$ligne .= $ct++?self::SEPARATOR.$value:$value;
				$donnees .= $ligne."\r\n";
			}

			if (!$skipLabels) {
				$result .= implode(self::SEPARATOR,$libelles)."\r\n";
			}

			$result .= $donnees;

			return $result;
		}

		/**
		 * Méthode de conversion d'une chaine de caractères formatée en CSV vers un Tableau
		 * @param String $pString				Chaine à convertir
		 * @return array
		 */
		static public function decode($pString)
		{
			$return = array();
			$dataArray = explode(PHP_EOL,$pString);
            $fields = explode(self::SEPARATOR, $dataArray[0]);
            foreach($fields as &$field){
                $field = preg_replace('/(\r|\n)$/', '', $field);
            }
            $maxFields = count($fields);
			$max = count($dataArray);
			for($i = 1; $i < $max; $i++)
			{
				if($dataArray[$i]=="")
					continue;
				$temp = explode(self::SEPARATOR, $dataArray[$i]);
				$new = array();
				for($j = 0; $j<$maxFields; $j++)
				{
					$v = $temp[$j];
					$v = preg_replace("/^\"/", "", $v);
					$v = preg_replace("/\"$/", "", $v);
					$new[$fields[$j]] = $v;
				}
				$return[] = $new;
			}
			return $return;
		}

		/**
		 * Méthode d'exportation de données provenant de la base vers un fichier CSV
		 * Renvoie le résultat de l'écriture du fichier
		 * @param array $pData					Tableau des données
		 * @param String $pFileName				Nom du fichier
		 * @return Boolean
		 */
		static public function export(array $pData, $pFileName)
		{
			if(!$pData)
				return false;
			$donnees = self::encode($pData);
			File::delete($pFileName);
			File::create($pFileName);
			return File::append($pFileName, $donnees);
		}

		/**
		 * Méthode d'import de données à partir d'un fichier CSV
		 * @param String $pFileName				Nom du fichier
		 * @return array
		 */
		static public function import($pFileName)
		{
			try
			{
				$dataString = File::read($pFileName);
			}
			catch (Exception $e)
			{
				return null;
			}
			return self::decode($dataString);
		}
	}
}
