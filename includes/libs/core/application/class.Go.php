<?php
namespace core\application
{
	/**
	 * Class Go
	 *
	 * @author Arnaud NICOLAS <arno06@gmail.com>
	 * @version 1.0
	 * @package application
	 */
	class Go
	{
		/**
		 * @static
		 * @return void
		 */
		static public function to404()
		{
            $defaultController = Core::$application->getModule()->defaultController;
			$controller = new $defaultController();
			Header::http("1.0 404 Not Found");
			Header::status("404 Not Found");
			Core::execute($controller, Core::$application->getModule()->action404);
			Core::endApplication();
		}


		/**
		 * @static
		 * @param string $pController
		 * @param string $pAction
		 * @param array $pParams
		 * @param string $pLangue
		 * @param int $pCode
		 * @return void
		 */
		static public function to($pController = "", $pAction = "", $pParams = array(), $pLangue = "", $pCode = 301)
		{
			$rewriteURL = Configuration::$server_url;
			$rewriteURL .= Core::rewriteURL($pController, $pAction, $pParams, $pLangue);
			Header::location($rewriteURL, $pCode);
		}


		/**
		 * @static
         * @param string $pModule
		 * @param string $pController
		 * @param string $pAction
		 * @param array $pParams
		 * @return void
		 */
		static public function toModule($pModule = 'front', $pController = "", $pAction = "", $pParams = array())
		{
			$rewriteURL = Configuration::$server_url.$pModule."/";
			$rewriteURL .= Core::rewriteURL($pController, $pAction, $pParams);
			Header::location($rewriteURL);
		}
	}
}
