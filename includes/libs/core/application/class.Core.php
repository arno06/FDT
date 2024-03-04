<?php
namespace core\application {

    use core\data\SimpleJSON;
    use core\tools\debugger\Debugger;
    use core\db\DBManager;
    use core\application\routing\RoutingHandler;
    use core\utils\CLI;
    use \Exception;


    /**
     * Noyau central
     *
     * @author Arnaud NICOLAS <arno06@gmail.com>
     * @version 3.0
     * @package application
     */
    abstract class Core
    {
        /**
         * Version en cours du framework
         */
        const VERSION = "4.0";

        /**
         * Erreur de configuration
         */
        const ERROR_CONFIG = "Unable to parse the base configuration file \"includes/applications/config.json\". Please check the data formatting (quotation marks, commas, accents ...).";

        /**
         * @var string
         */
        static public $config_file = null;

        /**
         * Définit le chemin vers le dossier de l'application en cours
         * @var String
         */
        static public $path_to_application;

        /**
         * @var string
         */
        static public $path_to_components = "includes/components";

        /**
         * Contient l'url requêtée (sans l'application ni la langue)
         * @var String
         */
        static public $url;

        /**
         * @var Application
         */
        static public $application;

        /**
         * Définit le module en cours - front ou back
         * @var String
         */
        static public $module;

        /**
         * Définit le nom du controller
         * @var String
         */
        static public $controller = "";

        /**
         * Définit le nom de l'action
         * @var String
         */
        static public $action = "";

        /**
         * Fait référence &agrave; l'instance du controller en cours
         * @var DefaultController
         */
        static private $instance_controller;

        /**
         * @var bool
         */
        static public $request_async = false;

        /**
         * Initialisation du Core applicatif du framework
         * @return void
         */
        static public function init()
        {
            session_name(Configuration::$global_session);
            session_start();
            set_error_handler('\core\tools\debugger\Debugger::errorHandler');
            set_exception_handler('\core\tools\debugger\Debugger::exceptionHandler');
            self::$request_async = (isset($_SERVER["HTTP_X_REQUESTED_WITH"]) &&
                $_SERVER["HTTP_X_REQUESTED_WITH"] == "XMLHttpRequest");
        }

        /**
         * Instanciation des objects globlaux de l'application
         * @return void
         */
        static public function defineGlobalObjects()
        {
            if (self::debug())
                Debugger::prepare();
        }

        /**
         * Méthode d'identification de l'environnement en fonction du domaine
         * @param string $pFile
         * @throws \Exception
         */
        static public function checkEnvironment($pFile = "includes/applications/setup.json")
        {
            $setup = SimpleJSON::import($pFile);
            self::$config_file = "/includes/applications/dev.config.json";
            if (!$setup) {
                return;
            }
            foreach ($setup as $env => $domains) {
                foreach ($domains as $domain) {
                    if((isset($_SERVER["SERVER_NAME"])&&$_SERVER["SERVER_NAME"] === $domain)|| (CLI::isCurrentContext() && $domain == PHP_SAPI)){
                        self::$config_file = "/includes/applications/" . $env . ".config.json";
                        break 2;
                    }
                    else if (strpos($domain, "*") === 0) {
                        $domain = str_replace("*", "", $domain);
                        $domain = str_replace(".", "\.", $domain);
                        if (preg_match('/' . $domain . '$/', $_SERVER["SERVER_NAME"], $matches)) {
                            self::$config_file = "/includes/applications/" . $env . ".config.json";
                            break 2;
                        }
                    }
                }
            }
            self::setConfiguration();
        }

        /**
         * Méthode statique de définition de l'objet Configuration via le fichier JSON
         * Récupération + parsing du fichier JSON
         * Défintition des propriétés statiques de l'objet Configuration
         * @param String $pConfigurationFile Url du fichier de configuration
         * @return Void
         */
        static public function setConfiguration($pConfigurationFile = null)
        {
            if (is_null($pConfigurationFile))
                $pConfigurationFile = Autoload::$folder . self::$config_file;
            $configurationData = array();
            try {
                $configurationData = SimpleJSON::import($pConfigurationFile);
            } catch (Exception $e) {}
            if ((!is_array($configurationData) || empty($configurationData)) && $pConfigurationFile == Autoload::$folder . self::$config_file)
                trigger_error(self::ERROR_CONFIG, E_USER_ERROR);

            foreach ($configurationData as $prefix => $property) {
                if (property_exists('core\application\Configuration', $prefix)) {
                    Configuration::$$prefix = $property;
                    continue;
                }
                if (is_array($property)) {
                    foreach ($property as $name => $value) {
                        $n = $prefix . "_" . $name;
                        if (property_exists('core\application\Configuration', $n))
                            Configuration::$$n = $value;
                    }
                }
            }
            if (isset($configurationData['extra']) && !empty($configurationData['extra'])) {
                Configuration::setExtra($configurationData['extra']);
            }
        }

