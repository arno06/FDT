<?php

namespace core\utils {

    use core\data\SimpleJSON;
    use core\data\SimpleXML;
    use core\tools\debugger\Debugger;
    use core\tools\Request;

    class RestHelper
    {
        const HTTP_GET = "GET";
        const HTTP_POST = "POST";
        const HTTP_DELETE = "DELETE";
        const HTTP_PATCH = "PATCH";
        const HTTP_PUT = "PUT";

        const FORMAT_XML = "xml";
        const FORMAT_JSON = "json";
        const FORMAT_RAW = "raw";

        static private $runtime_cache = array();

        static public $use_cache = true;

        static public $debug_track = true;

        /**
         * @param string $pUrl
         * @param string $pMethod
         * @param array $pParams
         * @param string $pFormat
         * @param array $pHeaders
         * @return array|bool|\SimpleXMLElement|string
         */
        static public function request($pUrl, $pMethod = self::HTTP_GET, array $pParams = array(), $pFormat = self::FORMAT_XML, array $pHeaders = array())
        {

            if($pMethod == self::HTTP_GET&&!empty($pParams)){
                $pUrl .= '?'.http_build_query($pParams);
            }

            if(self::$use_cache && (isset(self::$runtime_cache[md5($pUrl)]) && !empty(self::$runtime_cache[md5($pUrl)]) && $pMethod == self::HTTP_GET))
                return self::$runtime_cache[md5($pUrl)];

            $id = "RestHelper::request : <a target='_blank' href='".$pUrl."'>".$pUrl."</a>";
            if(self::$debug_track){
                Debugger::track($id);
            }
            $r = new Request($pUrl);
            $r->setOption(CURLOPT_ENCODING, 'gzip');
            $r->setOption(CURLOPT_TIMEOUT, 10);
            $r->setOption(CURLOPT_CONNECTTIMEOUT, 5);
            $r->setMethod($pMethod);
            $r->setOption(CURLOPT_SSL_VERIFYPEER, false);
            $r->setOption(CURLOPT_HTTPHEADER, $pHeaders);

            if (!empty($pParams)) {
                switch($pMethod) {
                    case self::HTTP_POST:
                    case self::HTTP_PATCH:
                    case self::HTTP_PUT:
                    case self::HTTP_DELETE:
                        $r->setDataPost(http_build_query($pParams));
                        break;
                }
            }

            try
            {
                $d = $r->execute();
            }
            catch(\Exception $e)
            {
                trace_r($e->getMessage());
                $d = false;
            }

            if($r->getResponseHTTPCode()>=400){
                $d = false;
            }

            if($d===false)
            {
                $error = "RestHelper::request failed : ".$pUrl;
                trigger_error($error, E_USER_WARNING);
                return false;
            }

            switch(strtolower($pFormat))
            {
                case self::FORMAT_JSON:
                    $result = SimpleJSON::decode($d);
                    break;
                case self::FORMAT_XML:
                    $result = simplexml_load_string($d);
                    SimpleXML::registerNameSpaces($result);
                    break;
                default:
                case self::FORMAT_RAW:
                    $result = $d;
                    break;
            }
            if(self::$debug_track){
                Debugger::track($id);
            }
            if(self::$use_cache){
                self::$runtime_cache[md5($pUrl)] = $result;
            }
            return $result;
        }
    }
}