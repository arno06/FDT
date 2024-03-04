<?php
namespace core\tools\template
{
    /**
     * Class TemplateModifiers
     *
     * @author Arnaud NICOLAS <arno06@gmail.com>
     * @version .1
     * @package core\tools\template
     */
    class TemplateModifiers
    {
        /**
         * @var array
         */
        static private $list = [];


        /**
         * @param $pName
         * @return null|string
         */
        static public function get($pName)
        {
            if(isset(self::$list[$pName])&&is_callable(self::$list[$pName]))
                return self::$list[$pName];
            return null;
        }


        /**
         * @param string $pName
         * @param string $pMethod
         */
        static public function set($pName, $pMethod)
        {
            self::$list[$pName] = $pMethod;
        }
    }
}
