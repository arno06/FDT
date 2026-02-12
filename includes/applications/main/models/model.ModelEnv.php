<?php

namespace app\main\models
{

    use core\data\SimpleJSON;
    use core\system\File;

    class ModelEnv
    {
        const FILE_NAME = "includes/resources/envs.json";

        public function all(){
            $envs = $this->loadFile();
            usort($envs, function($pA, $pB){
                return strcmp(strtolower($pA['name']), strtolower($pB['name']));
            });
            return $envs;
        }

        public function insert($pValues){
            $envs = $this->loadFile();
            $envs[] = $pValues;
            $this->saveEnvs($envs);
        }

        private function saveEnvs($pEnvs){
            $envs = SimpleJSON::encode($pEnvs);
            File::delete(self::FILE_NAME);
            File::create(self::FILE_NAME);
            return File::append(self::FILE_NAME, $envs);
        }

        private function loadFile(){

            try{
                $envs = SimpleJSON::import(self::FILE_NAME);
            }
            catch(\Exception $e){
                trigger_error('Impossible de charger le fichier d\'environnements', E_USER_WARNING);
                $envs = [];
            }
            return $envs;
        }
    }
}