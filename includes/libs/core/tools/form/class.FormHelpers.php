<?php
namespace core\tools\form
{

    use core\application\Application;
    use core\application\Configuration;
    use core\application\Core;
    use core\application\Dictionary;
    use core\models\ModelUpload;

    /**
     * Class FormHelpers
     * @package core\tools\form
     */
    class FormHelpers
    {
        static private $helpers = array(
            Form::TAG_CHECKBOXGROUP=>"checkboxgroup",
            Form::TAG_UPLOAD=>"upload",
            Form::TAG_DATEPICKER=>"datepicker",
            Form::TAG_COLORPICKER=>"colorpicker",
            Form::TAG_RADIOGROUP=>"radiogroup",
            Form::TAG_RICHEDITOR=>"richeditor",
            Form::TAG_CAPTCHA=>"captcha",
            Form::TAG_INPUT=>"input",
            Form::TAG_SELECT=>"input",
            Form::TAG_TEXTAREA=>"input"
        );

        static private $ct_upload = 0;

        static private $ct_datepicker = 0;

        static public function script($pContent = "", $pSrc = "", $pReturn=false)
        {
            $d = "<script type='text/javascript'";
            if(!empty($pSrc))
                $d .= " src='".$pSrc."'";
            $d .= ">".$pContent."</script>";
            if($pReturn)
                return $d;
            echo $d;
            return "";
        }

        static public function getLabel($pLabel, $pFor, $pColon = true)
        {
            if(empty($pLabel))
            {
                $pLabel = "&nbsp;";
                $pFor = "";
            }
            elseif ($pColon)
                $pLabel .= " :";
            return "<label for='".$pFor."'>".$pLabel."</label>";
        }

        static public function getComponent($pComponent, $pClassName = "")
        {
            $className = isset($pClassName) && !empty($pClassName)?" ".$pClassName:"";
            $className = "input".$className;
            return '<div class="'.$className.'">'.$pComponent.'</div>';
        }

        static public function has($ptag)
        {
            return array_key_exists(strtolower($ptag), self::$helpers);
        }

        static public function get($pTag, $pParams)
        {
            $class = "component"." ".$pParams[1];
            if(isset($pParams[2]["attributes"]["type"])
                && $pParams[2]["attributes"]["type"]=="hidden")
                $class .= " hidden";
            if(isset($pParams[2]["attributes"]["type"])
                && $pParams[2]["attributes"]["type"]=="submit")
                $class .= " submit";
            if(isset($pParams[2]["inline"])
                && $pParams[2]["inline"])
                $class .= " inline";
            return "<div class='".$class."'>".call_user_func_array(array('core\tools\form\FormHelpers', self::$helpers[$pTag]), $pParams)."<div class='inp_separator'></div></div>";
        }

        static private function checkboxgroup($pName, $pId,$pData, $pRequire = "")
        {
            if(!isset($pData["options"])||!is_array($pData["options"]))
                return "";
            $style = "overflow:auto;";
            if(isset($pData["height"]))
                $style .= "height:".$pData["height"].";";
            if(isset($pData["width"]))
                $style .= "width:".$pData["width"].";";

            $class = '';
            if (isset($pData["attributes"]["class"])) {
                $class = ' '.$pData["attributes"]["class"];
            }
            $group = "<div class='checkboxgroup".$class."' style='".$style."'>";
            $i = 0;
            $style = "";
            if(isset($pData["display"])&&$pData["display"]=="block")
                $style = " style='display:block;'";
            $values = array();
            if(isset($pData["attributes"]["value"])) {
                for ($i = 0, $max = count($pData["attributes"]["value"]); $i < $max; $i++) {
                    array_push($values, $pData["attributes"]["value"][$i]);
                }
            }

            if(!empty($pData["options"]))
            {
                foreach($pData["options"] as $opt)
                {
                    $value = $opt["value"];
                    $label = $opt["label"];
                    $i++;
                    $defaultChecked = array_key_exists('checked', $opt) ? $opt["checked"] : false;
                    $c = "";
                    if($defaultChecked || in_array($value, $values))
                        $c = " checked";
                    $group .= '<span class="checkbox" '.$style.'><input type="checkbox" name="'.$pName.'[]" id="'.$pName.'_'.$i.'" value="'.$value.'" '.$c.' />&nbsp;&nbsp;<label for="'.$pName.'_'.$i.'">'.$label.'</label></span>';
                }
            }
            else
                $group .= "<span class='empty'>".Dictionary::term("global.forms.noAvailableValue")."</span>";
            $group .= '</div>';
            $input = self::getLabel($pData["label"].$pRequire, $pId);
            $input .= self::getComponent($group);
            return $input;
        }

