<?php
namespace core\application
{
	use core\tools\form\Form;
    use core\application\event\EventDispatcher;
    use core\tools\template\Template;

    /**
	 * Controller de base
	 *
	 * @author Arnaud NICOLAS <arno06@gmail.com>
	 * @version 1.1
	 * @package application
	 * @subpackage controller
	 */
	class DefaultController extends EventDispatcher
	{
		/**
		 * Tableau associatif des données qu'on souhaite envoyer &agrave; la vue.
		 * @var array
		 */
		private $content = array();


		/**
		 * Tableau associatif des données contenues entre les Balises Head de la vue.
		 * @var array
		 */
		private $head = array("title"=>"", "description"=>"");


		/**
		 * Tableau associatif des formulaires qu'on souhaite envoyer &agrave; la vue.
		 * @var array
		 */
		private $forms = array();


		/**
		 * Nom de la vue (template) qu'on souhaite afficher
		 * @var String
		 */
		private $template = "";


		/**
		 * @static
		 * @param  $pControllerName
		 * @param  $pActionName
		 * @param  $pUrl
		 * @return bool
		 */
		static public function isFromDB($pControllerName, $pActionName, $pUrl)
		{
			/** MUST BE OVERRIDEN */
			return false;
		}


		/**
		 * Méthode static d'initialisation des données avant l'envoi &agrave; la vue dans le cas de contenu dynamique
		 * @return void
		 */
		public function prepareFromDB()
		{
			/** MUST BE OVERRIDEN */
			Go::to404();
		}


        /**
         * Méthode par défaut de page introuvable
         */
        public function notFound()
        {
            if(get_called_class() != __CLASS__)
            {
                Go::to404();
            }
            $this->setTemplate(null, null, 'notFound.tpl');
        }


		/**
		 * Méthode public de rendu de la page en cours
		 * @param bool $pDisplay
		 * @return string
		 */
		public function render($pDisplay = true)
		{
            $props = get_class_vars('core\application\Configuration');
            $conf = array();
            foreach($props as $name=>$val){
                $conf[$name] = Configuration::$$name;
            }
            $terms = Dictionary::terms();
			$globalVars = $this->getGlobalVars();
            $t = new Template();
            $t->assign("configuration", $conf);
			foreach($globalVars as $n=>&$v)
			{
                $t->assign($n, $v);
			}
            $global = array('get'=>$_GET, 'post'=>$_POST);
            $t->assign('global', $global);
            $t->assign("dictionary", $terms);
            $t->assign("request_async", Core::$request_async);
            $t->assign("form", $this->forms);
            return $t->render($this->template, $pDisplay);
		}


		/**
		 * @return array
		 */
		public function getGlobalVars()
		{
			$is = array();
            $authHandler = Application::getInstance()->authenticationHandler;
            foreach($authHandler::$permissions as $name=>$value)
                $is[$name] = $authHandler::$data&&$authHandler::is($name);
			return array(
				"path_to_components"=>Core::$path_to_components,
				"scripts"=>Autoload::scripts(),
				"styles"=>Autoload::styles(),
				"head"=>$this->head,
				"forms"=>$this->forms,
				"content"=>$this->content,
				"user_is"=>$is,
				"controller"=>preg_replace("/\_/", "-", Core::$controller),
				"action"=>preg_replace("/\_/", "-", Core::$action)
			);
		}


		/**
		 * Méthode d'ajout de script &agrave; la vue.
		 * @param String $pScript				Nom du fichier JS
		 * @return void
		 */
		protected function addScript($pScript)
		{
			Autoload::addScript($pScript);
		}


		/**
		 * Méthode d'ajout de feuille de style &agrave; la vue
		 * @param String $pStyle				Nom du fichier CSS
		 * @return void
		 */
		protected function addStyle($pStyle)
		{
			Autoload::addStyle($pStyle);
		}


		/**
		 * Méthode d'ajout d'une variable de contenu envoyé &agrave; la vue
		 * @param String $pTemplateVar				Nom d'acc&egrave;s &agrave; la variable
		 * @param mixed $pContent					Valeur de la variable acc&egrave;s tout type (String, Object, array, int...)
		 * @return void
		 */
		protected function addContent($pTemplateVar, $pContent)
		{
			$this->content[$pTemplateVar]=$pContent;
		}

		/**
		 * Méthode de récupération du contenu d'une variable
		 * @param String $pTemplateVar
		 * @return mixed
		 */
		protected function getContent($pTemplateVar)
		{
			if(!isset($this->content[$pTemplateVar]))
				return "";
			return $this->content[$pTemplateVar];
		}


		/**
		 * Méthode d'ajout d'un formulaire envoyé &agrave; la vue
		 * @param String $pName				Nom d'acc&egrave;s au formulaire
		 * @param Form $pForm
		 * @return void
		 */
		protected function addForm($pName, Form &$pForm)
		{
			$pForm->prepareToView();
			$this->forms[$pName] = $pForm;
		}


		/**
		 * Méthode de définition de la valeur pour la balise Title contenue entre les balises Head de la vue
		 * @param String $pTitle				SEO : 5 mots de longueur moyenne pour 70 caract&egrave;res espace compris
		 * @return void
		 */
		public function setTitle($pTitle)
		{
			$this->head['title'] = $pTitle;
		}


		/**
		 * Méthode définition de la valeur pour la balise Meta - description - contenue entre les balises Head de la vue
		 * @param String $pDescription				SEO : 150 caract&egrave;res espace compris
		 * @return void
		 */
		public function setDescription($pDescription)
		{
			$this->head['description'] = $pDescription;
		}


		/**
		 * @param $pFolder
		 * @param $pName
		 * @param string $pFile
		 * @return void
		 */
		public function setTemplate($pFolder, $pName, $pFile = "")
		{
			if(!empty($pFile))
				$this->template = $pFile;
			else
				$this->template = $pFolder."/".$pName.".tpl";
		}

		/**
		 * Destructor
		 * @return void
		 */
		public function __destruct()
		{
			unset($this->forms);
			unset($this->content);
			unset($this->head);
			$this->removeAllEventListeners();
		}
	}
}
