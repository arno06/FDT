<?php
namespace core\tools\template
{
    use core\utils\Stack;

    /**
     * Class RenderingContext
     *
     * @author Arnaud NICOLAS <arno06@gmail.com>
     * @package core\tools\template
     * @version 1.0
     */
    class RenderingContext
    {
        /**
         * @var string
         */
        private $file;

        /**
         * @var array
         */
        private $data;

        /**
         * @var string
         */
        private $templateDir;

        /**
         * @var string
         */
        private $cacheDir;

        /**
         * RenderingContext constructor.
         * @param null $pFile
         */
        public function __construct($pFile = null)
        {
            $this->file = $pFile;
            $this->data = array();
        }

        /**
         * @param $pTemplateDir
         * @param $pCacheDir
         */
        public function prepare($pTemplateDir, $pCacheDir)
        {
            $this->templateDir = $pTemplateDir;
            $this->cacheDir = $pCacheDir;
        }

        /**
         * @param string $pFile
         */
        public function setFile($pFile)
        {
            $this->file = $pFile;
        }


        /**
         * Méthode d'ajout de l'assignation d'une variable
         * @param string $pName
         * @param mixed $pValue
         */
        public function assign($pName, &$pValue)
        {
            $this->data[$pName] = $pValue;
        }


        /**
         * @param string $pName
         * @param array $pExtra
         */
        public function includeTpl($pName, $pExtra = array())
        {
            $tpl = new Template(array_merge($this->data, $pExtra));
            $tpl->setup($this->templateDir, $this->cacheDir);
            $tpl->render($pName, true);
        }

        /**
         * @param string $pSeparator    Chaine de caractère servant de glue
         * @param array $pData          Tableau à parcourir
         * @param bool $pEcho           Default "true", définit si on affiche le résultat
         * @return null|string
         */
        public function implode($pSeparator, $pData, $pEcho = true)
        {
            $res = implode($pSeparator, $pData);
            if($pEcho)
            {
                echo $res;
                return null;
            }
            return $res;
        }


        /**
         * Méthode de définition du tableau de données
         * @param array $pData
         */
        public function setData(array $pData)
        {
            $this->data = $pData;
        }

        /**
         * @param string $pName
         * @param array $pModifiers
         * @return mixed
         */
        public function get($pName, $pModifiers = array())
        {
            $value = Stack::get($pName, $this->data);
            if($value&&!empty($pModifiers))
            {
                foreach($pModifiers as $m)
                {
                    if(is_callable($m)||($m = TemplateModifiers::get($m)))
                        $value = call_user_func($m, $value);
                }
            }
            return $value;
        }


        /**
         * @param bool $pDisplay
         * @return bool|string
         */
        public function render($pDisplay)
        {
            if(!file_exists($this->file)){
                trigger_error("Template file '".$this->file."' not found", E_USER_ERROR);
                return;
            }
            ob_start();
            include($this->file);
            $rendering = ob_get_contents();
            ob_end_clean();
            if($pDisplay)
            {
                echo $rendering;
                return true;
            }
            return $rendering;
        }
    }
}
