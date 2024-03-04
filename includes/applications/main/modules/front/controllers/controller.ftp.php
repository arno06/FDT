<?php
namespace app\main\controllers\front{

    use core\application\DefaultController;

    class ftp extends DefaultController{

        public function __construct(){

        }

        public function checkServer(){
            $host = $_POST['host'];
            $user = $_POST['user'];
            $pass = $_POST['pass'];
            $folder = $_POST['folder'];
            $ftp = ftp_ssl_connect($host);
            $login_result = ftp_login($ftp, $user, $pass);
            if (!$login_result) {
                $this->addContent('error', true);
                return;
            }
            ftp_pasv($ftp, true);

            if (!ftp_chdir($ftp, $folder)) {
                $this->addContent('error', true);
            }else{
                $this->addContent('result', true);
            }
            ftp_close($ftp);
        }
    }
}