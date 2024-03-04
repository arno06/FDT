<?php
namespace core\application
{

    use core\tools\debugger\Debugger;
    use core\tools\Dependencies;
    use core\tools\form\Form;
    use core\models\ModelUpload;
    use core\system\File;
    use core\system\Image;
    use core\data\SimpleJSON;
    use core\db\Query;
    use core\tools\form\Upload;
    use core\tools\form\Captcha;
    use core\utils\OPCacheHelper;
    use \Exception;

    /**
     * Controller StaticController - définit les pages statiques "utilitaires"
     *
     *
     * @author Arnaud NICOLAS <arno06@gmail.com>
     * @version 1.1
     * @package application
     * @subpackage controller
     */
    class StaticController extends DefaultController
    {

        public function check_env()
        {
            /**
             * Vérifier la configuration
             * Vérifier les dossiers de caches (existence + droit d'écriture)
             */
        }

        public function dependencies()
        {
            $type = Dependencies::TYPE_JS;
            if(isset($_GET['type'])&&in_array($_GET['type'], array(Dependencies::TYPE_JS, Dependencies::TYPE_CSS)))
                $type = $_GET['type'];
            $d = new Dependencies($type);
            $d->retrieve();
        }

        /**
         * M&eacute;thode permettant de redimensionner une image upload&eacute;e (enregistr&eacute;e en base)
         * http://www.site.com/statique/resize/id:2/w:200/h:200/
         *
         * $_GET["id"]		int		Id de l'upload
         * $_GET["w"]		int		largeur max souhait&eacute;e
         * $_GET["h"]		int		hauteur max souhait&eacute;e
         * @return void
         */
        public function resize()
        {
            if(!Form::isNumeric($_GET["id"])||!Form::isNumeric($_GET["w"])||!Form::isNumeric($_GET["h"]))
                Go::to404();
            if(!file_exists($image = ModelUpload::getPathById($_GET["id"])))
                Go::to404();

            preg_match(File::REGEXP_EXTENSION, $image, $extract);
            $ext = $extract[1];
            $folder_cache = "includes/applications/".Core::$application."/_cache/imgs/";
            $file_cache = $folder_cache."resize_".$_GET["id"]."_".$_GET["w"]."_".$_GET["h"].".".$ext;
            if(Application::getInstance()->__toString() != Application::DEFAULT_APPLICATION)
                Configuration::$server_url .= "../";
            if(file_exists($file_cache))
                Header::location(Configuration::$server_url.$file_cache);

            Image::createCopy($image, $file_cache, $_GET["w"], $_GET["h"]);
            Header::location(Configuration::$server_url.$file_cache);
        }

        /**
         * @return void
         */
        public function captcha()
        {
            if(!Core::checkRequiredGetVars("form", "input"))
                Go::to404();
            $form = $_GET["form"];
            $input = $_GET["input"];
            $form = new Form($form);
            $captcha = $form->getInput($input);
            if(empty($captcha) || $captcha["tag"] != Form::TAG_CAPTCHA)
                Go::to404();

            $attributes = $captcha["attributes"];
            $available_attributes = array("backgroundColor", "fontSizeRange", "width", "height", "rotation", "transparent", "valueMax");
            $available_collections = array(
                "fontColors"=>"addFontColor",
                "fontFace"=>"addFontFace"
            );

            if(!isset($attributes["length"]) || empty($attributes["length"])|| $attributes["length"]==0)
                $attributes["length"] = 5;
            if(!isset($attributes["type"]) || empty($attributes["type"]))
                $attributes["type"] = "random";

            $c = new Captcha($attributes["length"], $input, $attributes["type"]);

            foreach($available_collections as $name=>$method){
                if(!isset($attributes[$name]) || !is_array($attributes[$name]))
                    continue;
                array_map(array($c, $method), $attributes[$name]);
            }
            for($i = 0, $max = count($available_attributes); $i<$max; $i++)
            {
                if(isset($attributes[$available_attributes[$i]])&&!empty($attributes[$available_attributes[$i]]))
                    $c->{$available_attributes[$i]} = $attributes[$available_attributes[$i]];
            }
            $c->render();
            Core::endApplication();
        }

