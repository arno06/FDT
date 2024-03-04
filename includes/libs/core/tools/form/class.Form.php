<?php
namespace core\tools\form
{

    use core\application\Application;
    use core\application\Core;
	use core\data\SimpleJSON;
	use core\application\Dictionary;
	use core\application\Autoload;
	use core\db\Query;
    use core\models\ModelUpload;
    use \Exception;

	/**
	 * Classe de gestion des formulaires (création / vérification des données)
	 *
	 * @author Arnaud NICOLAS <arno06@gmail.com>
	 * @version .8
	 * @package tools
	 * @subpackage form
	 */
	class Form
	{
		const TAG_CAPTCHA          = "captcha";

		const TAG_RICHEDITOR       = "richeditor";

		const TAG_DATEPICKER       = "datepicker";

		const TAG_COLORPICKER      = "colorpicker";

		const TAG_RADIOGROUP       = "radiogroup";

		const TAG_UPLOAD           = "upload";

		const TAG_CHECKBOXGROUP    = "checkboxgroup";

		const TAG_INPUT            = "input";

		const TAG_TEXTAREA         = "textarea";

		const TAG_SELECT           = "select";

		/**
		 * Constante définissant le dossier de base des uploads
		 */
		const PATH_TO_UPLOAD_FOLDER = "files/uploads/";

		/**
		 * Expression régulière pour une chaine de caractére alpha numérique
		 * @var String
		 */
		static public $regExp_AlphaNumeric= '/^[0-9a-z\_\-]+$/i';

		static public $regExp_Password= '/^[0-9a-z]{6,}$/i';

		/**
		 * Expression régulière de mail - PhpMailer
		 * @var String
		 */
		static public $regExp_Mail = '/^(?:[\w\!\#\$\%\&\'\*\+\-\/\=\?\^\`\{\|\}\~]+\.)*[\w\!\#\$\%\&\'\*\+\-\/\=\?\^\`\{\|\}\~]+@(?:(?:(?:[a-zA-Z0-9_](?:[a-zA-Z0-9_\-](?!\.)){0,61}[a-zA-Z0-9_-]?\.)+[a-zA-Z0-9_](?:[a-zA-Z0-9_\-](?!$)){0,61}[a-zA-Z0-9_]?)|(?:\[(?:(?:[01]?\d{1,2}|2[0-4]\d|25[0-5])\.){3}(?:[01]?\d{1,2}|2[0-4]\d|25[0-5])\]))$/';

		/**
		 * Expression régulière pour un chiffre/nombre
		 * @var String
		 */
		static public $regExp_Numeric = '/^[0-9]+$/';

		/**
		 * @var string
		 */
		static public $regExp_Date = '/^((19|20)[0-9]{2})\-(0[1-9]|1[012])\-(0[1-9]|[12][0-9]|3[01])$/';

		/**
		 * Expression régulière pour du texte (autorise tout type de caractére)
		 * @var String
		 */
		static public $regExp_Text = '/.{1,}/';

		/**
		 * Expression régulière d'un url http://www.domain.ext/
		 * @var String
		 */
		static public $regExp_Url = '/^http\:\/\/www\.[a-z0-9\_\-\?\&]\.[a-z]{2,3}\/$/';

		/**
		 * Expression régulière des chaines de caractéres interdisant le <html>
		 * @var String
		 */
		static public $regExp_TextNoHtml = '/^[^\<\>]{1,}$/i';

        /**
         * @var string
         */
		static public $regExp_Hexa = '/^[0-9a-f]{6}$/i';

		/**
		 * Variable contenant la concaténation des erreurs relevées lors de la vérification du formulaire
		 * @var String
		 */
		private $error = "";

		/**
		 * Données du formulaire, parsées du fichier JSON
		 * @var array
		 */
		private $data = array();

		/**
		 * @var bool
		 */
		private $dataCleaned = false;

		/**
		 * Nom du formulaire JSON
		 * @var String
		 */
		public $name = "";

		/**
		 * Tableau des fichiers uploadés
		 * @var array
		 */
		private $uploads = array();

		/**
		 * Variable permettant de savoir si le traitement du formulaire en cours est valide ou non
		 * @var Boolean
		 */
		private $isValid = false;

