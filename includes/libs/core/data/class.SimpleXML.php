<?php
namespace core\data
{
	use core\application\Configuration;
	use core\system\File;
	use \Exception;

	/**
	 * Class SimpleXML
	 * Permet de manipuler des données au format XML
	 *
	 * @author Arnaud NICOLAS <arno06@gmail.com>
	 * @version .3
	 * @package data
	 */
	abstract class SimpleXML implements InterfaceData
	{

		/**
		 * Méthode de chargement de décodage d'un fichier XML
		 * @param String $pFile
		 * @return array
		 * @throws Exception
		 */
		static public function import ($pFile)
		{
			try
			{
				$content = 	File::read($pFile);
			}
			catch (Exception $e)
			{
				throw new Exception("Impossible de lire le fichier source <b>".$pFile."</b>");
			}
			return self::decode($content);
		}

		/**
		 * Méthode de récupération d'un tableau associatif multidimensionnel à partir d'un contenu écrit au format XML
		 * @param String $pString		Contenu XML
		 * @return array
		 */
		static public function decode ($pString)
		{
			$pString = preg_replace("/[\r\n]+/","",$pString);
			$pString = preg_replace('/\>[\t\s]+\</',"><", $pString);
			$pString = preg_replace('/^\<\?xml\s*([a-z]+\=\"[a-z0-9\.\_\-]*\"\s*)*\?\>/i', "", $pString);
			return self::getArrayFromNode($pString);
		}


		/**
		 * Méthode d'encodage d'un tableau en données formatées en XML
		 * @param array $pTableau		Tableau des données
		 * @param bool  $pEncoding
		 * @return String
		 */
		static public function encode(array $pTableau, $pEncoding = true)
		{
			$return = "";
			if($pEncoding)
				$return = "<?xml version=\"1.0\" encoding=\"".Configuration::$global_encoding."\"?>";
			$return .= self::getRecursiveNodes($pTableau);
			return $return;
		}

		/**
		 * Méthode permettant de décoder de facon récursive un noeud XML en tableau
		 * @param String $pString		Noeux XML
		 * @return array
		 */
		static private function getArrayFromNode($pString)
		{
			$return = array();
			while(preg_match("/^\<([a-z0-9:_]{1,})([^>]*)?[\s]*\/\>(.*)/i", $pString, $m))
				$pString = preg_replace("/".addcslashes($m[0], "<>\\/")."/", "<".$m[1].$m[2]."></".$m[1].">".$m[3], $pString);
			while(preg_match("/^\<([a-z0-9:_]{1,})([^>]*)?[\s]*\>(.*)/i", $pString, $m))
			{
				if(preg_match("/\<\/".$m[1]."\>/", $m[3], $extract))
				{
					$pString = strstr($m[3], "</".$m[1].">");
					$pString = substr_replace($pString,'', 0, (strlen ($m[1])+3));
					$tableau = explode("</".$m[1].">",$m[3],2);
					$childs = array_shift($tableau);
				}
				else
				{
					$pString = $m[3];
					$childs = "";
				}
				$node = array();
				preg_match_all('/(([a-z0-9\_\-\:]*)\=\"([^"]*)?\"){1}/i', $m[2], $p);
				for($i = 0, $max = count($p[0]); $i<$max; ++$i)
				{
					if(is_string($p[2][$i])&&!empty($p[2][$i]))
						$node[$p[2][$i]] = $p[3][$i];
				}
				if(!empty($childs)&&(preg_match('/^\<\!\[CDATA\[(.*)?\]\]\>$/',$childs,$c)||(preg_match('/^([^<>]+)$/',$childs,$c))))
					$node["nodeValue"] = $c[1];
				$node = array_merge($node,self::getArrayFromNode($childs));
				if(!isset($return[$m[1]]))
					$return[$m[1]] = $node;
				elseif(is_numeric(key($return[$m[1]])))
					$return[$m[1]][] = $node;
				elseif(is_string(key($return[$m[1]])))
				{
					$f = $return[$m[1]];
					$return[$m[1]] = array($f,$node);
				}
			}
			return $return;
		}