        /**
         * @return void
         */
        public function autocomplete()
        {
            $datas = null;
            $response = array("error"=>"");
            if(!isset($_GET["form_name"])||empty($_GET["form_name"]))
                $response["error"] = '$_GET["form_name"] require';
            if(!isset($_GET["input_name"])||empty($_GET["input_name"]))
                $response["error"] = '$_GET["input_name"] require';
            if(empty($response["error"]))
            {
                $path_to_form = "includes/applications/".$_GET["application"]."/modules/";
                if($_GET["module"])
                    $path_to_form .= $_GET["module"]."/";
                else
                    $path_to_form .= "front/";
                $path_to_form .= "forms/form.".$_GET["form_name"].".json";
                try
                {
                    $datas = SimpleJSON::import($path_to_form);
                }
                catch (Exception $e)
                {
                    $response["error"] = "Formulaire introuvable ".$path_to_form;
                    $this->response($response);
                }
                if(!is_array($datas[$_GET["input_name"]]))
                {
                    $response["error"] = "Champs cibl&eacute; introuvable";
                    $this->response($response);
                }

                $input = $datas[$_GET["input_name"]];

                if($input["tag"]!=Form::TAG_INPUT || $input["attributes"]["type"]!="text")
                {
                    $response["error"] = "Champs cibl&eacute; n'est pas un input type 'text'";
                    $this->response($response);
                }

                if(!$input["autoComplete"] || !is_array($input["autoComplete"]))
                {
                    $response["error"] = "Les &eacute;l&eacute;ments de bases ne sont pas renseign&eacute;s";
                    $this->response($response);
                }
                $model = new $input["autoComplete"]["model"]();
                $cond = Query::condition()->andWhere($input["autoComplete"]["value"], Query::LIKE, "%".$_GET["q"]."%");
                if(isset($input["autoComplete"]["condition"])&&is_array($input["autoComplete"]["condition"])&&count($input["autoComplete"]["condition"]))
                {
                    foreach($input["autoComplete"]["condition"] as $m=>$p)
                        call_user_func_array(array($cond, $m), $p);
                }

                if (isset($_GET["replies"]) && Form::isNumeric($_GET["replies"]))
                    $result = $model->$input["autoComplete"]["method"]($_GET["replies"]);
                else
                    $result = $model->$input["autoComplete"]["method"]($cond, $input["autoComplete"]["value"]);

                $response["responses"] = array();
                foreach($result as $r)
                {
                    $d = array("value"=>$r[$input["autoComplete"]["value"]]);
                    if(isset($input["autoComplete"]["raw"]) && is_array($input["autoComplete"]["raw"]))
                    {
                        foreach($input["autoComplete"]["raw"] as $v)
                            $d[$v] = $r[$v];
                    }
                    $response["responses"][] =$d;

                }
            }
            $this->response($response);
        }

        static private function convertConfSize($pSize){
            if(!preg_match('/^([0-9]+)([OKMGT])/', $pSize, $matches)){
                return false;
            }
            $units = ["K", "M", "G", "T"];
            $size = $matches[1];
            $unit = $matches[2];
            for($i = 0, $max = array_search($unit, $units); $i<=$max; $i++){
                $size *= 1024;
            }
            return $size;
        }

