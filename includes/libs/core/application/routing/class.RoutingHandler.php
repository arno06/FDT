<?php
namespace core\application\routing
{

    use core\application\Application;
    use core\application\Configuration;
	use core\application\Core;
	use core\application\Go;
    use core\application\Module;
    use core\data\SimpleJSON;
	use \Exception;

	/**
	 * Class RoutingHandler - gestionnaire par défault de réécriture d'url
	 *
	 * @author Arnaud NICOLAS <arno06@gmail.com>
	 * @version 1.2
	 * @package application
	 * @subpackage routing
	 */
	abstract class RoutingHandler
	{
        const HTTP_METHOD_WILDCARD = "*";
		const REGEXP_LANGUAGE   = '/^([a-z]{2,3})\//';
		const REGEXP_CONTROLLER = '/^([a-z\-]{1,})\//';
		const REGEXP_ACTION     = '/^([a-z\-]{1,})\//';
		const REGEXP_PARAMETERS = '/^(([a-z][a-z0-9\_\-]*:.*)*)\//i';

		/**
		 * Méthode de parsing d'une url
		 * Renvoie nécessairement un tableau contenant les informations :  controller - action - application - paramètre - langue
		 * @param String $pUrl		Url à parser
		 * @return array
		 */
		static public function parse($pUrl)
		{
            $static = preg_match('/^statique\//', $pUrl, $matches);

			if(!$static && Application::getInstance()->getModule()->useRoutingFile)
			{
				return self::handleRoutingRules($pUrl);
			}

			$parameters = array();
			$request_url = $pUrl;
			$controller = self::extractController($request_url);

			if(!empty($controller))
			{
				$action = self::extractAction($request_url);
				if(empty($action))
					$action = "index";
				$parameters = self::extractParameters($request_url);
			}
			else
			{
				$controller = "index";
				$action = "index";
			}
			return array("controller"=>$controller,
				"action"=>$action,
				"parameters"=>$parameters);
		}

        /**
         * Méthode de définition du contexte en fonction du fichier de routing & de l'url
         * @param string $pUrl
         * @return array|null
         */
		static private function handleRoutingRules($pUrl)
		{
            if(empty($pUrl))
                $pUrl = "/";

			$request_url = $pUrl;

			$rules_file = Core::$path_to_application."/src/routing_rules.json";
			try
			{
				$rules = SimpleJSON::import($rules_file);
			}
			catch(Exception $e)
			{
				return null;
			}

			$index_parameters = array();

            $rules = $rules[Core::$module];

            if(!$rules)
            {
                trigger_error("Le module '".Core::$module."' ne dispose d'aucune règle dans le fichier de routing.", E_USER_ERROR);
            }

            $request_method = $_SERVER['REQUEST_METHOD'];

            $final_rule = null;

            foreach($rules as $url=>$rule)
            {
                $re_url = $url;
                $re_url = str_replace('/', '\/', $re_url);
                $re_url = str_replace('.', '\.', $re_url);
                $index_param = 0;
                if(!isset($rule["parameters"]))
                    $rule["parameters"] = array();
                $parameters = array();
                foreach($rule["parameters"] as $name=>$re)
                {
                    if(!preg_match('/\{\$'.$name.'\}/', $re_url))
                    {
                        $parameters[$name] = $re;
                        continue;
                    }

                    $index_parameters[++$index_param] = $name;
                    $re_url = preg_replace('/\{\$'.$name.'\}/', '('.$re.')', $re_url);
                }
                $re_url = "/^".$re_url.'$/';

                if(!preg_match($re_url, $request_url, $matches))
                    continue;

                for($k = 1, $maxk = count($matches); $k<$maxk; $k++)
                {
                    $parameters[$index_parameters[$k]] = $matches[$k];
                }
                unset($rule['parameters']);

                if(isset($rule[$request_method]) && !empty($rule[$request_method]))
                {
                    $final_rule = $rule[$request_method];
                }
                else
                {
                    $allowed_methods = array_keys($rule);
                    foreach($allowed_methods as $m)
                    {
                        if(preg_match('/\|*'.$request_method.'\|*/', $m, $matches))
                        {
                            $final_rule = $rule[$m];
                        }
                    }
                }

                if(!$final_rule && isset($rule[self::HTTP_METHOD_WILDCARD]) && !empty($rule[self::HTTP_METHOD_WILDCARD]))
                {
                    $final_rule = $rule[self::HTTP_METHOD_WILDCARD];
                }

                if(!$final_rule)
                {
                    return null;
                }


                if(isset($parameters["controller"])&&!empty($parameters["controller"]))
                {
                    $final_rule["controller"] = $parameters["controller"];
                    unset($parameters["controller"]);
                }
                if(isset($parameters["action"])&&!empty($parameters["action"]))
                {
                    $final_rule["action"] = $parameters["action"];
                    unset($parameters["action"]);
                }

                $final_rule['parameters'] = $parameters;

                return $final_rule;
            }
			return null;
		}


		/**
		 * Méthode d'écriture d'une URL
		 * @static
		 * @param string $pController
		 * @param string $pAction
		 * @param array $pParams
		 * @param string $pLangue
		 * @return string
		 */
		static public function rewrite($pController = "", $pAction = "", $pParams = array(), $pLangue = "")
		{
			$pController =  self::getAlias($pController);
			$pAction = self::getAlias($pAction);
			$return = "";
            if(Application::getInstance()->multiLanguage)
            {
                $return .= ((!isset($pLangue)||empty($pLangue))?Application::getInstance()->currentLanguage:$pLangue)."/";
            }
			if(!empty($pController))
				$return .= $pController."/";
			if(!empty($pAction))
				$return .= $pAction."/";
			if(isset($pParams)&&is_array($pParams))
			{
				foreach($pParams as $name=>$value)
					$return .= $name.":".urlencode($value)."/";
			}
			return $return;
		}


