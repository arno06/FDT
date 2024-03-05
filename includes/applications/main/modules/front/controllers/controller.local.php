<?php

namespace app\main\controllers\front
{
    use core\application\DefaultController;

    class local extends DefaultController{
        public function __construct(){

        }

        public function checkLocalFolder(){

            $local_folder = $_POST['local_folder'];

            exec('cd '.$local_folder.' && git status', $output);

            if(empty($output) || !preg_match('/On branch\s(.*)/', $output[0], $matches)){
                $this->addContent('error', true);
            }else{
                $this->addContent('result', true);
                $this->addContent('branch', $matches[1]);
            }
        }

        public function readLocalFolder(){
            $local_folder = $_POST['local_folder'];
            $files = $this->readFiles($local_folder, ['.git', '.idea'], $local_folder);
            $this->addContent('files', $files);
        }

        public function getUpdatedGitInfo(){
            $files = $_POST['files'];
            $local_folder = $_POST['local_folder'];

            $final_files = [];
            foreach($files as $file){
                $output = [];
                $infos = stat($file['filename']);
                $lastMTime = $infos['mtime'];
                if(!exec('cd '.$local_folder.' && git log -1 --pretty="format:%ci" '.str_replace($local_folder.'/', '', $file['filename']), $output)){
                    $file['last_m_time'] = $lastMTime;
                    $final_files[] = $file;
                }else{
                    $final_files[]= array("filename"=>$file['filename'], "timestamp"=>strtotime($output[0]), "last_modified_date"=>$output[0], 'last_m_time'=>$lastMTime);
                }
            }

            $this->addContent('files', $final_files);
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
                    $return[]= array("filename"=>$f, "timestamp"=>null, "last_modified_date"=>null);
                }
            }
            closedir($dossier);
            return $return;
        }
    }
}