        /**
         * @static
         * @param string $pController
         * @param string $pAction
         * @param array $pParams
         * @param string $pLangue
         * @return mixed
         */
        static public function rewriteURL($pController = "", $pAction = "", $pParams = array(), $pLangue = "")
        {
            return RoutingHandler::rewrite($pController, $pAction, $pParams, $pLangue);
        }

        /**
         * Méthode de parsing de l'url en cours
         * récupère le controller, l'action, la langue (si multilangue) ainsi que les paramètres $_GET
         * @param $pUrl
         * @return void
         */
        static public function parseURL($pUrl = null)
        {
            Configuration::$server_domain = $_SERVER["SERVER_NAME"];
            $protocol = "http" . (self::isHttps() ? 's' : '') . "://";
            Configuration::$server_folder = preg_replace('/\/(index).php$/', "", $_SERVER["SCRIPT_NAME"]);
            Configuration::$server_folder = preg_replace('/^\//', "", Configuration::$server_folder);
            Configuration::$server_url = $protocol . Configuration::$server_domain . "/";
            if (!empty(Configuration::$server_folder))
                Configuration::$server_url .= Configuration::$server_folder . "/";

            /**
             * Définition de l'url + suppression des paramètres GET ?var=value
             */
            $url = isset($pUrl) && !is_null($pUrl) ? $pUrl : $_SERVER["REQUEST_URI"];
            if (preg_match("/([^\?]*)\?.*$/", $url, $matches)) {
                $url = $matches[1];
            }

            $application_name = RoutingHandler::extractApplication($url);

            self::$application = Application::getInstance()->setup($application_name);
            self::$application->setModule(RoutingHandler::extractModule($url, self::$application->getModulesAvailable()));
            self::$module = self::$application->getModule()->name;

            Configuration::$server_url .= self::$application->getUrlPart();

            $access = self::$application->getPathPart();

            self::$path_to_components = Configuration::$server_url . $access . self::$path_to_components;

            self::defineGlobalObjects();


            if (self::$application->multiLanguage) {
                self::$application->currentLanguage = RoutingHandler::extractLanguage($url);

                if (empty(self::$application->currentLanguage)) {
                    self::$application->currentLanguage = self::$application->defaultLanguage;
                    Header::location(Configuration::$server_url . self::$application->currentLanguage . "/" . $url);
                }
            }

            self::$path_to_application = Application::getInstance()->getFilesPath();

            self::setDictionary();

            self::$url = $url;

            $parsedURL = RoutingHandler::parse($url);

            if(is_null($parsedURL)){
                return;
            }

            self::$controller = str_replace("-", "_", $parsedURL["controller"]);
            self::$action = str_replace("-", "_", $parsedURL["action"]);

            if (isset($parsedURL["parameters"]) && is_array($parsedURL["parameters"]) && is_array($_GET)) {
                $_GET = array_merge($_GET, $parsedURL["parameters"]);
            }
        }

        /**
         * Méthode vérifiant l'existance et retournant une nouvelle instance du controller récupéré
         * Renvoie vers la page d'erreur 404 si le fichier contenant le controller n'existe pas
         * Stop l'application et renvoie une erreur si le fichier existe mais pas la classe demandée
         * @return DefaultController
         */
        static public function getController()
        {
            if (Core::$controller === "statique") {
                self::$instance_controller = new StaticController();
                return self::$instance_controller;
            }
            $seo = Dictionary::seoInfos(self::$controller, self::$action);
            $controller_file = self::$path_to_application . "/modules/" . self::$module . "/controllers/controller." . self::$controller . ".php";
            $controller = 'app\\' . self::$application . '\\controllers\\' . self::$module . '\\' . self::$controller;
            if (!file_exists($controller_file)) {
                $defaultController = self::$application->getModule()->defaultController;
                if (call_user_func_array(array($defaultController, "isFromDB"), array(self::$controller, self::$action, self::$url))) {
                    $controller = self::$controller = $defaultController;
                    self::$action = "prepareFromDB";
                } else
                    Go::to404();
            } else
                include_once($controller_file);
            if (!class_exists($controller)) {
                if (self::debug())
                    trigger_error("Controller <b>" . self::$controller . "</b> introuvable", E_USER_ERROR);
                else
                    Go::to404();
            }
            self::$instance_controller = new $controller();
            if (isset($seo["title"]))
                self::$instance_controller->setTitle($seo["title"]);
            if (isset($seo["description"]))
                self::$instance_controller->setDescription($seo["description"]);
            return self::$instance_controller;
        }