		/**
		 * Méthode de parsage récursif d'un tableau en noeuds XML
		 * @param array $pTableau		Tableau des données
		 * @return String
		 */
		static private function getRecursiveNodes($pTableau)
		{
			$nodes = "";
			foreach($pTableau as $nodeName=>$node)
			{
				if(is_string($node))
				{
					$nodes .= "<".$nodeName.">";
					$nodes .= self::getEscapedString($node);
					$nodes .= "</".$nodeName.">";
					continue;
				}
				if(!is_array($node))
					continue;
				if(is_numeric(key($node)))
				{
					for($i = 0, $max = count($node); $i<$max; ++$i)
						$nodes .= self::getNode($nodeName, $node[$i]);
				}elseif(is_string(key($node)))
					$nodes .= self::getNode($nodeName, $node);
			}
			return $nodes;
		}

		/**
		 * Méthode de récupération d'un noeud XML et de ses enfants
		 * @param String $pNodeName		Nom du noeud à récupérer
		 * @param array $pTableau		Tableau des attributs et enfants du noeud
		 * @return String
		 */
		static public function getNode($pNodeName, $pTableau)
		{
			$info = "<".$pNodeName;
			$children = array();
			foreach($pTableau as $name=>$value)
			{
				if($name=="nodeValue")
				{
					$nodeValue = $value;
					continue;
				}
				if(!is_array($value))
					$info .= " ".$name."=\"".$value."\"";
				else
					$children[$name] = $value;
			}
			$info .= ">";
			if(isset($nodeValue))
				$info .= self::getEscapedString($nodeValue);
			$info .= self::getRecursiveNodes($children);
			$info .= "</".$pNodeName.">";
			return $info;
		}

		static public function xPath($pXPath, $pData)
		{
			$pXPath = preg_replace('/^\//',"", $pXPath);
			$pXPath = preg_replace('/\/$/',"", $pXPath);
			$nodes = explode("/",$pXPath);
			for($i=0, $max=count($nodes); $i<$max;$i++)
			{
				$node = array();
				preg_match('/^([a-z0-9:]+)(\[([a-z0-9\:\@\=\.\;\,\(\)]+)\])*$/i', $nodes[$i], $extract);
				$node["node"] = $extract[1];
				if(!empty($extract[3]))
				{
					if(preg_match('/^([0-9]+|(last\(\)))$/', $extract[3], $useless))
						$node["index"] = $extract[3];
					else
					{
						$extract[3] = explode("@", $extract[3]);
						$extract[3] = explode("=", $extract[3][1]);
						$node["property"] = $extract[3][0];
						$node["value"] = $extract[3][1];
					}
				}
				$nodes[$i] = $node;
			}
			for($i=0;$i<$max;$i++)
			{
				$node = $nodes[$i];
				$pData = $pData[$node["node"]];
				if(isset($node["index"])&&(isset($pData[0])||$node["index"]>1))
					$pData = $pData[$node["index"]-1];
				if(isset($node["property"]))
				{
					if(!isset($pData[0]))
					{
						if(!isset($pData[$node["property"]]))
							return false;
						if(isset($node["value"])&&$pData[$node["property"]]!=$node["value"])
							return false;
					}
					else
					{
						$d = array();
						for($j = 0, $maxj = count($pData); $j<$maxj;$j++)
						{
							if(!isset($pData[$j][$node["property"]]))
								continue;
							if(isset($node["value"])&&$pData[$j][$node["property"]]!=$node["value"])
								continue;
							array_push($d, $pData[$i]);
						}
					}
				}
			}
			return $pData;
		}

		/**
		 * Méthode de récupération d'une chaine de caract&egrave;res dont on souhaite protéger des caract&egrave;res d'échappements (balises...)
		 * @param String $pString		Chaine à échappée
		 * @return String
		 */
		static private function getEscapedString($pString)
		{
			if(preg_match("/(<|>)/",$pString, $m))
				return "<![CDATA[".$pString."]]>";
			else
				return $pString;
		}

        /**
         * @param \SimpleXMLElement $pXml
         */
        static public function registerNameSpaces(\SimpleXMLElement $pXml){
            $ns = $pXml->getNamespaces(true);
            foreach($ns as $a=>$n){
                if(empty($a)){
                    $a = "default";
                }
                $pXml->registerXPathNamespace($a, $n);
            }
        }
	}
}