		/**
		 *
		 * @param String $pValue
		 * @return string
		 */
		static public function getAlias($pValue = "")
		{
            return preg_replace('/(\_)/', "-", $pValue);
		}

        /**
         * @param $pUrl
         * @return mixed|string
         */
		static public function extractApplication(&$pUrl)
		{
			$folder = preg_replace('/(\/)/', '\/', Configuration::$server_folder);
			$pUrl = preg_replace('/^(\/'.(!empty($folder)?$folder.'\/':"").")/","",$pUrl);
			$applications = array_keys(Configuration::$applications);
            $application = self::shift($pUrl, '/^('.implode("|", $applications).')\//');
            if($application == Application::DEFAULT_APPLICATION)
                Go::to();
            if($application === false)
                $application = Application::DEFAULT_APPLICATION;
			return $application;
		}

        /**
         * @param $pUrl
         * @param $pAvailableModule
         * @return string
         */
		static public function extractModule(&$pUrl, $pAvailableModule = array('default'))
		{
            $modules = implode("|", $pAvailableModule);
            $modules = str_replace('_', '-', $modules);
            $module = self::shift($pUrl, '/^('.$modules.')\//');
            if($module == Module::DEFAULT_MODULE)
                Go::to();
            if($module === false)
                $module = Module::DEFAULT_MODULE;
            $module = str_replace('-', '_', $module);
            return $module;
		}


		/**
		 * @static
		 * @param  $pURL
		 * @return bool|string
		 */
		static public function extractLanguage(&$pURL)
		{

			if(Application::getInstance()->multiLanguage&&!preg_match("/^statique/",$pURL, $matches))
			{
				$language = self::shift($pURL, self::REGEXP_LANGUAGE);
				if(!$language)
					Go::to("","",array(), Application::getInstance()->defaultLanguage);
				return $language;
			}
			return Application::getInstance()->defaultLanguage;
		}


		/**
		 * @static
		 * @param  $pURL
		 * @return bool|String
		 */
		static public function extractController(&$pURL)
		{
			$controller = self::shift($pURL, self::REGEXP_CONTROLLER);
			return $controller;
		}


		/**
		 * @static
		 * @param  $pURL
		 * @return bool|String
		 */
		static public function extractAction(&$pURL)
		{
			$action = self::shift($pURL, self::REGEXP_ACTION);
			return $action;
		}


		/**
		 * Méthode permettant de dépiler une chaine de caractères de l'url passée en paramètre et respectant l'expression régulière souhaitée
		 * @static
		 * @param  $pURL
		 * @param  $pRegExp
		 * @return bool|string
		 */
		static public function shift(&$pURL, $pRegExp)
		{
			if(isset($pURL)&&preg_match($pRegExp, $pURL, $extract, PREG_OFFSET_CAPTURE))
			{
				if(!isset($extract[1][0]))
					return false;
				$r = str_replace("/", '\/', $extract[1][0]);
				$pURL = preg_replace("/^".$r.'\//', "", $pURL);
				return $extract[1][0];
			}
			return false;
		}


		/**
		 * @static
		 * @param  $pUrl
		 * @return array
		 */
		static public function extractParameters(&$pUrl)
		{
			$parameters = array();
			if(empty($pUrl))
				return $parameters;
			$p = self::shift($pUrl, mb_convert_encoding(self::REGEXP_PARAMETERS, 'UTF-8'));
			$params = explode("/",$p);
			$max = count($params);
			for($i=0;$i<$max;$i++)
			{
				$params[$i] = urldecode($params[$i]);
				$param = explode(":",$params[$i]);
				if(isset($param[0])&&isset($param[1]))
				{
					$value = $param[1];
					for($j=2,$maxJ=count($param);$j<$maxJ;$j++)
						$value.=":".$param[$j];
					$parameters[$param[0]] = $value;
				}
			}
			if(isset($parameters["q"]))
				$parameters["q"] = urldecode($parameters["q"]);
			return $parameters;
		}


		/**
		 * Méthode permettant de filtrer une chaine de caractères pour son utilisation dans une url
		 * @param String $pTexte			Chaine de caractères a filtrer
		 * @param bool $pLower
		 * @return String
		 */
		static public function sanitize($pTexte, $pLower = true)
		{
			$chars = array(
				"ç"=>"c",
				"â"=>"a",
				"à"=>"a",
				"ä"=>"a",
				"é"=>"e",
				"è"=>"e",
				"ê"=>"e",
				"ë"=>"e",
				"ì"=>"i",
				"ï"=>"i",
				"î"=>"i",
				"ù"=>"u",
				"ü"=>"u",
				"û"=>"u",
				"ô"=>"o",
				"ò"=>"o",
				"ö"=>"o",
				"ÿ"=>"y",
				"æ"=>"ae"
			);

			foreach($chars as $key=>$change)
			{
				$pTexte = str_replace($key, $change, $pTexte);
				$pTexte = str_replace(mb_strtoupper($key, Configuration::$global_encoding), mb_strtoupper($change, Configuration::$global_encoding), $pTexte);
			}
			if ($pLower) $pTexte = strtolower($pTexte);
			$pTexte = preg_replace("/[\s]/i", "-", $pTexte);
			$pTexte = preg_replace("/[^\_0-9a-z]/i", "-", $pTexte);
			$pTexte = preg_replace(array("/^-+/", "/-+$/", "/-+/"), array("", "", "-"), $pTexte);
			return $pTexte;
		}
	}
}