        static private function upload($pName, $pId, $pData, $pRequire = "")
        {
            self::$ct_upload++;
            $file = $value = "";
            $server_url = Configuration::$server_url;

            $disabled = isset($pData["attributes"]["disabled"]) && $pData["attributes"]["disabled"] == "disabled"?"disabled":"";

            if(isset($pData["attributes"]["value"])&&!empty($pData["attributes"]["value"]))
            {
                $value = $pData["attributes"]["value"];
                $file = $server_url;
                /** @var ModelUpload $m */
                $m = (isset($pData["model"]) && !empty($pData["model"])) ? $pData["model"] : "core\\models\\ModelUpload";
                if(Form::isNumeric($value))
                    $file .= Application::getInstance()->getPathPart().$m::getPathById($value);
                else
                    $file .= $value;
            }
            $deleteFileAction = "";
            if(isset($pData['deleteFileAction']) && !empty($pData['deleteFileAction']))
            {
                if($value&&Form::isNumeric($value))
                    $action = preg_replace('/\{id\}/', $value, $pData['deleteFileAction']);
                else
                    $action = $pData['deleteFileAction'];
                $deleteFileAction = 'data-delete_file_action="'.$action.'"';
            }
            $comp = "<input ".$disabled." type='file' name='".$pName."_input' data-form_name='".$pData["form_name"]."' data-input_name='".$pData["field_name"]."' data-application='".Core::$application."' data-value='".$value."' data-file='".$file."' data-module='".Core::$module."'".$deleteFileAction.">";
            $input = self::getLabel($pData["label"].$pRequire, $pId);
            $input .= self::getComponent($comp, 'upload');
            return $input;
        }

        /**
         * @static
         * @param $pName
         * @param $pId
         * @param $pData
         * @param string $pRequire
         * @return string
         */
        static private function datepicker($pName, $pId, $pData, $pRequire = "")
        {
            self::$ct_datepicker++;
            $component = "<input ";
            $attributes = $pData["attributes"];
            if(!isset($attributes["id"]) || empty($attributes["id"]))
                $attributes["id"] = $pId."-dpicker";
            $attributes["name"] = $pName;
            $attributes["type"] = "text";
            if(!isset($attributes["class"]))
                $attributes["class"] = "";
            if(!empty($attributes["class"]))
                $attributes["class"] = " ";
            $attributes["class"] .= "datepicker";
            foreach($attributes as $name=>$value)
                $component .= $name."='".$value."' ";
            $component .= "/>";
            $component .= "<label for='".$attributes["id"]."'></label>";
            $extra = "";
            $input = self::getLabel($pData["label"].$pRequire, $pId);
            $input .= self::getComponent($component.$extra);
            return $input;
        }

        static private function colorpicker($pName, $pId, $pData, $pRequire = "")
        {
            $component = '<input type="text" name="'.$pName.'" id="'.$pId.'" class="color"';
            if(isset($pData["attributes"]))
            {
                foreach($pData["attributes"] as $prop=>$value)
                {
                    if($prop == "id" || $prop == "name")
                        continue;
                    $component .= ' '.$prop.'="'.$value.'"';
                }
            }
            $component .= '/>';
            $input = self::getLabel($pData["label"].$pRequire, $pId);
            $input .= self::getComponent($component);
            return $input;
        }

