<?php
namespace core\utils
{
    use core\data\SimpleXML;
    use \ReflectionClass;

    /**
     * Class SoapHelper
     *
     * @author Arnaud NICOLAS <arno06@gmail.com>
     * @version 0.1
     * @package core\utils
     */
    class SoapHelper
    {
        /**
         * Méthode de génération d'un fichier WSDL à partir d'une classe de définition
         * Les méthodes publics de la classe doivent être commentées par le biais de PHPDocComment décrivant les paramètres (nom & type) ainsi que le format de retour
         *
         * @param string $pServiceName
         * @param string $pNamespace
         * @param string $pSoapLocation
         * @param string $pClassName
         * @return String   XML préformaté par le biais de la classe SimpleXML
         */
        static public function generateWSDLFromClass($pServiceName, $pNamespace, $pSoapLocation, $pClassName)
        {
            $ref = new ReflectionClass($pClassName);

            $methods = $ref->getMethods();

            $callable_methods = array();
            foreach($methods as $method)
            {
                $comments = $method->getDocComment();
                if($method->isPrivate()
                    ||$method->isProtected()
                    ||$method->isConstructor()
                    ||$method->isDestructor()
                    ||empty($comments))
                    continue;
                $callable_methods[$method->name] = self::parseDocComment($comments);
            }

            $operations = array();
            $bindings = array();
            $messages = array();
            foreach($callable_methods as $name=>$context)
            {
                $op = array(
                    'name'=>$name,
                    'input'=>array(
                        'message'=>'tns:'.$name.'Request'
                    ),
                    'output'=>array(
                        'message'=>'tns:'.$name.'Response'
                    )
                );
                $binding = array(
                    'name'=>$name,
                    'soap:operation'=>array(
                        'soapAction'=>"urn:".$pServiceName."#".$name
                    ),
                    'input'=>array(
                        'soap:body'=>array(
                            'use'=>'encoded',
                            'namespace'=>$pNamespace,
                            'encodingStyle'=>'http://schemas.xmlsoap.org/soap/encoding/'
                        )
                    ),
                    'output'=>array(
                        'soap:body'=>array(
                            'parts'=>'return',
                            'use'=>'encoded',
                            'namespace'=>$pNamespace,
                            'encodingStyle'=>'http://schemas.xmlsoap.org/soap/encoding/'
                        )
                    )
                );
                $message = array(
                    array(
                        'name'=>$name.'Request'
                    ),
                    array(
                        'name'=>$name.'Response'
                    )
                );
                if(!empty($context['parametersOrder']))

                {
                    $op['parameterOrder'] = $context['parametersOrder'];
                    $binding['input']['soap:body']['parts'] = $context['parametersOrder'];
                }

                foreach($context["parameters"] as $p)
                {
                    if(!isset($message[0]['part']))
                        $message[0]['part'] = array();
                    $message[0]['part'][] = array(
                        'name'=>$p['name'],
                        'type'=>'xsd:'.$p['type']
                    );
                }

                if(is_array($context['return']) && !empty($context['return']))
                {
                    $message[1]['part'] = array(
                        'name'=>'return',
                        'type'=>'xsd:'.$context['return']['type']
                    );
                }

                $operations[] = $op;
                $bindings[] = $binding;
                $messages = array_merge($message, $messages);

            }

            $wsdl = array(
                "definitions"=>array(
                    'xmlns'=>'http://schemas.xmlsoap.org/wsdl/',
                    'name'=>$pServiceName,
                    'targetNamespace'=>$pNamespace,
                    'xmlns:tns'=>$pNamespace,
                    'xmlns:xsd'=>'http://www.w3.org/2001/XMLSchema',
                    'xmlns:soap'=>'http://schemas.xmlsoap.org/wsdl/soap/',
                    'xmlns:soapenc'=>'http://schemas.xmlsoap.org/soap/encoding/',
                    'xmlns:wsdl'=>'http://schemas.xmlsoap.org/wsdl/',
                    'portType'=>array(
                        'name'=>$pServiceName.'Type',
                        'operation'=>$operations
                    ),
                    'binding'=>array(
                        'name'=>$pServiceName.'Binding',
                        'type'=>'tns:'.$pServiceName.'Type',
                        'soap:binding'=>array(
                            'style'=>'rpc',
                            'transport'=>'http://schemas.xmlsoap.org/soap/http'
                        ),
                        'operation'=>$bindings
                    ),
                    'message'=>$messages,
                    'service'=>array(
                        'name'=>$pServiceName.'Service',
                        'port'=>array(
                            'name'=>$pServiceName.'Port',
                            'binding'=>'tns:'.$pServiceName.'Binding',
                            'soap:address'=>array(
                                'location'=>$pSoapLocation
                            )
                        )
                    )
                )
            );

            return SimpleXML::encode($wsdl);
        }


        /**
         * @param string $pComments
         * @return array
         */
        static private function parseDocComment($pComments)
        {
            $parameters = array();
            $parametersOrder = array();
            if(preg_match_all('/@param\s*([a-z]+)\s*\$([a-z\_]+)/i', $pComments, $matches))
            {
                foreach($matches[0] as $i=>$m)
                {
                    $parametersOrder[] = $matches[2][$i];
                    $parameters[] = array(
                        "type"=>$matches[1][$i],
                        "name"=>$matches[2][$i]
                    );
                }
            }

            $return = false;
            if(preg_match('/@return\s*([a-z]+)\s*/i', $pComments, $matches))
            {
                $return = array(
                    "type"=>$matches[1]
                );
            }

            return array(
                "return"=>$return,
                "parameters"=>$parameters,
                "parametersOrder"=>implode(" ", $parametersOrder)
            );
        }
    }
}
