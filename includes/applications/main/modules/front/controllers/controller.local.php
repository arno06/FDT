<?php

namespace app\main\controllers\front
{
    use core\application\DefaultController;
    use core\system\File;

    class local extends DefaultController{
        public function __construct(){

        }

        public function checkLocalFolder(){

            $local_folder = $_POST['local_folder'];

            if(!file_exists($local_folder.'/.git/HEAD')){
                $this->addContent('error', true);
            }

            $ref = File::read($local_folder.'/.git/HEAD');

            $this->addContent('result', true);
            $this->addContent('branch', str_replace('ref: refs/heads/', '', $ref));
        }

        public function readLocalFolder(){
            $local_folder = $_POST['local_folder'];
            $files = $this->readFiles($local_folder, ['.git', '.idea'], $local_folder);
            $this->addContent('files', $files);
        }

        public function getUpdatedGitInfo(){
            $files = $_POST['files'];
            $local_folder = $_POST['local_folder'];

            $max_pool = 50;
            $cmd = [];

            for($j = 0,$maxJ = ceil(count($files)/$max_pool); $j<$maxJ; $j++){

                $commands = ['cd '.$local_folder];
                for($i = $j * $max_pool, $max = min(($j * $max_pool)+$max_pool, count($files)); $i<$max; $i++){
                    $file = $files[$i];
                    if(!$file || !$file['filename']){
                        continue;
                    }
                    $commands[]= 'git log -1 --pretty="format:'.$i.'|%ci;" '.str_replace($local_folder.'/', '', $file['filename']);
                }
                $output = [];

                exec(implode(' && ', $commands), $output);

                if(!$output){
                    $cmd[] = $commands;
                    continue;
                }

                $output = explode(';', $output[0]);

                foreach($output as $infos){
                    if(empty(trim($infos))){
                        continue;
                    }
                    list($index, $date) = explode('|', $infos);
                    $files[$index]["timestamp"] = strtotime($date);
                    $files[$index]["last_modified_date"] = $date;
                }
            }

            if(!empty($cmd)){
                $this->addContent('cmd', $cmd);
            }
            $this->addContent('files', $files);
        }

        private function readFiles($pPath, $pIgnore, $pBaseFolder){
            $return = array();
            $dossier = opendir($pPath);
            $pPath = preg_replace('/\/$/', "", $pPath);
            while ($file = readdir($dossier))
            {
                if (in_array($file, $pIgnore) || $file == "." || $file == "..")
                {
                    continue;
                }
                $f = $pPath."/".$file;
                if(!is_file($f))
                    $return = array_merge($return, self::readFiles($f, $pIgnore, $pBaseFolder));
                else{
                    $infos = stat($f);
                    $lastMTime = $infos['mtime'];
                    $return[]= array("filename"=>$f, "timestamp"=>null, "last_modified_date"=>null, "last_m_time"=>$lastMTime);
                }
            }
            closedir($dossier);
            return $return;
        }
    }
}