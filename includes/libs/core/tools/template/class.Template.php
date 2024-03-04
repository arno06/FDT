<?php
namespace core\tools\template
{

    use core\application\Application;
    use core\system\File;

    /**
     * Class Template
     *
     * @author Arnaud NICOLAS <arno06@gmail.com>
     * @version 0.5
     * @package core\tools\template
     */
    class Template
    {
        /**
         * Chemin du dossier de cache
         * @var string
         */
        public $cacheDir;

        /**
         * Chemin du dossier de template
         * @var string
         */
        public $templateDir;

        /**
         * Nom du fichier du template
         * @var string
         */
        public $templateFile;

        /**
         * Chemin du fichier du template
         * @var string
         */
        private $templatePath;

        /**
         * Nom du fichier de cache du template
         * @var string
         */
        private $cacheFile;

        /**
         * Chemin du chemin du fichier de cache du template
         * @var string
         */
        private $cachePath;

        /**
         * Booléen définissant si le contexte d'exécution autorise l'usage des tags PHP <?php ?>
         * @var bool
         */
        public $safeMode = true;

        /**
         * Booléen définissant si on utilise le cache (en lecture et écriture)
         * @var bool
         */
        public $cacheEnabled = true;

        /**
         * Durée de la dernière exécution d'un template
         * @var float
         */
        public $duration;

        /**
         * Instance de RenderingContext
         * @var RenderingContext
         */
        private $context;

        /**
         * Variable contenant l'incrément sur les blocs en cours
         * @var number
         */
        private $step;

        /**
         * Tableau contenant l'identifiant des blocs en cours ainsi que leur état d'ouverture
         * @var array
         */
        private $opened;

        /**
         * @var array
         */
        private $available_functions = array(
            "implode"=>array("parameters"=>array("data"=>array(), "separator"=>"|"), "template"=>'implode({$separator}, {$data})'),
            "json_encode"=>array("parameters"=>array("data"=>array()), "template"=>'json_encode({$data})'),
            "nl2br"=>array("parameters"=>array("string"=>""), "template"=>'nl2br({$string})'),
            "addslashes"=>array("parameters"=>array("string"=>""), "template"=>'addslashes({$string})'),
            "trace_r"=>array("parameters"=>array("value"=>array()), "template"=>'trace_r({$value})')
        );


        /**
         * Template constructor.
         * @param null $pDefaultData
         */
        public function __construct($pDefaultData = null)
        {
            $this->context = new RenderingContext();
            if(!is_null($pDefaultData))
            {
                $this->context->setData($pDefaultData);
            }
            /** @var Application $app */
            $app = Application::getInstance();
            $this->setup($app->getTemplatesPath(), $app->getTemplatesCachePath());
        }


        /**
         * Méthode d'assignation d'une variable
         * @param string $pName     Nom de la variable
         * @param mixed $pValue     Valeur
         */
        public function assign($pName, &$pValue)
        {
            $this->context->assign($pName, $pValue);
        }

        /**
         * Méthode de réinitialisation des données accessibles au sein du context de rendu du template
         */
        public function clearData()
        {
            $this->context->setData([]);
        }


        /**
         * Méthode de définition des différents dossiers de travail
         * @param string $pTemplateDir
         * @param string $pCacheDir
         */
        public function setup($pTemplateDir, $pCacheDir)
        {
            $this->templateDir = $pTemplateDir;
            $this->cacheDir = $pCacheDir;
            $this->context->prepare($pTemplateDir, $pCacheDir);
        }


        /**
         * Méthode de rendu d'un template
         * @param string $pTemplateFile     Nom du fichier du template
         * @param bool $pDisplay            Indique sur le template doit être affiché ou renvoyé
         * @return bool|string
         */
        public function render($pTemplateFile, $pDisplay = true)
        {
            $this->templateFile = $pTemplateFile;
            $this->templatePath = $this->templateDir."/".$this->templateFile;
            $this->cacheFile = str_replace("/", "%", $this->templateFile).".php";
            $this->cachePath = $this->cacheDir."/".$this->cacheFile;

            $this->context->setFile($this->cachePath);

            if($this->pullFromCache())
            {
                return $this->execute($pDisplay);
            }

            $this->evaluate();
            return $this->execute($pDisplay);
        }


        /**
         * Méthode privée indiquant si un cache est existant pour le template en cours et si il est à jour
         * @return bool
         */
        private function pullFromCache()
        {
            if(!$this->cacheEnabled)
                return false;

            if(!file_exists($this->cachePath))
                return false;

            $cacheTime = filemtime($this->cachePath);

            /** Cache is no more valid */
            if($cacheTime < filemtime($this->templatePath))
                return false;

            return true;
        }


