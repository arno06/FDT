<?php
namespace core\utils
{
	/**
	 * Class SimpleRandom
	 *
	 * @author Arnaud NICOLAS <arno06@gmail.com>
	 * @version .1
	 * @package core\utils
	 */
	abstract class SimpleRandom
	{
		const ALPHA_LOW = 1;
		const ALPHA_UP = 2;
		const NUMERIC = 4;

		/**
		 * Méthode static de génération d'une chaine de caract&egrave;res aléatoires (majuscule, minuscule, chiffre)
		 * @param Number $pLength		Longueur souhaitée de la chaine
		 * @param int $pType
		 * @return String
		 */
		static public function string($pLength, $pType = 7)
		{
			if(!is_numeric($pLength))
				return false;
			if(!$pType)
				$pType = 1;
			$chars = array();
			if($pType&self::ALPHA_LOW)
				$chars = array_merge($chars, range("a", "z"));
			if($pType&self::ALPHA_UP)
				$chars = array_merge($chars, range("A", "Z"));
			if($pType&self::NUMERIC)
				$chars = array_merge($chars, range(0, 9));
			$maxChars = count($chars);
			$string = "";
			$i = 0;
			for(;$i<$pLength;++$i)
				$string .= $chars[rand(0, $maxChars-1)];
			return $string;
		}
	}
}
