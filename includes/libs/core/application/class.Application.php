<?php
namespace core\application
{
    /**
     * Class Application
     * @author Arnaud NICOLAS <arno06@gmail.com>
     * @version 1.0
     * @package core\application
     */
    class Application extends Singleton
    {
        const DEFAULT_APPLICATION = "main";

        /**
         * @var string
         */
        private $name = "";

        /**
         * @var Module
         */
        private $module;

        /**
         * @var string
         */
        private $url = "";

        /**
         * @var string
         */
        private $relative_path = "";

        /**
         * @var bool
         */
        public $multiLanguage = false;

        /**
         * @var string
         */
        public $currentLanguage = "fr";

        /**
         * @var string
         */
        public $defaultLanguage = "fr";

        /**
         * @var string
         */
        public $authenticationHandler = "core\\application\\authentication\\AuthenticationHandler";

        /**
         * @param PrivateClass $pInstance
         */
        public function __construct(PrivateClass $pInstance)
        {
            if(!$pInstance instanceOf PrivateClass)
                trigger_error("Il est interdit d'instancier un objet de type <i>Singleton</i> - Merci d'utiliser la m�thode static <i>".__CLASS__."::getInstance()</i>", E_USER_ERROR);
        }

        /**
         * @param string $pName
         * @return $this
         */
        public function setup($pName = self::DEFAULT_APPLICATION)
        {
            $this->name = $pName;
            if($pName != self::DEFAULT_APPLICATION)
            {
                $this->url .= $pName."/";
                $this->relative_path .= "../";
            }
            if(!Configuration::$applications[$this->name])
            {
                trigger_error("L'application ".$this->name." n'a pas �t� d�finie dans le fichier de configuration.", E_USER_ERROR);
            }

            $data = Configuration::$applications[$this->name];

            $props = get_class_vars(__CLASS__);
            foreach($props as $n=>$p)
            {
                if(isset($data[$n]))
                {
                    $this->{$n} = $data[$n];
                }
            }
            return $this;
        }

        /**
         * @param string $pName
         */
        public function setModule($pName = Module::DEFAULT_MODULE)
        {
            if($pName != Module::DEFAULT_MODULE)
            {
                $this->url .= str_replace('_', '-', $pName)."/";
                $this->relative_path .= "../";
            }
            $data = Configuration::$applications[$this->name]['modules'][$pName];
            $this->module = new Module($pName, $data);
        }

        /**
         * @return array
         */
        public function getModulesAvailable()
        {
            return array_keys(Configuration::$applications[$this->name]['modules']);
        }

        /**
         * @return string
         */
        public function getUrlPart()
        {
            return $this->url;
        }

        /**
         * @return string
         */
        public function getPathPart()
        {
            return $this->relative_path;
        }

        /**
         * @return string
         */
        public function getTemplatesCachePath()
        {
            return $this->getFilesPath()."/_cache/".$this->module->name;
        }

        /**
         * @return string
         */
        public function getTemplatesPath()
        {
            return $this->getFilesPath()."/modules/".$this->module->name."/views";
        }

        /**
         * @return string
         */
        public function getFilesPath()
        {
            return Autoload::$folder."/includes/applications/".$this->name;
        }

        /**
         * @return Module
         */
        public function getModule()
        {
            return $this->module;
        }

        /**
         * @return string
         */
        public function __toString()
        {
            return $this->name;
        }

    }

    /**
     * Class Module
     * @author Arnaud NICOLAS <arno06@gmail.com>
     * @version 1.0
     * @package core\application
     */
    class Module
    {
        const DEFAULT_MODULE = "front";

        /**
         * @var string
         */
        public $name = self::DEFAULT_MODULE;

        /**
         * @var bool
         */
        public $useRoutingFile = true;

        /**
         * @var DefaultController
         */
        public $defaultController = "core\\application\\DefaultController";

        /**
         * @var string
         */
        public $action404 = "not_found";

        /**
         * @param string $pName
         * @param array $pData
         */
        public function __construct($pName, $pData)
        {
            $this->name = $pName;
            $props = get_class_vars(__CLASS__);
            foreach($props as $n=>$p)
            {
                if(isset($pData[$n]))
                {
                    $this->{$n} = $pData[$n];
                }
            }
        }
    }
}