        /**
         * Méthode privée de stockage du résultat du template en cours dans un fichier de cache
         * @param string $pContent
         */
        private function storeInCache($pContent)
        {
            if(!$this->cacheEnabled)
                return;

            if(file_exists($this->cachePath))
            {
                unlink($this->cachePath);
            }

            file_put_contents($this->cachePath, $pContent);
        }


        /**
         * Méthode d'exécution du template sur le context en cours
         * @param bool $pDisplay    Indique sur le template doit être affiché ou renvoyé
         * @return bool|string
         */
        private function execute($pDisplay = true)
        {
            return $this->context->render($pDisplay);
        }


        /**
         * Méthode de transformation de la source du fichier de template pour l'évaluer et le transformer dans une version PHP exécutable
         */
        private function evaluate()
        {
            try
            {
                $content = File::read($this->templatePath);
            }
            catch (\Exception $e)
            {
                trigger_error("Le fichier '".$this->templateFile."' n'existe pas. ".$this->templatePath, E_USER_WARNING);
                return;
            }
            
            $startTime = microtime(true);

            $otag = TemplateDictionary::$TAGS[0];
            $etag = TemplateDictionary::$TAGS[1];

            $content = $this->escapeBlock($content, $otag."*", "*".$etag);

            if($this->safeMode)
            {
                $content = $this->escapeBlock($content, "<?php", "?>");
            }

            $to = TemplateDictionary::$TAGS[0];
            $tc = TemplateDictionary::$TAGS[1];

            $blocks = "[a-z0-9\_]+";

            $re_block = "/(\\".$to."(".$blocks.")|\\".$to."\/(".$blocks."))([^\\".$tc."]*)\\".$tc."/i";

            $re_vars = "/\\$([a-z0-9\_\.\|]+)/i";

            $content = preg_replace_callback($re_vars, function($pMatches)
            {
                $modifiers = explode('|', $pMatches[1]);
                $var = $this->extractVar(array_shift($modifiers), array_reverse($modifiers));
                return $var;
            }, $content);

            $re_vars = "/".$to."\\$([^".$tc."]+)".$tc."/i";

            $content = preg_replace_callback($re_vars, function($pMatches)
            {
                return "<?php echo \$".$pMatches[1]."; ?>";
            }, $content);

            $this->step = 0;
            $this->opened = [];
            $content = preg_replace_callback($re_block, function($pMatches){
                return $this->parseBlock($pMatches);
            }, $content);

            $endTime = microtime(true);

            $this->duration = $endTime - $startTime;

            $this->storeInCache($content);
        }

