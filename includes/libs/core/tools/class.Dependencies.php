<?php
namespace core\tools
{

    use core\application\Application;
    use core\application\Core;
    use core\application\Header;
    use core\system\File;
    use core\data\SimpleJSON;
    use core\utils\Stack;

    /**
     * Class Dependencies
     * Gère deux types de dépendences JS & CSS
     * @author Arnaud NICOLAS <arno06@gmail.com>
     * @version 1.4
     */
    class Dependencies
    {
        /**
         * Chemin du fichier manifest
         */
        const MANIFEST = "includes/components/manifest.json";

        /**
         * Séparateur des librairies dans l'url
         */
        const NEED_SEPARATOR = ',';

        /**
         * Type javascript
         */
        const TYPE_JS = "javascript";

        /**
         * Type CSS
         */
        const TYPE_CSS = "css";

        /**
         * @var string
         */
        static private $current_folder;

        /**
         * @var string
         */
        private $output = "";

        /**
         * @var array
         */
        private $manifest = array();

        /**
         * @var string
         */
        private $type;

        /**
         * @var array
         */
        private $configuration = array();

        /**
         * Constructor
         * @param string $pType
         * @throws \Exception
         */
        public function __construct($pType = self::TYPE_JS)
        {
            $this->type = $pType;
            switch($this->type)
            {
                case self::TYPE_JS:
                    Header::contentType("application/javascript");
                    break;
                case self::TYPE_CSS:
                    Header::contentType("text/css");
                    break;
            }

            /**
             * Load manifest
             */
            if(!file_exists(self::MANIFEST))
                $this->output($this->log("Manifest file '".self::MANIFEST."' not found", "error"));

            $this->manifest = SimpleJSON::import(self::MANIFEST);

            $this->configuration = isset($this->manifest["config"])?$this->manifest["config"]:array();
            unset($this->manifest["config"]);
        }

        /**
         * @throws \Exception
         */
        public function retrieve()
        {
            /**
             * Check get vars
             */
            $need = Core::checkRequiredGetVars("need")?explode(self::NEED_SEPARATOR, $_GET["need"]):array();

            if(empty($need))
                $this->output($this->log("No lib to load", "warn"));

            $needs = array();

            $this->calculateNeeds($need, $needs);

            $needs = array_unique($needs);

            /**
             * Get lib contents
             */
            foreach($needs as $lib)
            {
                if(isset($this->manifest[$lib]))
                {
                    if(!isset($this->manifest[$lib][$this->type])
                        ||!is_array($this->manifest[$lib][$this->type]))
                    {
                        $this->output .= $this->log($lib." is not available", "warn");
                        continue;
                    }

                    $files = $this->manifest[$lib][$this->type];

                    for($i = 0, $max = count($files); $i<$max;$i++)
                    {
                        $absolute_link = preg_match('/^http(s*)\:\/\//', $files[$i], $matches);
                        if(!$absolute_link)
                        {
                            $files[$i] = dirname(self::MANIFEST)."/".$this->configuration["relative"].$files[$i];
                            $content = File::read($files[$i]);
                            self::$current_folder = dirname($files[$i]);
                            if($this->type == self::TYPE_CSS)
                            {
                                $content = preg_replace_callback('/(url\(\")([^\"]+)/', 'core\tools\Dependencies::correctUrls', $content);
                            }
                            $this->output .= $content."\r\n";
                        }
                        else
                            $this->output .= Request::load($files[$i]);
                    }
                }
                else
                    $this->output .= $this->log($lib." is not available", "warn");
            }   


            /**
             * Minified / Uglyflied / gzip
             */

            $accept_gzip = preg_match('/gzip/', $_SERVER['HTTP_ACCEPT_ENCODING'], $matches)&&!Core::checkRequiredGetVars("output");
            if($accept_gzip)
            {
                Header::contentEncoding("gzip");
                $this->output = gzencode($this->output);
            }

            $this->output($this->output);
        }

        /**
         * @param array $pNeeded
         * @param array $pFinalList
         */
        private function calculateNeeds($pNeeded, &$pFinalList)
        {

            foreach($pNeeded as $lib)
            {
                if(isset($this->manifest[$lib]))
                {
                    array_unshift($pFinalList, $lib);
                    if(!isset($this->manifest[$lib]["need"])
                        ||!is_array($this->manifest[$lib]["need"])
                        ||empty($this->manifest[$lib]["need"]))
                        continue;
                    $dep = array_reverse($this->manifest[$lib]["need"]);
                    $this->calculateNeeds($dep, $pFinalList);
                }
                else
                    $this->output .= $this->log($lib." is not available", "warn");
            }
        }

        /**
         * @param string $pText
         * @param string $pLevel
         * @return string
         */
        private function log($pText, $pLevel='log')
        {
            switch($this->type)
            {
                case self::TYPE_JS:
                    return "console.".$pLevel."('Dependencies : ".addslashes($pText)."');".PHP_EOL;
                    break;
                case self::TYPE_CSS:
                    return "/* Dependencies -".$pLevel."- : ".$pText." */".PHP_EOL;
                    break;
            }
            return "";
        }

        /**
         * @param string $pContent
         */
        private function output($pContent)
        {
            /**
             * Cache
             */
            $cacheDuration = Stack::get("cache.duration", $this->configuration);
            if(!empty($cacheDuration))
            {
                $eTag = md5($pContent);
                Header::handleCache($eTag, $cacheDuration);
            }

            Header::contentLength(strlen($pContent));
            echo $pContent;
            Core::endApplication();
        }

        /**
         * Méthode de correction des urls des assets utilisés dans les CSS
         * @param array $pMatches
         * @return string
         */
        static private function correctUrls($pMatches)
        {
            if(strpos($pMatches[2], 'data:image')>-1)
            {
                return $pMatches[0];
            }
            return $pMatches[1].Application::getInstance()->getPathPart().'../../'.self::$current_folder.'/'.$pMatches[2];
        }
    }
}