		/**
		 * @var bool
		 */
		protected $hasUpload = false;

		/**
		 * @var bool
		 */
		protected $hasDatePicker = false;

		/**
		 * @var bool
		 */
		protected $hasColorPicker = false;

		/**
		 * @var int
		 */
		protected $countMendatory = 0;

		/**
		 * Tableau des champs "input type file" dont le type de fichier est incorrect
		 * @var array
		 */
		private $uploadsFailMimeType = array();

		/**
		 * Tableau des champs "input type file" dont l'upload est impossible
		 * @var array
		 */
		private $uploadsSendFail = array();

		/**
		 * Tableau des champs du formulaire dont la valeur est incorrecte (Expression régulière non vérifiée par exemple)
		 * @var array
		 */
		private $inputsIncorrect = array();

		/**
		 * @var array
		 */
		private $inputsWithAlternative = array();

		/**
		 * Tableau des champs du formulaire dont la valeur du champ de confirmation n'est pas bonne
		 * @var array
		 */
		private $inputsWithConfirm = array();

		/**
		 * Tableau des champs obligatoires du formulaire dont les valeurs ne sont pas valides (vide ou expression régulière non vérifiée)
		 * @var	array
		 */
		private $inputsRequire = array();

		/**
		 * @var array
		 */
		private $files;

		/**
		 * @var array
		 */
		private $post;


		/**
		 * Constructor
		 * Récupére et parse le fichier JSON de configuration du formulaire
		 * @param  $pName
		 * @param bool $pReal
		 * @throws Exception
		 */
		public function __construct($pName, $pReal = true)
		{
			$this->name = $pName;
			if(!$pReal)
				return;
            $module = Core::$module;
			$formFile = Core::$path_to_application."/modules/".$module."/forms/form.".$pName.".json";
			try
			{
				$this->data = SimpleJSON::import($formFile);
			}
			catch (Exception $e)
			{
				throw new Exception("Le formulaire <b>".$pName."</b> est introuvable");
			}
			if(!$this->data)
				throw new Exception("Impossible de parser le fichier de déclaration du formulaire <b>".$pName."</b>, veuillez vérifier le formatage des données (guillements, virgules, accents...).");
		}

		/**
		 * Méthode permettant de modifier la valeur d'une propriete d'un des champs définit dans le formulaire
		 * @param string $pName
		 * @param string $pPropery
		 * @param mixed $pValue
		 * @return bool
		 */
		public function setProperty($pName, $pPropery, $pValue)
		{
			if(!isset($this->data[$pName]))
				return false;
			if(!isset($this->data[$pName]["attributes"]))
				$this->data[$pName]["attributes"] = array();
			$this->data[$pName]["attributes"][$pPropery] = $pValue;
			return true;
		}

		/**
		 *
		 */
		private function cleanData()
		{
			if($this->dataCleaned)
				return;
			$this->dataCleaned = true;
			$default = array(
                "errorLabel"=>"",
				"label"=>"",
				"require"=>false,
				"attributes"=>array(),
				"regExp"=>"",
				"inputModifiers"=>array(),
				"outputModifiers"=>array()
			);
			$spe = array(
				self::TAG_UPLOAD=>array(
					"fileType"=>"txt|rtf|pdf|doc|docx|xls|xlsx|csv|ppt|pptx"
				),
				self::TAG_CAPTCHA=>array(
					"attributes"=>array(
                        "length"=>5
                    )
				),
				self::TAG_SELECT=>array(
					"parameters"=>array()
				)
			);
			$parseLabels = false;
			foreach($this->data as &$input)
			{
				foreach($default as $n=>$v)
				{
					if(!isset($input[$n]))
						$input[$n] = $v;
				}
				if(isset($spe[$input["tag"]]))
				{
					foreach($spe[$input["tag"]] as $n=>$v)
					{
						if(!isset($input[$n]))
							$input[$n] = $v;
					}
				}
				if($parseLabels&&!empty($input["label"]))
					$input["label"] = Dictionary::term($input["label"]);
                else if(!$parseLabels && empty($input['label']) && isset($input['attributes']) && isset($input['attributes']['placeholder']))
                    $input['errorLabel'] = $input['attributes']['placeholder'];
			}
		}