        /**
         * Méthode de parsing d'un block
         * @param array $pMatches
         * @return string
         */
        private function parseBlock(array $pMatches)
        {
            $opener = !empty(trim($pMatches[2]));
            $name = $opener?$pMatches[2]:$pMatches[3];
            $params = trim($pMatches[4]);

            switch($name)
            {
                case "if":
                    if($opener)
                    {
                        return "<?php if(".$params."): ?>";
                    }
                    else
                    {
                        return "<?php endif; ?>";
                    }
                    break;
                case "foreach":
                    if($opener)
                    {
                        $this->step++;
                        $this->opened[$this->step] = true;

                        $default = ["key"=>'key', "item"=>'value'];

                        $this->parseParameters($params, $default);

                        $array_var = 'data_'.$this->step;
                        $var = '$'.$array_var.'='.$default["from"].';';
                        $item = preg_replace("/(\"|')/", '', $default['item']);
                        $key = preg_replace("/(\"|')/", '', $default['key']);

                        return '<?php '.$var.' if($'.$array_var.'&&is_array($'.$array_var.')&&!empty($'.$array_var.')):
foreach($'.$array_var.' as $'.$key.'=>$'.$item.'): $this->assign("'.$item.'", $'.$item.'); $this->assign("'.$key.'", $'.$key.'); ?>';
                    }
                    else
                    {
                        $array_var = 'data_'.$this->step;
                        $extra = isset($this->opened[$this->step])?"endforeach; unset(\$".$array_var."); ":"";
                        unset($this->opened[$this->step--]);
                        return "<?php ".$extra."endif; ?>";
                    }
                    break;
                case "foreachelse":
                    unset($this->opened[$this->step]);
                    $array_var = 'data_'.$this->step;
                    return "<?php endforeach; unset(\$".$array_var."); else: ?>";
                    break;
                case "else":
                    return "<?php else: ?>";
                    break;
                case "include":
                    $default = array();
                    $this->parseParameters($params, $default);
                    $extra = 'array(';
                    foreach($default as $n=>$v){
                        if($n === "file"){
                            $file = $v;
                            continue;
                        }
                        if($extra !== 'array('){
                            $extra .= ',';
                        }
                        $extra .= '"'.$n.'"=>'.$v;
                    }
                    $extra .= ')';
                    if(!$file){
                        trigger_error('Paramètre "file" manquant lors de l\'appel à la fonction "include"', E_USER_WARNING);
                        return '';
                    }
                    return "<?php \$this->includeTpl(".$file.", ".$extra."); ?>";
                    break;
                default:
                    if(isset($this->available_functions[$name]) && !empty($this->available_functions[$name]))
                    {
                        $default = $this->available_functions[$name]['parameters'];
                        $this->parseParameters($params, $default);
                        $tpl = $this->available_functions[$name]['template'];
                        foreach($default as $n=>$v)
                        {
                            $tpl = str_replace('{$'.$n.'}', $v, $tpl);
                        }
                        return "<?php echo ".$tpl."; ?>";
                        break;
                    }
                    $re_object = "/\\".TemplateDictionary::$TAGS[0]."([a-z0-9\\.\\_]+)(\\-\\>[a-z\\_]+)*([^\\".TemplateDictionary::$TAGS[1]."]+)*\\".TemplateDictionary::$TAGS[1]."/i";
                    preg_match($re_object, $pMatches[0], $matches);
                    if(isset($matches)&&!empty($matches)&&!empty($matches[1])&&!empty($matches[2]))
                    {
                        $p = "";
                        if(isset($matches[3])&&!empty($matches[3]))
                        {
                            $ptr = array();
                            $this->parseParameters($matches[3], $ptr);
                            foreach($ptr as $n=>$v)
                            {
                                if(empty($n)||empty($v))
                                    continue;
                                if(!empty($p))
                                    $p .= ', ';
                                $p .= '"'.$n.'"=>'.$v;
                            }
                            $p = "array(".$p.")";
                        }
                        return "<?php ".$this->extractVar($matches[1]).$matches[2]."(".$p."); ?>";
                    }
                    else
                    {
                        //todo identifier les cas d'usage
                    }

                    return $pMatches[0];
                    break;
            }
        }



        /**
         * Méthode d'échappement de block
         * @param string $content       Chaîne de caractères contextuelle
         * @param string $pStartTag     Tag de début du block
         * @param string $pEndTag       Tag de fin du block
         * @return mixed
         */
        private function escapeBlock($content, $pStartTag, $pEndTag)
        {
            while(($s = strpos($content, $pStartTag))!==false)
            {
                $e = strpos($content, $pEndTag)+2;
                $length = $e-$s;
                $content = substr_replace($content, "", $s, $length);
            }
            return $content;
        }


        /**
         * Méthode d'identification et de parsing des paramètres envoyés à un block
         * @param string $pString       Chaîne de caractères contextuelle
         * @param array &$pParams       Tableau des valeurs par défaut
         */
        private function parseParameters($pString, &$pParams)
        {
            $re = "/(([a-z0-9A-Z\_]+)=)/";
            preg_match_all($re, $pString, $matches);
            for($i = 0, $max = count($matches[0]); $i<$max; $i++){
                $m = $matches[0][$i];
                $s = strpos($pString, $m) + strlen($m);
                $e = $i<($max-1)?strpos($pString, $matches[0][$i+1]):strlen($pString);
                $pParams[trim($matches[2][$i])] = trim(substr($pString, $s, $e-$s));
            }
        }


        /**
         * Méthode de récupération de la chaîne de caractères correspondant à une variable
         * @param string $pId           Identifiant de la variable dans le tableau de contenu contextuel
         * @param array $pModifiers     Tableau des méthodes de modification à appliquer au résultat de la valeur
         * @return string
         */
        private function extractVar($pId, $pModifiers = array())
        {
            $modifiers = "[]";
            if(!empty($pModifiers))
                $modifiers = "['".implode("','", $pModifiers)."']";
            return '$this->get("'.$pId.'",'.$modifiers.')';
        }
    }

    /**
     * Class TemplateDictionary
     *
     * @author Arnaud NICOLAS <arno06@gmail.com>
     * @package core\tools\template
     */
    class TemplateDictionary
    {
        /**
         * @var array
         */
        static public $TAGS = ["{", "}"];

        /**
         * @var array
         */
        static public $BLOCKS = [
            "foreach",
            "if"
        ];

        /**
         * @var array
         */
        static public $NEUTRALS = [
            "foreachelse",
            "else"
        ];
    }
}
