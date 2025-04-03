<?php

namespace app\main\src\application{

    use core\application\DefaultController;
    use core\utils\Logs;

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
                $this->ftp = ftp_connect($host);
                $login_result = ftp_login($this->ftp, $user, $pass);
                if (!$login_result) {
                    return false;
                }
            }
            ftp_pasv($this->ftp, true);

            if (!ftp_chdir($this->ftp, $folder)) {
                ftp_close($this->ftp);
                return false;
            }
            return true;
        }

        public function render($pDisplay = true)
        {
            if($this->ftp){
                ftp_close($this->ftp);
            }
            return parent::render($pDisplay);
        }

        protected function isTextType($pFile){
            $types_text = ['php', 'js', 'css', 'json', 'xml', 'html', 'tpl', 'htaccess', 'txt', 'csv', 'svg'];
            $parts = explode('.', $pFile);
            $ext = strtolower(array_pop($parts));
            return in_array($ext, $types_text);
        }
    }
}