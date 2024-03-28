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
    }
}