		/**
		 * Méthode de validation des données attendues dans le formulaire
		 * Upload les fichiers
		 * Définit les erreurs en fonction de la nécessité des différents champs
		 * @return Boolean
		 */
		public function isValid()
		{
			$this->cleanData();
			$this->error = "";
			if(!isset($_POST[$this->name])||empty($_POST[$this->name]))
				return false;
			$this->post = &$_POST[$this->name];
			$this->performUploads();
			$this->checkInputs();
			return $this->isValid;
		}


		/**
		 * Méthode de d'exécution des uploads si le formulaire présente des inputs type FILE
		 * @return void
		 */
		private function performUploads()
		{
			$this->uploadsFailMimeType = array();
			$this->uploadsSendFail = array();
			$this->uploads = array();
			if(!isset($_FILES[$this->name]))
				return;
			$tmp = $_FILES[$this->name];
			$this->files = array();
			foreach($tmp["name"] as $name=>$value)
				$this->files[$name] = array();
			foreach($this->files as $name=>$value)
			{
				$file = array();
				$file["name"] = $tmp["name"][$name];
				$file["type"] = $tmp["type"][$name];
				$file["tmp_name"] = $tmp["tmp_name"][$name];
				$file["error"] = $tmp["error"][$name];
				$file["size"] = $tmp["size"][$name];
				$this->files[$name] = $file;
			}
			foreach($this->files as $name=>$data)
			{
				if(isset($this->post[$name])&&!empty($this->post[$name]))
					continue;
				$input = $this->data[$name];
				if(isset($input["fileName"]))
					$fileName = "file".(rand(0,999999));
				else
					$fileName = "";
				$folder = self::PATH_TO_UPLOAD_FOLDER;
				if(isset($input["folder"]))
					$folder .= $input["folder"];
				$up = new Upload($data, $folder, $fileName);
				if(isset($input["resize"])&&is_array($input["resize"]))
					$up->resizeImage($input["resize"][0],$input["resize"][1]);

				if(!$up->isMimeType($input["fileType"]))
				{
					$this->uploadsFailMimeType[] = $name;
					continue;
				}
				try
				{
					$up->send(true);
				}
				catch(Exception $e)
				{
					$this->uploadsSendFail[] = $name;
					continue;
				}
				$this->data[$name]["isUpload"] = true;
				$this->post[$name] = $up->id_upload;
				$this->uploads[] =  array("name"=>$name, "instance"=>$up);
			}
		}


