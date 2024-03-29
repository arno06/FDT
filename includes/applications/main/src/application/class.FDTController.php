<?php

namespace app\main\src\application{

    use core\application\DefaultController;

    class FDTController extends DefaultController
    {
        protected $ftp;

        protected function initConnection(){
            $host = $_POST['host'];
            $user = $_POST['user'];
            $pass = $_POST['pass'];
            $folder = $_POST['folder'];
            $this->ftp = ftp_ssl_connect($host);
            if(!$this->ftp){
                return false;
            }
            $login_result = ftp_login($this->ftp, $user, $pass);
            if (!$login_result) {
                return false;
            }
            ftp_pasv($this->ftp, true);

            if (!ftp_chdir($this->ftp, $folder)) {
                ftp_close($this->ftp);
                return false;
            }
            return true;
        }
    }
}