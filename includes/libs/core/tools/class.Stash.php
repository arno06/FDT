<?php
namespace core\tools
{
    use core\application\Application;
    use core\data\Encoding;
    use core\data\SimpleJSON;
    use core\system\File;
    use core\system\Folder;

    /**
     * Class Stash
     * @package core\tools
     */
    class Stash
    {
        /**
         * @var string
         */
        private $cache_folder;

        /**
         * @var bool
         */
        private $cache_enabled = true;

        /**
         * @var int
         */
        protected $cache_duration = 60;//minutes

        /**
         * Stash constructor.
         * @param string $pCacheFolder
         */
        public function __construct($pCacheFolder)
        {
            $this->cache_folder = Application::getInstance()->getFilesPath().'/_cache/'.Application::getInstance()->getModule()->name.'/'.$pCacheFolder.'/';
        }

        /**
         * @param mixed $pData
         * @param string $pFileName
         * @return bool
         */
        protected function storeInCache($pData, $pFileName)
        {
            if(!$this->cache_enabled||!$pData)
                return false;
            $pFileName = $this->cache_folder.$pFileName.".json";
            Folder::create($this->cache_folder);
            File::delete($pFileName);
            $pData = SimpleJSON::encode($pData);
            File::create($pFileName);
            File::append($pFileName, $pData);
            return true;
        }

        /**
         * @param string $pFileName
         * @return array|bool|mixed|string
         */
        protected function pullFromCache($pFileName)
        {
            if(!$this->cache_enabled)
                return false;

            $pFileName = $this->cache_folder.$pFileName.".json";

            if(!file_exists($pFileName))
                return false;

            try
            {
                $fmtime = filemtime($pFileName);
                $current = time();
                $diff = ($current - $fmtime) / (60);
                if($diff>$this->cache_duration)
                    return false;
                $jsonParsed = SimpleJSON::import($pFileName);
            }
            catch(\Exception $e)
            {
                return false;
            }
            $jsonParsed = Encoding::fromNumericEntities($jsonParsed);
            return $jsonParsed;
        }

        /**
         * Méthode d'activation du cache
         */
        public function activateCache()
        {
            $this->cache_enabled = true;
        }

        /**
         * Méthode de désactivation du cache
         */
        public function deactivateCache()
        {
            $this->cache_enabled = false;
        }
    }
}