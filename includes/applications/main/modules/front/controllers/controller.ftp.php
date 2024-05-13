<?php
namespace app\main\controllers\front{


    use app\main\src\application\FDTController;
    use core\application\Core;
    use core\application\routing\RoutingHandler;
    use core\system\Folder;
    use core\utils\SimpleRandom;

    class ftp extends FDTController {

        private $init_connection;

        public function __construct(){
            $this->init_connection = $this->initConnection();
            if(!$this->init_connection){
                $this->addContent('error', true);
            }
        }

        public function checkServer(){

            if(!$this->init_connection){
                return;
            }
            $this->addContent('result', true);
            ftp_close($this->ftp);
        }

        public function uploadFiles(){
            if(!$this->init_connection){
                return;
            }
            $ascii_types = ['txt','csv','json', 'xml', 'html', 'tpl', 'php', 'js', 'css'];
            $local_folder = $_POST['local_folder'];
            $files = $_POST['files'];
            $uploaded_files = [];
            $failed_files = [];
            $created_folder = [];
            foreach($files as $file){
                $distant = str_replace($local_folder.'/', '', $file);
                $parts = explode('.', $distant);
                $ext = strtolower(array_pop($parts));

                $parts = explode('/', $distant);
                array_pop($parts);

                $current_folder = '';
                foreach($parts as $f){
                    $current_folder .= $f.'/';
                    if(ftp_mkdir($this->ftp, $current_folder)){
                        $created_folder[] = $current_folder;
                    }
                }

                if(ftp_put($this->ftp, $distant, $file, in_array($ext, $ascii_types)?FTP_ASCII:FTP_BINARY)){
                    $uploaded_files[] = $file;
                }else{
                    $failed_files[] = $file;
                }
            }
            if(count($files) === count($uploaded_files)){
                $this->addContent('uploaded', true);
            }
            $this->addContent('uploaded_files', $uploaded_files);
            $this->addContent('failed_files', $failed_files);
            $this->addContent('created_folder', $created_folder);
            ftp_close($this->ftp);
        }

        public function backup(){
            if(!$this->init_connection){
                return;
            }

            $idbackup = SimpleRandom::string(8);

            $folder = RoutingHandler::sanitize($_POST['name']);
            $backup_folder = 'files/backups/'.$folder.'/'.$idbackup;

            Folder::create($backup_folder);

            $files = $_POST['files'];
            $local_folder = $_POST['local_folder'];
            $backup_files = [];
            $failed_files = [];

            foreach($files as $file){
                $distant = str_replace($local_folder.'/', '', $file);
                $local = $backup_folder.'/'.$distant;
                $parts = explode('/', $local);
                array_pop($parts);
                Folder::create(implode('/', $parts));
                if(ftp_get($this->ftp, $local, $distant, $this->isTextType($distant)?FTP_ASCII:FTP_BINARY)){
                    $backup_files[] = $local;
                }else{
                    $failed_files[] = $file;
                }
            }
            if(count($files) === count($backup_files)){
                $this->addContent('backup', true);
            }
            $this->addContent('id_backup', $idbackup);
            $this->addContent('backup_files', $backup_files);
            $this->addContent('failed_files', $failed_files);
            $this->addContent('folder', $backup_folder);
            ftp_close($this->ftp);
        }
    }
}