        /**
         * @static
         * @param $pName
         * @param $pId
         * @param $pData
         * @param string $pRequire
         * @return string
         */
        static private function radiogroup($pName, $pId, $pData, $pRequire = "")
        {
            if(!isset($pData["options"])||!is_array($pData["options"]))
                return "";
            $style = "overflow:auto;";
            if(isset($pData["height"]))
                $style .= "height:".$pData["height"].";";
            if(isset($pData["width"]))
                $style .= "width:".$pData["width"].";";

            $class = '';
            if (isset($pData["attributes"]["class"])) {
                $class = ' '.$pData["attributes"]["class"];
            }
            $group = "<div class='radiogroup".$class."' style='".$style."'>";
            $i = 0;
            $style = "";
            if(isset($pData["display"])&&$pData["display"]=="block")
                $style = " style='display:block;'";
            if(!empty($pData["options"]))
            {
                foreach($pData["options"] as $opt)
                {
                    $value = $opt["value"];
                    $label = $opt["label"];
                    $i++;
                    $select = "";
                    if(isset($pData['attributes']) && isset($pData['attributes']['value']) && $pData["attributes"]["value"]==$value)
                        $select = ' checked="checked"';
                    if (isset($opt["disabled"]) && $opt["disabled"] == "disabled")
                        $select .= " disabled=\"disabled\"";
                    $group .= '<span class="radio"'.$style.'><input id="radio_'.$pId.'_'.$i.'" type="radio" name="'.$pName.'" value="'.$value.'"'.$select.'/><label for="radio_'.$pId.'_'.$i.'">&nbsp'.$label.'&nbsp;</label></span>';
                }
            }
            else
                $group .= "<span class='empty'>".Dictionary::term("global.forms.noAvailableValue")."</span>";
            $group .= "</div>";
            $input = self::getLabel($pData["label"].$pRequire, $pId);
            $input .= self::getComponent($group);
            return $input;
        }

        static private function richeditor($pName, $pId, $pData, $pRequire = "")
        {
            trigger_error('To Be Implemented', E_USER_ERROR);
            return false;
        }

        static private function captcha($pName, $pId, $pData, $pRequire = "")
        {
            $l = "' onclick='return reloadCaptcha(this);";
            $r = self::getLabel("<span class='captcha'><img src='statique/captcha/form:".$pData["form_name"]."/input:".$pData["field_name"]."/' alt=''/><br/><span class='reload_captcha'>".Dictionary::term("global.forms.infosCaptcha").$pRequire."</span></span>", $pId);
            $r .= self::getComponent("<p class='input'><input type='text' name='".$pName."' id='".$pId."'/><br/><span class='details_captcha'>".sprintf(Dictionary::term("global.forms.reloadCaptcha"),$l)."</span></p>");
            return $r;
        }

