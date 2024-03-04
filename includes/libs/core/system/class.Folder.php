<?php
namespace core\system
{
	/**
	 * Class Folder
	 * Surcouche aux fonctions Php permettant de gérer les dossiers
	 *
	 * @author Arnaud NICOLAS <arno06@gmail.com>
	 * @version .4
	 * @package system
	 */
	abstract class Folder
	{
		/**
		 * Méthode permettant de lister les dossiers et les fichiers contenus dans un dossier passé en param&egrave;tre
		 * Renvoi un tableau multidimensionnel sous la forme
		 * @param String $pFolder					Chemin du dossier &agrave; lire
		 * @param Boolean $pRecursive				Indique si la lecture du dossier se fait de fa�on récurcive
		 * @return array
		 */
		static public function read($pFolder,$pRecursive = true)
		{
			$return = array();
			$dossier = opendir($pFolder);
			$pFolder = preg_replace('/\/$/', "", $pFolder);
			while ($file = readdir($dossier))
			{
				if ($file != "." && $file != "..")
				{
					$data = array();
					$f = $pFolder."/".$file;
					if(!is_file($f)&&$pRecursive)
						$data = self::read($f);
					$return[$file]= array("children"=>$data,"path"=>$f, "size"=>(is_file($f)?filesize($f):"N/A"));
				}
			}
			closedir($dossier);
			return $return;
		}

		/**
		 * @static
		 * @param $pFolder
		 * @return bool
		 */
		static public function isEmpty($pFolder)
		{
			return (($childs = scandir($pFolder))&&count($childs)<=2);
		}


		/**
		 * Méthode de création d'un nouveau dossier
		 * Renvoi le résultat du traitement
		 * @static
		 * @param string $pPath
		 * @param int $pMode
		 * @return bool
		 */
		static public function create($pPath, $pMode = 0777)
		{
			if(file_exists($pPath))
				return chmod($pPath, $pMode);
			else
				return mkdir($pPath, $pMode, true);
		}

		/**
		 * Méthode de destruction d'un dossier et de tout son contenu <!!!>
		 * Renvoi le résultat du traitement
		 * @param String $pPath					Chemin du dossier &agrave; supprimer
		 * @return Boolean
		 */
		static public function deleteRecursive($pPath)
		{
			if (!is_dir($pPath))
				return false;
			$childs = scandir($pPath);
			foreach($childs as $child)
			{
				if($child=="." || $child=="..")
					continue;
				if(is_dir($pPath.$child."/"))
					self::deleteRecursive($pPath.$child."/");
				else
					File::delete($pPath.$child);
			}
			return rmdir($pPath);
		}
	}
}
