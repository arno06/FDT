<?php
namespace core\utils
{
	/**
	 * @author NICOLAS Arnaud <arno06@gmail.com>
	 * @version 2.0
	 * @package core\utils
	 */
	class Stack
	{
		/**
		 * Permet d'accéder rapidement &agrave; une valeur présente dans un tableau par rapport &agrave; ses clés :
		 *
		 * $ar = array("key"=>array("key2"=>array("key3"=>"une valeur)));
		 *
		 * $value = Stack::get("key.key2.key3", $ar); //une valeur
		 *
		 * @static
		 * @param string $pId
		 * @param array $pStack
		 * @return mixed
		 */
		static public function get($pId, &$pStack)
		{
			$value = $pStack;
			$keys = explode(".", $pId);
			foreach($keys as &$k)
			{
				if(!isset($value[$k]))
					return null;
				$value = $value[$k];
			}
			return $value;
		}
	}
}