        /**
         * Méthode permettant de définir le dictionnaire en fonction d'un fichier de langue
         * @return void
         */
        static public function setDictionary()
        {
            $dictionary_path = self::$path_to_application . "/localization/" . Application::getInstance()->currentLanguage . ".json";
            try {
                $data = SimpleJSON::import($dictionary_path);
            } catch (Exception $e) {
                if (self::debug())
                    trigger_error('Fichier de langue "<b>' . $dictionary_path . '</b>" introuvable', E_USER_ERROR);
                else {
                    Application::getInstance()->currentLanguage = Application::getInstance()->defaultLanguage;
                    Go::to404();
                }
            }
            $seo = array();
            $terms = array();
            $alias = array();
            if (isset($data["terms"]) && is_array($data["terms"]))
                $terms = $data["terms"];
            if (isset($data["seo"]) && is_array($data["seo"]))
                $seo = $data["seo"];
            if (isset($data["alias"]) && is_array($data["alias"]))
                $alias = $data["alias"];
            Dictionary::defineLanguage(Application::getInstance()->currentLanguage, $terms, $seo, $alias);
        }


        /**
         * Méthode vérifiant l'existance de la méthode action dans la classe controller précédemment instanciée
         * @return String
         */
        static public function getAction()
        {
            if (!method_exists(self::$instance_controller, self::$action))
                Go::to404();
            return self::$action;
        }


        /**
         * Méthode de récupération du template par défault en fonction du controller et de l'action demandée
         * @return String
         */
        static public function getTemplate()
        {
            return self::$controller . "/" . self::$action . ".tpl";
        }


        /**
         * Méthode de vérification si l'application est disponible en mode développeur (en fonction du config.json et de l'authentication)
         * @return bool
         */
        static public function debug()
        {
            $authHandler = Application::getInstance()->authenticationHandler;
            return Configuration::$global_debug || call_user_func_array(array($authHandler, "is"), array($authHandler::DEVELOPER));
        }


        /**
         * @static
         * @return bool
         */
        static public function isBot()
        {
            $ua = $_SERVER["HTTP_USER_AGENT"];
            $UA_bots = array('Googlebot\/', 'bingbot\/', "Yahoo");
            for ($i = 0, $max = count($UA_bots); $i < $max; $i++) {
                if (preg_match("/" . $UA_bots[$i] . "/i", $ua, $matches))
                    return true;
            }
            return false;
        }

        /***
         * Méthode permettant d'afficher simplement un contenu sans passer par le système de templating
         * Sert notamment dans le cadre de requêtes asychrones (avec du Flash ou du JS par exemple)
         * @param string $pContent Contenu &agrave; afficher
         * @param string $pType [optional]    Type de contenu &agrave; afficher - doit être spécifié pour assurer une bonne comptatilité &agrave; l'affichage
         * @return void
         */
        static public function performResponse($pContent, $pType = "text")
        {
            $pType = strtolower($pType);
            switch ($pType) {
                case "json":
                    $content = "application/json";
                    break;
                case "xml":
                    $content = "application/xml";
                    break;
                case "text":
                default:
                    $content = "text/plain";
                    break;
            }
            Header::contentType($content);
            echo $pContent;
            self::endApplication();
        }

        /**
         * Méthode de vérification de l'existance de variables GET
         * @return bool
         */
        static public function checkRequiredGetVars()
        {
            $gets = func_get_args();
            for ($i = 0, $max = count($gets); $i < $max; $i++) {
                if (!isset($_GET[$gets[$i]]) || empty($_GET[$gets[$i]]))
                    return false;
            }
            return true;
        }

        /**
         * @static
         * @param DefaultController|null $pController
         * @param null $pAction
         * @param string $pTemplate
         * @return void
         */
        static public function execute(DefaultController $pController = null, $pAction = null, $pTemplate = "")
        {
            if ($pController != "statique")
                $pController->setTemplate(self::$controller, self::$action, $pTemplate);
            if (!is_null($pAction))
                $pController->$pAction();
            if (!Core::$request_async) {
                Header::contentType("text/html");
                $pController->render();
                if (Core::debug())
                    Debugger::getInstance()->render();
            } else {
                $return = $pController->getGlobalVars();
                if (Core::debug()) {
                    $return = array_merge($return, Debugger::getInstance()->getGlobalVars());
                }
                if (Core::checkRequiredGetVars('render') && $_GET["render"] !== "false")
                    $return["html"] = $pController->render(false);
                $response = SimpleJSON::encode($return);
                $type = "json";
                self::performResponse($response, $type);
            }
        }

        /**
         * @return bool
         */
        static public function isHttps(){
            return (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == '443')
                || isset($_SERVER['HTTP_X_FORWARDED_PORT']) && $_SERVER['HTTP_X_FORWARDED_PORT'] == '443';
        }

        /**
         * Méthode appelée afin de clore l'application
         * @param int $pExitCode
         * @return void
         */
        static public function endApplication($pExitCode = 0)
        {
            self::$instance_controller = null;
            self::$action = null;
            self::$controller = null;
            self::$application = null;
            self::$module = null;
            self::$path_to_application = null;
            self::$path_to_components = null;
            Singleton::dispose();
            DBManager::dispose();
            exit($pExitCode);
        }
    }
}
