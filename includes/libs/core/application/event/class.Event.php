<?php

namespace core\application\event
{
    /**
    * Class Event
    *
    * @author Arnaud NICOLAS <arno06@gmail.com>
    * @version .1
    * @package core\application
    * @subpackage event
    */
    class Event
    {
        /**
         * @var string
         */
        public $type;

        /**
         * @var array
         */
        public $args;

        /**
         * @var EventDispatcher
         */
        public $target;

        /**
         * @param $pType
         */
        public function __construct($pType)
        {
            $this->type = $pType;
            $this->args = array();
            $count = func_num_args();
            if ($count > 1)
            {
                $arguments = func_get_args();
                for($i = 1 ; $i < $count ; $i++)
                {
                    $this->args[] = $arguments[$i];
                }
            }
        }

        /**
         * @return Event
         */
        public function __clone()
        {
            return new Event($this->type);
        }

        /**
         * @return string
         */
        public function __toString()
        {
            return "[Event type='".$this->type."']";
        }
    }
}
