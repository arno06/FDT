<?php
namespace core\data
{
	use core\system\File;
    use \Exception;

	/**
	 * Class SimpleJSON
	 * Permet de manipuler des données au format JSON
	 *
	 * @author Arnaud NICOLAS <arno06@gmail.com>
	 * @version .3
	 * @package data
	 */
	abstract class SimpleJSON implements InterfaceData
	{
		/**
		 * Méthode de chargement et de décodage d'un fichier JSON
		 * @param String $pFile
		 * @return array
		 * @throws \Exception
		 */
		static public function import($pFile)
		{
			try
			{
				$contenu = 	File::read($pFile);
			}
			catch (Exception $e)
			{
				throw new Exception("Impossible de lire le fichier source <b>".$pFile."</b>");
			}
			return self::decode($contenu);
		}


		/**
		 * Méthode de décodage d'un String en Tableau
		 * @param String $pString				donnée string &agrave; decoder
		 * @return array
		 */
		static public function decode($pString)
		{
			return json_decode($pString,true);
		}

		/**
		 * Méthode d'encodage d'un String en Tableau
		 * @param array $pArray				Tableau &agrave; encoder
		 * @return String
		 */
		static public function encode(array $pArray)
		{
			return json_encode(self::parseToNumericEntities($pArray));
		}

		/**
		 * Méthode de parsing récursif des valeurs d'un tableau dans leur format encodé numériquement (é ==> &#233;)
		 * @param array $pArray
		 * @return array
		 */
		static public function parseToNumericEntities(array $pArray)
		{
			$return = array();
			foreach($pArray as $key=>$value)
			{
				$key = Encoding::toNumericEntities($key);
				$value = Encoding::toNumericEntities($value);
				$return[$key] = $value;
			}
			return $return;
		}

		static public function indent($pData)
		{
			$pData = preg_replace('/(\,)/', '${1}'."\r\n", $pData);
			$pData = preg_replace('/(\{|\})/', "\r\n".'${1}'."\r\n", $pData);
			$new = array();
			$lignes = explode("\r\n", $pData);
			$deep = 0;
			$t = "";
			for($i = 0, $max = count($lignes); $i<$max;$i++)
			{
				if(empty($lignes[$i]))
					continue;
				$l = $lignes[$i];
				if(preg_match('/^\}$/', $l))
				{
					$t = "";
					for($j = 0, $maxj = --$deep; $j<$maxj;$j++)
						$t .= "\t";
				}
				$new[]= $t.$l;
				if(preg_match('/^\{$/', $l))
				{
					$t = "";
					for($j = 0, $maxj = ++$deep; $j<$maxj;$j++)
						$t .= "\t";
				}
			}
			$pData = implode("\r\n", $new);
			return $pData;
		}
	}
}