        /**
         * ATTENTION AU NAME DE L'INPUT
         * ==> FORM[INPUTNAME] <==
         *
         * ATTENTION A LA TECHNIQUE DE RENVOIE D'INFORMATION !M&eacute;thode priv&eacute;e
         *
         * @return void
         */
        public function upload_async()
        {
            $datas = null;
            $response = array("error"=>"");

            $upload_size = self::convertConfSize(ini_get("upload_max_filesize"));
            $post_size = self::convertConfSize(ini_get("post_max_size"));

            $content_size = $_SERVER['CONTENT_LENGTH'];

            if($content_size > $upload_size || $content_size > $post_size){
                $min_size = min($upload_size, $post_size);
                $response["error"] = 'Le fichier transmis est trop volumineux ('.Debugger::formatMemory($content_size).'). Poids maximum autorisé : '.Debugger::formatMemory($min_size);
                $this->response($response);
            }

            if(!isset($_POST["form_name"])||empty($_POST["form_name"]))
            {
                $response["error"] = '$_POST["form_name"] require';
                $this->response($response);
            }
            if(!isset($_POST["input_name"])||empty($_POST["input_name"]))
            {
                $response["error"] = '$_POST["input_name"] require';
                $this->response($response);
            }

            $file = $_FILES[$_POST["input_name"]];
            if(!isset($file)||empty($file)){
                $response["error"] = "Aucun fichier n'a été transmis";
                $this->response($response);
            }
            $app = $_POST["application"];
            $path_to_form = "includes/applications/".$app."/modules/";
            if(isset($_POST["module"])&&!empty($_POST['module']))
                $path_to_form .= $_POST["module"]."/";
            else
                $path_to_form .= "front/";
            $form_name = $_POST["form_name"];
            $path_to_form .= "forms/form.".$form_name.".json";
            if (!file_exists($path_to_form))
                $path_to_form = preg_replace("/_[0-9]+\.json$/", ".json", $path_to_form);
            try
            {
                $datas = SimpleJSON::import($path_to_form);
            }
            catch (Exception $e)
            {
                $response["error"] = "Formulaire introuvable ".$path_to_form;
                $this->response($response);
            }

            if(!is_array($datas[$_POST["input_name"]]))
            {
                $response["error"] = "Champs cibl&eacute; introuvable";
                $this->response($response);
            }

            $input = $datas[$_POST["input_name"]];

            if($input["tag"]!=Form::TAG_UPLOAD && ($input["tag"]!="input"&&$input["attributes"]["type"]!="file"))
            {
                $response["error"] = "Le champ ciblé n'est pas un input type 'file'";
                $this->response($response);
            }

            if(isset($input['fileSize'])){
                $acceptedFileSize = self::convertConfSize($input['fileSize']);
                if($acceptedFileSize<$content_size){
                    $response["error"] = 'Le fichier transmis est trop volumineux ('.Debugger::formatMemory($content_size).'). Poids maximum accepté : '.Debugger::formatMemory($acceptedFileSize);
                    $this->response($response);
                }
            }

            $fileName = "";
            if(isset($input["fileName"]))
                $fileName = "file".(rand(0,999999));
            $folderName = Form::PATH_TO_UPLOAD_FOLDER;
            if(isset($input["folder"]))
                $folderName .= $input["folder"];

            $upload = new Upload($file, $folderName, $fileName);
            if(isset($input["resize"])&&is_array($input["resize"]))
                $upload->resizeImage($input["resize"][0],$input["resize"][1]);
            if(!$upload->isMimeType($input["fileType"]))
            {
                $response["error"] = "Type de fichier non-autorisé (".$input["fileType"].")";
                $this->response($response);
            }
            try
            {
                $upload->send();
            }
            catch(Exception $e)
            {
                $response["error"] = "Upload impossible ".$e->getMessage();
                $this->response($response);
            }
            if(isset($input["fileName"])&&!empty($input["fileName"]))
            {
                $fileName = preg_replace("/(\{id})/", $upload->id_upload, $input["fileName"]);
                $upload->renameFile($fileName);
            }
            $response["path_upload"] = Application::getInstance()->getPathPart().$upload->pathFile;
            $response["id_upload"] = $upload->id_upload;
            $this->response($response);
        }

        private function response($response)
        {
            Core::performResponse(json_encode($response), "json");
        }

        /**
         * Les routes sont à définir dans le fichier de routing de l'application ciblée
         * ie :
         * "some/url":
         *  {
         *      "parameters":{
         *          "OPTIONS":{
         *              "methods":["GET"],
         *              "headers":["awaited-header"]
         *          }
         *      },
         *      "OPTIONS":{
         *          "controller":"statique",
         *          "action":"handleOptionsRequest"
         *      }
         *  }
         *  Note dans l'action du controller ciblé par la méthode HTTP appelée par un domaine distant, il est nécessaire d'appeler :
         *      Header::allowOrigin('domain.tld');
         */
        public function handleOptionsRequest(){
            if(!Core::checkRequiredGetVars("OPTIONS")){
                Go::to404();
            }
            $options = $_GET["OPTIONS"];
            $domains = $options['domains']??array("*");
            $methods = $options['methods']??array('GET');
            $headers = $options['headers']??array('Content-Type');
            Header::handleOptionsRequest($domains, $methods, $headers);
            Core::endApplication();
        }

        public function opcache_invalidate(){
            if(!Core::debug() || !Core::checkRequiredGetVars('script')){
                Go::to404();
            }
            $result = OPCacheHelper::getInstance()->invalidate($_GET['script']);
            Core::performResponse($result?'true':'false');
        }
    }
}
