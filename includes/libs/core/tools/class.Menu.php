<?php
namespace core\tools
{

    use core\application\Core;
    use core\data\SimpleJSON;
    use core\application\Go;

    /**
     * Class Menu
     * @author Arnaud NICOLAS <arno06@gmail.com>
     * @version 1.0
     * @package core\tools
     */
    class Menu
    {
        private $items;

        public function __construct($pFile)
        {
            if(!file_exists($pFile))
                return;
            $this->items = SimpleJSON::import($pFile);
            if(!$this->items)
            {
                trigger_error('[Object Menu] No Items loaded', E_USER_NOTICE);
            }
            foreach($this->items as &$item)
            {
                if(!isset($item['parameters']))
                    $item['parameters'] = array();
                if(!isset($item['controller']))
                    $item['controller'] = '';
                if(!isset($item['action']))
                    $item['action'] = '';
                $item['current'] = (Core::$controller == $item['controller']
                    && Core::$action == $item['action']);
                $item['url'] = Core::rewriteURL($item['controller'], $item['action'], $item['parameters']);
            }
        }

        public function redirectToDefaultItem()
        {
            $item = null;
            if(is_array($this->items) && !empty($this->items))
                $item = $this->items[0];
            foreach($this->items as $i)
            {
                if(isset($i['default']) && $i['default'] === true)
                    $item = $i;
            }
            if(is_null($item))
            {
                trigger_error("[Object Menu] No default item found", E_USER_WARNING);
                return;
            }

            Go::to($item['controller'], $item['action'], $item['parameters']);
        }

        public function retrieveItems()
        {
            return $this->items;
        }
    }
}