		/**
		 * Méthode de traitement du formulaire si les données $this->post existantes
		 * @return void
		 */
		private function checkInputs()
		{
			$this->isValid = true;
			$this->inputsWithAlternative = array();
			$this->inputsWithConfirm = array();
			$this->inputsRequire = array();
			$this->inputsIncorrect = array();

			foreach($this->data as $name=>&$data)
			{
				if($data["tag"] == Form::TAG_CAPTCHA)
				{
                    $attr = $data["attributes"];
					$data["label"] = "Captcha";
					$c = new Captcha($attr["length"], $name);
					if(!isset($this->post[$name]) || empty($this->post[$name]))
					{
						unset($this->post[$name]);
						$this->inputsRequire[] = $name;
						$this->isValid = false;
					}
					else if($c->getValue() != $this->post[$name])
					{
						unset($this->post[$name]);
						$this->inputsIncorrect[]= $name;
						$this->isValid = false;
					}
					else
					{
						unset($this->post[$name]);
						$c->unsetSessionVar();
					}
					continue;
				}
				if(is_array($data["inputModifiers"]))
					$this->applyModifiers($data["inputModifiers"], $name);
				if(isset($data["attributes"]["type"]))
				{
					switch($data["attributes"]["type"])
					{
						case "submit":
							unset($this->post[$name]);
							continue 2;
						case "checkbox":
							if(!isset($this->post[$name])||$this->post[$name]!=$data["attributes"]["value"])
								$this->post[$name] = $data["attributes"]["valueOff"];
							break;
					}
				}
				if ($data["tag"] == self::TAG_CHECKBOXGROUP)
				{
					if(!isset($this->post[$name]))
					{
						$this->post[$name] = array();
					}
				}
				if($data["tag"]==self::TAG_UPLOAD&&$data["require"])
				{
					if(!isset($data["isUpload"])&&!$data["attributes"]["value"]&&!$this->post[$name])
					{
						$this->inputsRequire[] = $name;
						$this->isValid = false;
					}
					continue;
				}
				if(isset($data["isAlternativeFor"])&&isset($this->data[$data["isAlternativeFor"]]))
				{
					if((!isset($this->post[$data["isAlternativeFor"]])||(empty($this->post[$data["isAlternativeFor"]])))
						&&(!isset($this->post[$name])||(empty($this->post[$name]))))
					{
						$this->inputsWithAlternative[] = array($name, $data["isAlternativeFor"]);
						$this->isValid = false;
						continue;
					}
				}
				if(isset($data["isConfirmFor"])&&isset($this->data[$data["isConfirmFor"]]))
				{
					if (!isset($this->post[$data["isConfirmFor"]]))
					{
						unset($this->post[$name]);
						continue;
					}
					if($this->post[$name]!=$this->post[$data["isConfirmFor"]])
					{
						$this->inputsWithConfirm[] = array($name, $data["isConfirmFor"]);
						$this->isValid = false;
					}
					else
						unset($this->post[$name]);
					continue;
				}
				if($data["require"])
				{
					if (is_string($this->post[$name]))
						$this->post[$name] = trim($this->post[$name]);
					elseif (is_array($this->post[$name]))
					{
						foreach($this->post[$name] as &$p)
						{
							if (is_string($p))
								$p = trim($p);
						}
					}

					if($this->post[$name]!==0&&$this->post[$name]!=='0'&&empty($this->post[$name]))
					{
						if(isset($data["isAlternativeFor"])&&isset($this->data[$data["isAlternativeFor"]])&&!empty($this->post[$data["isAlternativeFor"]]))
							continue;

						$this->inputsRequire[] = $name;
						$this->isValid = false;
						continue;
					}
				}

				if($data["require"]===false&&empty($this->post[$name]))
				{
					$this->applyModifiers($data["outputModifiers"], $name);
					continue;
				}
				if($data["require"]===true&&empty($data["regExp"]))
				{
					trigger_error("Les champs obligatoires doivent nécessairement renseigner une expression régulière &agrave; respecter !<br/>Formulaire <b>".$this->name."</b> champ <b>".$name."</b>", E_USER_ERROR);
				}
				if(empty($data["regExp"]))
				{
					$this->applyModifiers($data["outputModifiers"], $name);
					continue;
				}
				$regExp = $this->getRegExp($data["regExp"]);
				if($data["tag"] == self::TAG_SELECT
					&& isset($data["attributes"]["multiple"])
					&& $data["attributes"]["multiple"]=="multiple")
				{
					if($data["require"] === false)
						array_shift($this->post[$name]);
				}
				$valid = false;
				if(is_array($this->post[$name]))
				{
					$valid = true;
					foreach($this->post[$name] as $v)
						$valid = $valid&&preg_match($regExp, $v, $extract, PREG_OFFSET_CAPTURE);
				}
				else if (is_string($this->post[$name]))
				{
					$valid = preg_match($regExp, $this->post[$name], $extract, PREG_OFFSET_CAPTURE);
				}
				if(!$valid)
				{
					$this->inputsIncorrect[] = $name;
					unset($this->post[$name]);
					$this->isValid = false;
					continue;
				}
				$this->applyModifiers($data["outputModifiers"], $name);
			}
		}

		/**
		 * @param array $pModifiers
		 * @param string $pName
		 * @return void
		 */
		protected function applyModifiers($pModifiers, $pName)
		{
			if(!is_array($pModifiers))
				return;
			for($i = 0, $max = count($pModifiers);$i<$max;$i++)
			{
				$this->post[$pName] = call_user_func($pModifiers[$i], $this->post[$pName]);
			}
		}

