<?php
namespace app\main\controllers\front{


    use app\main\src\application\FDTController;

    class ftp extends FDTController {

        public function __construct(){

        }

        public function checkServer(){

            if(!$this->initConnection()){
                $this->addContent('error', true);
                return;
            }
            $this->addContent('result', true);
            ftp_close($this->ftp);
        }

        public function uploadFiles(){
            if(!$this->initConnection()){
                $this->addContent('error', true);
                return;
            }
            $local_folder = $_POST['local_folder'];
            $files = $_POST['files'];
            $uploaded = 0;
            foreach($files as $file){
                $distant = str_replace($local_folder.'/', '', $file);

                if(ftp_put($this->ftp, $distant, $file, FTP_ASCII)){
                    $uploaded++;
                }
            }
            if(count($files) === $uploaded){
                $this->addContent('uploaded', true);
            }
            ftp_close($this->ftp);
        }
    }
}