        static private function input($pName, $pId, $pData, $pRequire = "")
        {
            $label = $selectValue = $textareaValue = $extra = "";
            $inline = isset($pData["inline"]) && $pData["inline"];
            if ($inline)
                $pRequire = "";
            if(!empty($pData["label"]))
                $label = $pData["label"].$pRequire;
            $input = self::getLabel($label, $pId, !$inline);
            if($pData["tag"] == Form::TAG_SELECT && isset($pData["attributes"]["multiple"]) && $pData["attributes"]["multiple"] == "multiple")
                $pName .= "[]";
            if(isset($pData["autoComplete"])
                &&isset($pData["attributes"]["type"])
                &&$pData["attributes"]["type"]=="text")
            {
                $pData["attributes"]["data-ac_minQueryLength"] = "3";
                $pData["attributes"]["data-ac_resultsLocator"] = "responses";
                $pData["attributes"]["data-ac_source"] = "statique/autocomplete/application:".Core::$application."/module:".Core::$module."/form_name:".$pData["form_name"]."/input_name:".$pData["field_name"]."/q:{query}/";
            }
            $component = '<'.$pData["tag"].' name="'.$pName.'" id="'.$pId.'"';
            foreach($pData["attributes"] as $prop=>$value)
            {
                $value = str_replace('"', "&quot;", $value??"");
                if($prop == "id" || $prop == "name")
                    continue;
                if($prop!="value")
                    $component .= ' '.$prop.'="'.$value.'"';
                else
                {
                    switch($pData["tag"])
                    {
                        case Form::TAG_INPUT:
                            if($pData["attributes"]["type"]=="checkbox"
                                ||$pData["attributes"]["type"]=="hidden"
                                ||$pData["attributes"]["type"]=="text"
                                ||$pData["attributes"]["type"]=="email"
                                ||$pData["attributes"]["type"]=="submit"
                                ||$pData["attributes"]["type"]=="button")
                            {
                                $component .= ' '.$prop.'="'.$value.'"';
                            }
                            break;
                        case Form::TAG_SELECT:
                            $selectValue = $value;
                            break;
                        case Form::TAG_TEXTAREA:
                            $textareaValue = $value;
                            break;
                        default:
                            $component .= ' '.$prop.'="'.$value.'"';
                            break;
                    }
                }
            }

            switch($pData["tag"])
            {
                case "input":
                    $component .= "/>";
                    if(isset($pData["autoFill"])
                        &&isset($pData["attributes"]["type"])
                        &&$pData["attributes"]["type"]=="text")
                    {
                        $extra .= self::script("AutoFillPlugin.applyTo(document.getElementById('".$pId."'), '".$pData["autoFill"]."');", "", true);
                    }
                    if(isset($pData["autoComplete"])
                        &&isset($pData["attributes"]["type"])
                        &&$pData["attributes"]["type"]=="text")
                    {
                        $extra .= self::script("Autocomplete.applyTo('#".$pId."');");
                    }
                    break;
                case "select":
                    if(isset($pData["chosen"]) && $pData["chosen"] === true)
                    {
                        $no_result = Dictionary::term("global.forms.chosen.no_result_text");
                        $default_text =Dictionary::term("global.forms.chosen.default_text");
                        if(isset($pData["parameters"]["no_result_text"]))
                            $no_result = $pData["parameters"]["no_result_text"];
                        if(isset($pData["parameters"]["default_text"]))
                            $default_text = $pData["parameters"]["default_text"];
                        $component .= ' data-placeholder="'.$default_text.'"';
                        $extra .= self::script('
                    if($("'.$pId.'__chosen")){$("'.$pId.'__chosen").parentNode.removeChild($("'.$pId.'_chosen"));}
                    new Chosen($("'.$pId.'"),{no_results_text: "'.$no_result.'", allow_single_deselect: '.($pData["require"]?'false':'true').'});
                ', "", true);
                    }
                    $options = "";
                    if(!isset($pData["options"]))
                        $pData["options"] = array();
                    if(!$pData["require"] && (!(isset($pData["attributes"]["multiple"]) || $pData["attributes"]["multiple"] == "multiple")))
                    {
                        $d = array("value"=>"", "name"=>"");
                        array_unshift($pData["options"], $d);
                    }
                    foreach($pData["options"] as $opt)
                    {
                        $value = $opt["value"];
                        $display = $opt["name"];
                        if(is_array($display))
                        {
                            $options .= "<optgroup label='".$value."'>";
                            foreach($display as $v=>$l)
                                $options .= self::comboBoxOptions($l, $v, $selectValue);
                            $options .= "</optgroup>";
                            continue;
                        }
                        $options .= self::comboBoxOptions($display, $value, $selectValue);
                    }
                    $component .= ">".$options."</select>";
                    break;
                case "textarea":
                    $component .= ">".$textareaValue."</textarea>";
                    break;
            }
            if ($inline)
                $input = self::getComponent($component.$extra) . $input;
            else
                $input .= self::getComponent($component.$extra);
            return $input;
        }

        static private function comboBoxOptions($pDisplay, $pValue, $pRealValue)
        {
            $s = "";
            if(is_string($pRealValue) && $pValue == $pRealValue)
                $s = "selected";
            else if (is_array($pRealValue) && in_array($pValue, $pRealValue))
                $s = "selected";
            return '<option value="'.$pValue.'" '.$s.'>'.$pDisplay.'</option>';
        }
    }
}