		/**
		 * Méthode de récupération de l'expression régulière en fonction de l'information saisie dans le fichier de déclaration JSON
		 * Cette expression régulière peut se présenter sous deux formats :
		 * 					- Nom d'une expression disponible de base. Voir les propriétés statics Form::$regExp_NOM
		 * 					- Une expression régulière directement spécifiée dans le JSON, celle-ci devra étre déclarée de la mnaiére suivante : custom:/[votre expression]/
		 * @param String $pRegExp		Valeur de l'expression régulière telle qu'elle est déclarée dans le JSON
		 * @return String
		 */
		protected function getRegExp($pRegExp)
		{
			if(preg_match("/^(custom\:)/", $pRegExp, $extract, PREG_OFFSET_CAPTURE))
				return preg_replace("/^(custom\:)/", "", $pRegExp);
			$regExp = "regExp_".$pRegExp;
			if(isset(self::$$regExp))
				return self::$$regExp;
			return "";
		}

		/**
		 * Méthode de récupération des valeurs du formulaires
		 * @return array
		 */
		public function getValues()
		{
			return $this->post;
		}

		/**
		 * @return array
		 */
		public function toArray()
		{
			return $this->data;
		}

		/**
		 * Méthode de génération du message d'erreur en fonction du traitement du formulaire
		 * Ecrit sur la propriété public $error de l'objet Form
		 * @return string
		 */
		public function getError()
		{
			if(!isset($this->post))
				return "";
			$this->error = $this->getErrorFromArray($this->uploadsFailMimeType, "global.forms.errorMimeType", "global.forms.errorMimeTypes");
			$this->error .= $this->getErrorFromArray($this->uploadsSendFail, "global.forms.errorUploadSend", "global.forms.errorUploadsSend");
			$this->error .= $this->getErrorFromArray($this->inputsWithAlternative, "global.forms.errorInputWithAlternative", "global.forms.errorInputsWithAlternative");
			$this->error .= $this->getErrorFromArray($this->inputsWithConfirm, "global.forms.errorInputWithConfirm", "global.forms.errorInputsWithConfirm");
			$this->error .= $this->getErrorFromArray($this->inputsRequire, "global.forms.errorInputRequire", "global.forms.errorInputsRequire");
			$this->error .= $this->getErrorFromArray($this->inputsIncorrect, "global.forms.errorInputIncorrect", "global.forms.errorInputsIncorrect");
			return $this->error;
		}


		/**
		 * Méthode permettant de créer un message d'erreur é partir d'un tableau de champs invalides
		 * Récupére le message adéquate dans le Dictionnaire en fonction du nombre de champs
		 * Renvoie le message d'erreur pour le tableau en cours
		 * @param array	 $pArray		Tableau de champs invalides
		 * @param String $pLibelle		Identification du message au singulier (1 seul champ invalide)
		 * @param String $pLibelles		Identification du message au pluriel
		 * @return String
		 */
		private function getErrorFromArray($pArray, $pLibelle, $pLibelles)
		{
			$nb = count($pArray);
			if(!$nb)
				return "";
			$i = 0;
			$error = "";
			for(;$i<$nb;$i++)
			{
				if($i>0)
					$error.= ", ";
				if(!is_array($pArray[$i]))
					$error .= "<b>".(isset($this->data[$pArray[$i]]["errorLabel"])&&!empty($this->data[$pArray[$i]]["errorLabel"])?$this->data[$pArray[$i]]["errorLabel"]:$this->data[$pArray[$i]]["label"])."</b>";
				else
					$error .= "<b>".(isset($this->data[$pArray[$i][0]]["errorLabel"])&&!empty($this->data[$pArray[$i][0]]["errorLabel"])?$this->data[$pArray[$i][0]]["errorLabel"]:$this->data[$pArray[$i][0]]["label"])."</b> &amp; <b>".(isset($this->data[$pArray[$i][1]]["errorLabel"]) ? $this->data[$pArray[$i][1]]["errorLabel"] : $this->data[$pArray[$i][1]]["label"])."</b>";
			}
			if($nb==1)
			{
				$format = Dictionary::term($pLibelle.$pArray[0]);
				if ($format == Dictionary::UNDEFINED)
					$format = Dictionary::term($pLibelle);
			}
			else
			{
				$format = Dictionary::term($pLibelles);
			}
			return "<p>".sprintf($format, $error)."</p>";
		}


