<?php
namespace core\system
{

    use core\utils\DiffResult;
    use core\utils\FileDiff;
    use core\utils\StringDiff;
    use \Exception;
	/**
	 * Class File
	 * Surcouche aux fonctions Php permettant de gérer les fichiers
	 *
	 * @author Arnaud NICOLAS <arno06@gmail.com>
	 * @version .4
	 * @package system
	 */
	abstract class File
	{
		const REGEXP_EXTENSION = '/\.([a-z0-9]{2,4})$/i';

		/**
		 * Méthode de création d'un nouveau fichier sur le serveur
		 * Renvoi le résultat du traitement - False si le fichier existe déj&agrave;
		 * @param String $pFile					chemin du fichier
		 * @return boolean
		 */
		static public function create($pFile)
		{
			if(file_exists($pFile))
				return false;
			else
				return fclose(fopen($pFile,'x'));
		}

		/**
		 * Méthode de récupération d'une ressource apr&egrave;s ouverture d'un fichier non binaire
		 * @param String $pFile					Chemin du fichier
		 * @param String $pMode					Mode d'ouverture, par défault "r" pour "read" lecture
		 * @return resource
		 */
		static protected function open($pFile, $pMode = "r")
		{
			if(file_exists($pFile))
				return fopen($pFile, $pMode);
			else
				return false;
		}

		/**
		 * Méthode de récupération du contenu d'un fichier non binaire
		 * Déclenche une Exception en cas d'échec
		 * @param String $pPath					Chemin du fichier
		 * @return String
		 * @throws Exception
		 */
		static public function read($pPath)
		{
			if($ressource = self::open($pPath))
			{
				$r = fread($ressource, filesize($pPath));
				fclose($ressource);
				return $r;
			}
			else {
                $split = explode("/", $pPath);
                $file = array_pop($split);
                throw new Exception("Le fichier '".$file."' n'existe pas.");
            }
		}

        /**
         * @param $pFileName
         * @param $pCallBack
         * @return void
         * @throws Exception
         */
        static public function readLines($pFileName, $pCallBack){
            if(!($resource = self::open($pFileName))){
                throw new Exception("Le fichier '".$pFileName."' n'existe pas.");
            }
            $idx = 0;
            while(!feof($resource)){
                $pCallBack(fgets($resource), $idx++);
            }
            fclose($resource);
        }

		/**
		 * Méthode d'écriture &agrave; la suite d'un fichier existant
		 * @param String $pFile					Chemin du fichier
		 * @param String $pValue				Valeur &agrave; écrire
		 * @return resource
		 */
		static public function append($pFile, $pValue)
		{
			$r = self::open($pFile, "a");
			$return = @fwrite($r, $pValue);
			@fclose($r);
			return $return;
		}

		/**
		 * Méthode de suppression d'un fichier
		 * Renvoi le résultat de l'action, false si le fichier n'existe pas
		 * @param String $pFile					Chemin du fichier
		 * @return boolean
		 */
		static public function delete($pFile)
		{
			if(file_exists($pFile))
			{
				chmod($pFile, 0777);
				return unlink($pFile);
			}
			else
				return true;
		}

		/**
		 * Méthode de renommage d'un fichier/dossier
		 * Renvoi le résultat de l'action, false si le fichier n'existe pas
		 * @param String $pFile					Chemin actuel
		 * @param String $pNewName				Nouveau Chemin
		 * @return boolean
		 */
		static public function rename($pFile, $pNewName)
		{
			if(file_exists($pFile))
				return @rename($pFile, $pNewName);
			else
				return false;
		}

		/**
		 * Méthode d'échappement des caract&egrave;res pouvant poser probl&egrave;me dans certains syst&egrave;mes de fichiers
		 * @param String $pFileName				Nom du fichier
		 * @return String
		 */
		static public function sanitizeFileName($pFileName)
		{
			$pFileName = strtolower($pFileName);
			$chars = array(" "=>"-",
				"@"=>"at",
				"\\"=>"-",
				"/"=>"-",
				"â"=>"a",
				"à"=>"a",
				"ä"=>"a",
				"é"=>"e",
				"è"=>"e",
				"ê"=>"e",
				"ë"=>"e",
				"ï"=>"i",
				"ì"=>"i",
				"ù"=>"u",
				"ü"=>"u",
				"ô"=>"o",
				"ò"=>"o",
				"ö"=>"o",
				"ÿ"=>"y");
			foreach($chars as $key=>$change)
				$pFileName = str_replace($key, $change, $pFileName);
			return $pFileName;
		}


		/**
		 * Méthode de récupération d'une extension d'un fichier &agrave; partir du nom de ce même fichier
		 * @param String $pFile		Nom du fichier - peut être le chemin relatif ou absolu de celui-ci
		 * @return String(2,3)
		 */
		static public function getExtension($pFile)
		{
			preg_match(self::REGEXP_EXTENSION, $pFile, $extracts);
			return $extracts[1];
		}

        /**
         * @param string $pFile
         * @return bool
         */
		static public function isImage($pFile)
		{
			$extension = self::getExtension($pFile);
			return in_array($extension, array('gif', 'jpg', 'jpeg', 'png', 'tif'));
		}

		/**
		 * Méthode de récupération du MimType d'un fichier &agrave; partir de son nom
		 * @param object $pFile		Nom du fichier - peut être le chemin relatif ou absolu de celui-ci
		 * @return String
		 */
		static public function getMimeType($pFile)
		{
			$extension = self::getExtension($pFile);
			switch($extension)
			{
				case "gz":
					$type = "application/x-gzip"; break;
				case "tgz":
					$type = "application/x-gzip"; break;
				case "zip":
					$type = "application/zip"; break;
				case "rar":
					$type = "application/rar"; break;
				case "pdf":
					$type = "application/pdf"; break;
				case "png":
					$type = "image/png"; break;
				case "gif":
					$type = "image/gif"; break;
				case "jpg":
					$type = "image/jpeg"; break;
				case "txt":
					$type = "text/plain"; break;
				case "csv":
					$type = "text/csv"; break;
				default:
					$type = "application/octet-stream"; break;
			}
			return $type;
		}


		/**
		 * Méthode permettant de forcer le téléchargement d'un fichier ou un contenu via un fichier temporaire
		 * Quitte l'applicatif - aucune sortie HTML générée
		 * @param String 	$pFile		emplacement du fichier &agrave; télécharger
		 * @param String	$pSource	contenu du fichier - peut être du contenu JSON, CSV, XML...
		 * @return void
		 */
		static public function download($pFile, $pSource = "")
		{
			if(empty($pFile))
				return;
			$fromSource = !empty($pSource);
			if(!$fromSource)
				$length = filesize($pFile);
			else
				$length = strlen($pSource);
			header("content-disposition: inline; filename=\"".basename($pFile)."\"");
			header('Content-Type: application/force-download');
			header('Content-Transfer-Encoding: binary');
			header("Content-Length: ".$length);
			header("Pragma: no-cache");
			header("Cache-Control: must-revalidate, post-check=0, pre-check=0, public");
			header("Expires: 0");
			if(!$fromSource)
				readfile($pFile);
			else
				echo $pSource;
			exit(0);
		}

        static public function compare($pFileFrom, $pFileTo){
            $from = explode(PHP_EOL, self::read($pFileFrom));
            $to = explode(PHP_EOL, self::read($pFileTo));
            $diff = new StringDiff();
            return $diff->compare($from, $to);
        }
	}
}
