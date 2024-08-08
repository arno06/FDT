<?php

namespace app\main\controllers\front
{

    use core\application\Autoload;
    use core\application\DefaultController;
    use core\system\File;
    use core\system\Folder;
    use core\utils\StringDiff;

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
            $files = $this->readFiles($local_folder, ['.git', '.idea']);
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

        public function getSavedProjects(){
            $projects = Folder::read('files/backups/', false);

            $backups = [];

            foreach($projects as $file=>$project){
                if($file == ".gitkeep"){
                    continue;
                }
                $count = count(Folder::read($project['path']), false);
                $backups[] = ["name"=>$file, "count"=>$count];
            }

            $this->addContent('backups', $backups);
        }

        public function getBackups(){
            $folders = Folder::read('files/backups/'.$_GET['project'], false);

            $backups = [];

            foreach($folders as $file=>$project){
                $time = filemtime($project['path']);
                $backups[] = ["project"=>$_GET['project'], "name"=>$file, "date"=>date('d/m/y H:i', $time), "time"=>$time];
            }

            usort($backups, function($pA, $pB){
                return $pB['time'] - $pA['time'];
            });
            $this->addContent('backups', $backups);
        }

        public function getBackup(){

            $files = $this->readFiles('files/backups/'.$_GET['project'].'/'.$_GET['backup'].'/', []);

            $this->addContent('files', $files);
        }

        private function readFiles($pPath, $pIgnore){
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
                    $return = array_merge($return, self::readFiles($f, $pIgnore));
                else{
                    $infos = stat($f);
                    $lastMTime = $infos['mtime'];
                    $name = explode('/', $f);
                    $name = array_pop($name);
                    $return[]= array("filename"=>$f, "timestamp"=>null, "last_modified_date"=>null, "last_m_time"=>$lastMTime, "name"=>$name);
                }
            }
            closedir($dossier);
            return $return;
        }

        public function test(){
            Autoload::addComponent('FDT');
            $remote = 'files/distant.file';
            $local = 'files/local.file';

            $tmpContent = File::read($remote);
            $localContent = File::read($local);

            $from = explode(PHP_EOL, $tmpContent);
            $to = explode(PHP_EOL, $localContent);
            $diff = new StringDiff();
            $res = $diff->compare($from, $to);

            trace_r($res, true);
            $this->addContent('comparisons', [['comparison'=>$res]]);

        }
    }
}