		/**
		 * Méthode de renommage des fichiers uploadés en fonction de l'id de l'entrée enregistrée
		 * Renvoi le tableau associatif des nouveaux de fichiers pour l'update de la base
         * @param string|int $pId
		 * @return array
		 */
		public function setUploadFileName($pId = null)
		{
            foreach($this->data as $name=>&$inp)
            {
                if(isset($inp['tag']) && $inp['tag'] == self::TAG_UPLOAD && isset($this->post[$name]) && !empty($this->post[$name]))
                {
                    if((!isset($inp['fileName'])) || (!preg_match('/(\{id\})/', $inp['fileName'])))
                        continue;
                    $folderName = self::PATH_TO_UPLOAD_FOLDER;
                    if(isset($inp['folder']))
                        $folderName .= $inp['folder'];
                    $fileName = preg_replace("/(\{id\})/",$pId, $inp["fileName"]);
                    $newPath = $folderName.$fileName;
                    $id_upload = $this->post[$name];
                    $m = new ModelUpload();
                    $m->renameById($id_upload, $newPath);
                }
            }
			$newFileName = array();
			$max = count($this->uploads);
			for($i = 0; $i<$max; ++$i)
			{
				$upload = $this->uploads[$i];
				$name = $upload["name"];
				/** @var Upload $up */
				$up = $upload["instance"];
                if(is_null($pId))
				    $pId = $up->id_upload;
				if($this->data[$name]["fileName"])
				{
					$fileName = preg_replace("/(\{id\})/",$pId,$this->data[$name]["fileName"]);
					$up->renameFile($fileName);
				}
				if(preg_match("/(\{id\})/",$this->data[$name]["folder"]))
				{
					$folderName = self::PATH_TO_UPLOAD_FOLDER.preg_replace("/(\{id\})/",$pId,$this->data[$name]["folder"]);
					$up->renameFolder($folderName);
				}
			}
			return $newFileName;
		}


		/**
		 * Méthode permettant d'injecter des valeurs dans le formulaire
		 * @param array $pValues				Tableau associatif des valeurs é injecter array(nomDuChamp=>valeur);
		 * @return void
		 */
		public function injectValues(array $pValues)
		{
			if(empty($pValues))
				return;
			foreach($pValues as $champs=>$value)
			{
				if(isset($this->data[$champs])&&!empty($this->data[$champs]))
				{
					if(isset($this->data[$champs]["attributes"]["type"])&&$this->data[$champs]["attributes"]["type"]=="checkbox")
					{
						if($this->data[$champs]["attributes"]["value"] == $value)
							$this->data[$champs]["attributes"]["checked"] = "checked";
						else
							unset($this->data[$champs]["attributes"]["checked"]);
						continue;
					}

					if (is_numeric($value)) $value = (string)$value;
					$this->data[$champs]["attributes"]["value"] = $value;
				}
			}
		}


