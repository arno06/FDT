<?php
namespace core\tools\form
{
	use core\models\ModelUpload;
	use core\system\File;
	use core\system\Image;
	use core\system\Folder;
	use \Exception;
	/**
	 * Classe de gestion des uploads
	 * Gestion du redimensionnement et des miniatures si le fichier est une image
	 *
	 * @author Arnaud NICOLAS <arno06@gmail.com>
	 * @version .6
	 * @package tools
	 * @subpackage form
	 */
	class Upload
	{

		/**
		 * $_FILES du fichier &agrave; uploader
		 * @var array
		 */
		private $fileData;

		/**
		 * Nouveau nom du fichier
		 * @var String
		 */
		private $fileName;

		/**
		 * Type du fichier &agrave; uploader
		 * @var String
		 */
		private $fileType;

		/**
		 * Dossier cible de l'upload
		 * @var String
		 */
		private $folder;

		/**
		 * Nouvelles dimensions de l'image uploadée
		 * @var array
		 */
		private $newSize;

		/**
		 * Tableau des miniatures
		 * @var	array
		 */
		private $miniatures = array();

		/**
		 * Url relative du fichier, concaténation du dossier, du nom et du type du fichier
		 * @var String
		 */
		public $pathFile;

		/**
		 * Variable permettant de savoir si l'upload est effectif ou non
		 * @var	Boolean
		 */
		public $isUpload = false;

		/**
		 * Model Upload permettant de gérer directement sauvegarder l'ensemble des fichiers en bases
		 * @var ModelUpload
		 */
		public $model_upload;

		/**
		 * Id de l'upload en base
		 * @var int
		 */
		public $id_upload;


		/**
		 * Constructor
		 * @param array $pFile					$_FILES cible
		 * @param String $pFolder				Dossier cible
		 * @param String $pFileName				Nouveau nom du fichier
		 */
		public function __construct(array $pFile, $pFolder = "/", $pFileName = "")
		{
			$this->folder = $pFolder;
			$this->fileData = $pFile;
			$this->fileName = File::sanitizeFileName($pFileName?$pFileName:preg_replace("/(\.[a-z0-9]{2,4})$/i","",$pFile["name"]));
			$this->fileType = $this->getMimeType();
			$p = $this->fileName.".".$this->fileType;
			$f = $this->fileName;
			$i = 1;
			while(file_exists($this->folder.$p))
			{
				$t = $f."(".++$i.").";
				$p = $t.$this->fileType;
			}
			$this->fileName = $p;
			$this->pathFile = $this->folder.$this->fileName;
			$this->model_upload = new ModelUpload();
		}


		/**
		 * Méthode d'envoi de déclenchement des actions d'uploads, redimensionnement...
		 * @throws Exception
		 * @param bool $pCreateFolder
		 * @return bool
		 */
		public function send($pCreateFolder = false)
		{
			if ($pCreateFolder && !is_dir($this->folder))
				Folder::create($this->folder);
			if(!isset($this->fileData)||$this->fileData["error"]!=0)
				throw new Exception("Upload impossible : une erreur est survenue");
			if(!is_uploaded_file($this->fileData["tmp_name"]))
				throw new Exception("Upload impossible");
			if(!move_uploaded_file($this->fileData["tmp_name"], $this->pathFile))
				throw new Exception("Upload impossible : le dossier cible n'existe pas");
			chmod($this->pathFile, 0666);
			if(is_array($this->newSize) && count($this->newSize) == 2)
			{
				if(!Image::resize($this->pathFile, $this->newSize[0], $this->newSize[1]))
					throw new Exception("Upload effectué : redimensionnement impossible");
			}
			if($this->miniatures)
			{
				$max = count($this->miniatures);
				for($i = 0; $i< $max; ++$i)
					Image::createCopy($this->pathFile, $this->miniatures[$i]["pathFile"],$this->miniatures[$i]["width"], $this->miniatures[$i]["height"]);
			}

			if(!$this->model_upload->insertUpload($this->pathFile))
			{
				$this->cancelUpload();
				throw new Exception("Upload impossible : erreur lors de l'insertion dans la base.");
			}
			$this->isUpload = true;
			$this->id_upload = $this->model_upload->getInsertId();
			return $this->isUpload;
		}


		/**
		 * Méthode permettant d'annuler l'upload
		 * Supprime le fichier principal et les fichiers secondaires (miniatures)
		 * @return void
		 */
		public function cancelUpload()
		{
			if($this->isUpload)
				$this->model_upload->deleteById($this->id_upload);
			$max = count($this->miniatures);
			for($i = 0; $i< $max; ++$i)
				File::delete($this->miniatures[$i]["pathFile"]);
		}


		/**
		 * Méthode permettant de renommer le fichier principal
		 * @param String $pNewName				nouveau de du fichier (sans dossier ni extension)
		 * @return void
		 */
		public function renameFile($pNewName)
		{
			File::rename($this->pathFile, $this->folder.$pNewName.".".$this->fileType);
			$this->fileName = $pNewName;
			$this->pathFile = $this->folder.$this->fileName.".".$this->fileType;
			$this->model_upload->updateById($this->id_upload, array("path_upload"=>$this->pathFile));
		}


		/**
		 * @param  $pNewName
		 * @return void
		 */
		public function renameFolder($pNewName)
		{
			File::rename($this->pathFile, $pNewName.$this->fileName.".".$this->fileType);
			$this->folder = $pNewName;
			$this->pathFile = $this->folder.$this->fileName.".".$this->fileType;
			$this->model_upload->updateById($this->id_upload, array("path_upload"=>$this->pathFile));
		}


		/**
		 * Définie les nouvelles dimensions de l'image uploadée
		 * @param Number $pWidth				Largeur
		 * @param Number $pHeight				Hauteur
		 * @return void
		 */
		public function resizeImage($pWidth, $pHeight)
		{
			$this->newSize = array($pWidth, $pHeight);
		}


		/**
		 * Méthode permettant d'ajouter une nouvelle miniature dans la liste de traitement
		 * @deprecated
		 * @param  $pName
		 * @param  $pFolder
		 * @param  $pWidth
		 * @param  $pHeight
		 * @return void
		 */
		public function addMiniature($pName, $pFolder, $pWidth, $pHeight)
		{
			array_push($this->miniatures, array("pathFile"=>$pFolder.$pName.".".$this->fileType, "width"=>$pWidth, "height"=>$pHeight));
			if($this->isUpload)
				Image::createCopy($this->pathFile, $pFolder.$pName.".".$this->fileType, $pWidth, $pHeight);
		}


		/**
		 * Permet de récupérer le mimeType en fonction du nom du fichier &agrave; uploader
		 * @return String
		 */
		private function getMimeType()
		{
			if(!isset($this->fileData["name"])||empty($this->fileData["name"]))
				return "";
			$extract = array();
			preg_match(File::REGEXP_EXTENSION,$this->fileData["name"],$extract);
			return $extract[1];
		}


		/**
		 * Méthode permettant de vérifier si le fichier principal correpond &agrave; un mimeType particulier
		 * @param String $pExtension				Extensions autorisées ("pdf" ou "jpg|gif|png"...)
		 * @return boolean
		 */
		public function isMimeType($pExtension)
		{
			if($pExtension == "*")
				$pExtension = ".".$pExtension;
			$extract = array();
			if(preg_match("/^.*\.(".$pExtension.")$/i",$this->pathFile,$extract))
				return $extract[1];
			else
				return false;
		}
	}
}
