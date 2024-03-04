<?php

namespace core\utils
{

    use core\application\Configuration;
    use core\data\SimpleXML;
    use core\tools\docs\PHPDocHelpers;
    use ReflectionClass;
    use ReflectionProperty;
    use ReflectionException;
    use Exception;
    use DateTime;
    use SimpleXMLElement;

    class InteroperableObject
    {
        public function parse($pRaw = null, $pParams = null, $pQueryString = null)
        {
            if(is_null($pRaw)&&is_null($pParams)&&is_null($pQueryString)){
                return null;
            }

            $definition = IObjectDictionary::getDefinition(get_called_class());

            $allProps = $definition["props"];
            $format = $definition["format"];

            $pathThis = false;

            if(is_null($pRaw)){

                $api = $definition["api"];
                if(!$api){
                    return null;
                }

                if(is_array($pParams)){
                    foreach($pParams as $name=>$val){
                        $api = str_replace('{'.$name.'}', $val, $api);
                    }
                }

                preg_match_all('/{([a-z.0-9]+)}/', $api, $matches);

                foreach($matches[1] as $match){
                    $api = str_replace("{".$match."}", Configuration::extra($match), $api);
                }

                if(!is_null($pQueryString)){
                    if(strpos($api, "?")===false){
                        $api .= "?";
                    }else{
                        $api .= "&";
                    }
                    $api .= $pQueryString;
                }
                $pathThis = $definition["this"];

                $pRaw = RestHelper::request($api, RestHelper::HTTP_GET, array(), $format);
            }

            $raw = $pRaw;

            switch($format){
                case "xml":
                    if($pathThis !== false){
                        $pRaw = $pRaw->xpath($pathThis)[0];
                    }
                    break;
                case "json":
                    if($pathThis !== false){
                        $pRaw = Stack::get($pathThis, $pRaw);
                    }
                    break;
                default:
                    trigger_error('Unknown source type for parsing', E_USER_WARNING);
                    return null;
                    break;
            }

            for($i = 0, $max = count($allProps); $i<$max;$i++) {
                $prop = $allProps[$i];
                $name = $prop["name"];
                $type = $prop["type"];
                $path = $prop["path"];
                $callback = $prop["callback"];
                if(!is_null($callback)){
                    if($prop["setProp"]){
                        $this->setPropFromData($name, $type, $callback($path, $pRaw), $format);
                    }else{
                        $this->{$name} = self::extractValue($type, $callback($path, $pRaw));
                    }
                }else{
                    if(!$path){
                        continue;
                    }
                    $this->setPropFromData($name, $type, $definition['defaultExtract']($path, $raw));
                }
            }

            return $pRaw;
        }

        /**
         * @param string $pName
         * @param string $pType
         * @param mixed $pData
         * @param string $pFormat
         */
        protected function setPropFromData($pName, $pType, $pData, $pFormat = "json"){
            if(!$pData){
                return;
            }

            $isArray = strpos($pType, '[]')!==false;

            if($isArray){
                $pType = str_replace('[]', '', $pType);
                $values = array();
                foreach($pData as $re){
                    $values[] = self::extractValue($pType, $re);
                }
            }else{
                if($pFormat === "xml"){
                    $pData = $pData[0];
                }
                $values = self::extractValue($pType, $pData);
            }
            $this->{$pName} = $values;
        }

        /**
         * @param string $pType
         * @param mixed $pSource
         * @return mixed
         */
        static public function extractValue($pType, $pSource){
            switch($pType){
                case "string":
                    $val = strval($pSource);
                    break;
                case '\DateTime':
                    try{
                        $val = new DateTime(strval($pSource));
                    }
                    catch(Exception $e){
                        trigger_error("An error occurred while initializing DateTime instance ".$e->getMessage(), E_USER_WARNING);
                        return null;
                    }
                    break;
                case 'bool':
                    $val = strval($pSource) === 'true';
                    break;
                case 'int':
                    $val = intval($pSource);
                    break;
                default:
                    $val = null;
                    if(class_exists($pType)){
                        /** @var InteroperableObject $val */
                        $val = new $pType();
                        $val->parse($pSource);
                    }
                    break;
            }
            return $val;
        }
    }

    class IObjectDictionary
    {
        static private $classes = [];

        static public function getDefinition($pClassName){
            if(isset(self::$classes[$pClassName])){
                return self::$classes[$pClassName];
            }

            try{
                $reflec = new ReflectionClass($pClassName);
            }
            catch(ReflectionException $e){
                trigger_error("An error occurred while initializing RelfectionClass ".$e->getMessage(), E_USER_WARNING);
                return null;
            }
            $classComments = $reflec->getDocComment();
            $format = PHPDocHelpers::extractDocVar("format", $classComments);
            if(!$format){
                $format = "json";
            }

            $props = [];

            $p = $reflec->getProperties(ReflectionProperty::IS_PUBLIC|ReflectionProperty::IS_PROTECTED|ReflectionProperty::IS_PRIVATE);

            switch($format){
                case "xml":
                    $extracts = array(
                        "attribute"=>function($pVal, SimpleXMLElement $pXml){
                            $attr = $pXml->attributes();
                            return strval($attr[$pVal]);
                        },
                        "nodeValue"=>function($pVal, $pXml){
                            if($pVal !== "1"){
                                return null;
                            }
                            return strval($pXml);
                        },
                        "path"=>function($pVal, SimpleXMLElement $pXml){
                            SimpleXML::registerNameSpaces($pXml);
                            return $pXml->xpath($pVal);
                        }
                    );
                    break;
                default:
                case "json":
                    $extracts = array(
                        "path"=>function($pVal, $pRaw){
                            return Stack::get($pVal, $pRaw);
                        }
                    );
                    break;
            }

            foreach($p as &$property){
                $comment = $property->getDocComment();
                $prop = array(
                    "name"=>$property->getName(),
                    "type"=>PHPDocHelpers::extractDocVar('var', $comment),
                    "path"=>PHPDocHelpers::extractDocVar('path', $comment),
                    "callback"=>null,
                    "setProp"=>false
                );

                foreach($extracts as $n=>$pCallback){
                    if($n === "path"){
                        if($prop["path"] === false){
                            break;
                        }
                        $prop["setProp"] = true;
                        $prop["callback"] = $pCallback;
                        break;
                    }
                    $val = PHPDocHelpers::extractDocVar($n, $comment);
                    if($val !== false){
                        $prop["callback"] = $pCallback;
                        $prop["path"] = $val;
                        break;
                    }
                }

                if(is_null($prop["callback"])){
                    $prop["path"] = PHPDocHelpers::extractDocVar($prop["name"], $classComments);
                }

                $props[] = $prop;
            }

            self::$classes[$pClassName] = array(
                "api"=>PHPDocHelpers::extractDocVar('api', $classComments),
                "this"=>PHPDocHelpers::extractDocVar('this', $classComments),
                "format"=>$format,
                "props"=>$props,
                "defaultExtract"=>$extracts["path"]
            );
            return self::$classes[$pClassName];
        }
    }
}