		/**
		 * Méthode de pré-traitement du formulaire avant envoi &agrave; la vue (parsing des libellés, des types de balises, définition des js/css requis)
		 * @return void
		 */
		public function prepareToView()
		{
			$this->cleanData();
			if(isset($this->post))
				$this->injectValues($this->post);
			Autoload::addScript("Form");
			$this->countMendatory = 0;
			foreach($this->data as $name=>&$data)
			{
				switch($data["tag"])
				{
					case self::TAG_RICHEDITOR:
						trace("you must handle richeditor");
						break;
					case self::TAG_UPLOAD:
						Autoload::addComponent("Uploader");
						Autoload::addScript("M4Tween");
						$this->hasUpload = true;
						break;
					case self::TAG_INPUT:
						if(isset($data["attributes"]["type"])&&$data["attributes"]["type"]=="checkbox")
							unset($data["attributes"]["valueOff"]);
						if(isset($data["autoComplete"]))
                            Autoload::addComponent('Autocomplete');
						if(isset($data["attributes"]["type"])
							&& $data["attributes"]["type"]=="file")
						{
							Autoload::addComponent("Uploader");
							Autoload::addScript("M4Tween");
							$this->hasUpload = true;
						}else if(isset($data["attributes"]["type"])
							&& $data["attributes"]["type"]=="submit"
							&& Application::getInstance()->multiLanguage)
						{
							$data["attributes"]["value"] = Dictionary::term($data["attributes"]["value"]);
						}
						break;
					case self::TAG_DATEPICKER:
						Autoload::addComponent("WebCPicker");
						$this->hasDatePicker = true;
						break;
					case self::TAG_COLORPICKER:
						Autoload::addScript("jscolor/jscolor.js");
						$this->hasColorPicker = true;
						break;
					case self::TAG_RADIOGROUP:
					case self::TAG_CHECKBOXGROUP:
					case self::TAG_SELECT:
						if(isset($data["fromModel"])&&is_array($data["fromModel"]))
						{
							$condition = null;
							$fm = $data["fromModel"];
							if(isset($fm["condition"])&&is_array($fm["condition"]))
							{
								$condition = Query::condition();
								foreach($fm["condition"] as $method=>$parameters)
									call_user_func_array(array($condition, $method), $parameters);
							}
							$model = method_exists($fm["model"], "getInstance")? $fm["model"]::getInstance() : new $fm["model"]();
                            $method = $fm["method"]??"all";
                            $datas = $model->$method($condition);
							$options = array();
							$defaultChecked = isset($data["attributes"]["checked"]) && !isset($this->post);
							foreach($datas as $donnees)
							{
								if (is_object($donnees))
								{
									$donnees_name = $donnees->$fm["name"];
									$donnees_value = $donnees->$fm["value"];
								}
								else
								{
									$donnees_name = isset($donnees[$fm['name']])?$donnees[$fm["name"]]:'';
									$donnees_value = isset($donnees[$fm['value']])?$donnees[$fm["value"]]:'';
								}
								$options[] = array("name"=>$donnees_name, "label"=>$donnees_name, "value"=>$donnees_value, "checked"=>$defaultChecked);
							}
							if (!isset($data["options"]))
								$data["options"] = $options;
							else
								$data["options"] = array_merge($data["options"], $options);
						}
						break;
				}
				if($data["require"])
					$this->countMendatory++;
				if(isset($data["attributes"]["valueGet"]))
				{
					if (isset($_GET[$data["attributes"]["valueGet"]]))
					{
						$data["attributes"]["value"] = $_GET[$data["attributes"]["valueGet"]];
						unset($this->data[$name]["attributes"]["valueGet"]);
					}
					else
					{
						if(isset($data["attributes"]["type"]) && $data["attributes"]["type"] == "hidden")
							unset($this->data[$name]);
					}
				}
			}
		}

        /**
         * @param array|null $pParams
         */
		public function getValue(array $pParams = null)
		{
			$name = "";
			$toVar = false;
			if(!is_null($pParams))
				extract($pParams, EXTR_REFS);
			if($this->data[$name])
			{
				if(!$toVar)
					echo $this->data[$name]["attributes"]["value"];
			}
		}

        /**
         * @param array|null $pParams
         */
		public function isChecked(array $pParams = null)
		{
			$name = "";
			$toVar = false;
			if(!is_null($pParams))
				extract($pParams, EXTR_REFS);
			if($this->data[$name])
			{
				if(!$toVar)
					echo isset($this->data[$name]["attributes"]["checked"]) ? "checked" : "";
			}
		}

        /**
         * @param array|null $pParams
         */
		public function getOptions(array $pParams = null)
		{
			$name = "";
			$toVar = false;
			if(!is_null($pParams))
				extract($pParams, EXTR_REFS);
			if($this->data[$name])
			{
				if(!$toVar)
					echo $this->data[$name]["options"];
			}
		}

        /**
         * @param array|null $pParams
         */
		public function getLabel(array $pParams = null)
		{
			$name = "";
			$toVar = false;
			if(!is_null($pParams))
				extract($pParams, EXTR_REFS);
			if($this->data[$name])
			{
				if(!$toVar)
					echo $this->data[$name]["label"];
			}
		}


