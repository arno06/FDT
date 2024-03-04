<?php

namespace core\tools
{

    /**
     * Class AsyncRequest - Requête http "asynchrone"
     * Délenche une requête http sans attendre de réponse
     *
     * @author Arnaud NICOLAS <arno06@gmail.com>
     * @version 0.1
     * @package core\tools
     */
    class AsyncRequest
    {
        /**
         * @var String
         */
        private $url;

        /**
         * @var String
         */
        private $host;

        /**
         * @var int
         */
        private $port;

        private $scheme;

        private $path;

        private $httpMethod = "GET";

        private $headers = [];

        private $body = "";

        private $queryParameters = [];

        public function __construct($pUrl)
        {
            $this->setUrl($pUrl);
        }

        public function setMethod($pMethod)
        {
            $this->httpMethod = $pMethod;
        }

        public function setHeaders(array $pHeaders)
        {
            $this->headers = $pHeaders;
        }

        public function addHeader($pHeader)
        {
            $this->headers[] = $pHeader;
        }

        public function addQueryParam($pName, $pValue)
        {
            $this->queryParameters[$pName] = $pValue;
        }

        public function setBody($pBody)
        {
            $this->body = $pBody;
        }

        public function execute()
        {
            $hostname = $this->host;
            if($this->scheme==='https'){
                $hostname = 'ssl://'.$this->host;
                $this->port = 443;
            }

            $fp = fsockopen($hostname, $this->port, $errNo, $errStr, 30);


            if($fp==false){
                trigger_error('AsyncRequest : Unable to initialize socket connection ('.$errNo.' - '.$errStr.')', E_USER_WARNING);
                return;
            }

            $path = $this->path;
            if(!empty($this->queryParameters)){
                $path .= '?'.http_build_query($this->queryParameters);
            }

            $host = $this->host;
            if($this->port != 80){
                $host .= ':'.$this->port;
            }

            $call = $this->httpMethod.' '.$path." HTTP/1.1\r\n";
            $call .= 'Host: '.$host."\r\n";
            foreach($this->headers as $header){
                $call .= $header."\r\n";
            }
            $call .= "Connection: Close\r\n";
            $call .= "\r\n".$this->body;
            fwrite($fp, $call);
            fclose($fp);
        }

        public function setUrl($pUrl)
        {
            $this->url = $pUrl;
            $parts = parse_url($pUrl);
            $this->scheme = $parts['scheme'];
            $this->host = $parts['host'];
            $this->port = $parts['port']??'80';
            $this->path = $parts['path'];
            if(isset($parts['query']) && !empty($parts['query'])){
                $params = explode("&", $parts['query']);
                for($i = 0, $max = count($params); $i<$max; $i++){
                    $p = explode("=", $params[$i]);
                    $this->queryParameters[$p[0]] = $p[1];
                }
            }
        }
    }
}