		/**
         * @param $pParams
         * @param $pReturn
		 * @return string|void
		 */
		public function display(array $pParams = null, $pReturn = false)
		{
			$noForm = false;
			$noMandatory = false;
            $inputs = false;
			$output = $idForm = $classes = $url = "";
            $controller = Core::$controller;
            $action = Core::$action;
			$helper = "core\\tools\\form\\FormHelpers";
			if(!is_null($pParams))
				extract($pParams, EXTR_REFS);
            else
                $pParams = array();

            $n = array();
            $s = array("controller", "action", "noForm", "noMandatory", "helper", "idForm", "classes", "url");
            foreach($pParams as $np=>$vp)
            {
                if(in_array($np, $s))
                    continue;
                $n[$np] = $vp;
            }
            $pParams = $n;

            if(empty($url))
            {
                $url = Core::rewriteURL($controller, $action, $pParams, Application::getInstance()->currentLanguage);
            }
            else
            {
                if(!empty($pParams))
                {
                    $url .= "?".http_build_query($pParams);
                }
            }

			if(!$noForm)
			{
				$output .= '<form action="'.$url.'" method="post"';
				if($this->hasUpload)
					$output .= ' enctype="multipart/form-data"';
				if (!empty($idForm))
					$output .= ' id="'.$idForm.'" ';
				if (!empty($classes))
					$output .= ' class="'.$classes.'" ';
				$output .='>';
			}
			if($this->countMendatory>0 && !$noMandatory)
			{
				$output .= "<div class='mandatory'>*&nbsp;";
				if($this->countMendatory==1)
					$output .= Dictionary::term("global.forms.inputRequire");
				else
					$output .= Dictionary::term("global.forms.inputsRequire");
				$output .= "</div>";
			}
			foreach($this->data as $n=>$d)
			{
                if($inputs !== false && !in_array($n, $inputs)){
                    continue;
                }
				$require = "";
				if($d["require"]===true)
					$require = "*";
				if(!isset($d["tag"]))
					continue;
				if($d["tag"] == "input"
					&& $d["attributes"]["type"] == "file")
				{
					$d["tag"] = self::TAG_UPLOAD;
				}

				$id = "inp_".$this->name."_".$n;
				if(strrpos($n, "[")>-1)
				{
					$n = str_replace("[", "][", $n);
					$name = $this->name."[".$n;
				}
				else
					$name = $this->name."[".$n."]";
				$d["form_name"] = $this->name;
				$d["field_name"] = $n;
				if(call_user_func_array(array($helper,"has"), array($d["tag"])))
					$output .= call_user_func_array($helper."::get", array($d["tag"], array($name, $id, $d, $require)));
			}

			if(!$noForm)
				$output .= "</form>";
			if($pReturn === true)
				return $output;
			echo $output;
            return true;
		}


		/**
		 * Méthode utilitaire permettant de vérifier si une chaine de caractéres correspond é l'expression régulière numérique
		 * @param String $pVar				Valeur é tester
		 * @return Boolean
		 */
		static public function isNumeric($pVar)
		{
			return preg_match(self::$regExp_Numeric, $pVar, $matches);
		}

		/**
		 * @static
		 * http://www.regular-expressions.info/dates.html
		 * @param $pVar
		 * @return Boolean
		 */
		static public function isDate($pVar)
		{
			if(!preg_match(self::$regExp_Date, $pVar, $matches))
				return false;
			$y = $matches[1] * 1;
			$m = $matches[3] * 1;
			$d = $matches[4] * 1;
			$month30 = array(4, 6, 9, 2);
			if($d == 31 && in_array($m, $month30))
				return false;
			if($d >= 30 && $m == 2)
				return false;
			return !($d == 29 && $m == 2 && !($y % 4 == 0  && ($y % 100 != 0 || $y % 400 != 0)));
		}


		/**
		 * Méthode de desactivation d'un input dans le formulaire en cours
		 * @param String $pName
		 * @return void
		 */
		public function unsetInput($pName)
		{
			if(!isset($this->data[$pName]))
				return;
			unset($this->data[$pName]);
		}


		/**
		 * Méthode de définition d'un input dans le formulaire en cours
		 * @param String $pName			Nom souhaité
		 * @param array $pDetails		Tableau des données propriétés de l'input
		 * @return void
		 */
		public function setInput($pName, $pDetails)
		{
			$this->data[$pName] = $pDetails;
		}

		/**
		 * Méthode de récupération d'un input du formulaire en cours
		 * @param String $pName
		 * @return array
		 */
		public function getInput($pName)
		{
			if(!isset($this->data) || empty($this->data) || !isset($this->data[$pName]) || empty($this->data[$pName]))
				return null;
			return $this->data[$pName];
		}

		/**
		 * @return array
		 */
		public function getInputs()
		{
			return $this->data;
		}
	}
}
