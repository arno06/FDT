<?php
namespace app\main\controllers\front
{

    use core\application\Application;
    use core\application\Autoload;
    use core\application\DefaultController;
    use core\system\File;
    use core\utils\StringDiff;

    class index extends DefaultController
    {

        public function __construct()
        {

        }

        public function index(){
            Autoload::addComponent('FDT');
        }

        public function filesComparison(){
            $files = $_POST['files'];
            $host = $_POST['host'];
            $user = $_POST['user'];
            $pass = $_POST['pass'];
            $folder = $_POST['folder'];
            $local_folder = $_POST['local_folder'];

            $ftp = ftp_ssl_connect($host);
            $login_result = ftp_login($ftp, $user, $pass);
            if (!$login_result) {
                $this->addContent('error', true);
                return;
            }
            ftp_pasv($ftp, true);

            if (!ftp_chdir($ftp, $folder)) {
                $this->addContent('error', true);
                ftp_close($ftp);
                return;
            }
            $comparisons = [];
            foreach($files as $idx=>$file){
                $filePath = str_replace($local_folder.'/', '', $file);
                $localTmp = 'includes/applications/main/_cache/'.$idx.'.tmp';
                if(ftp_get($ftp, $localTmp, $filePath, FTP_ASCII)){
                    $tmpContent = File::read($localTmp);
                    File::delete($localTmp);
                }else{
                    $tmpContent = "";
                }

                $localContent = File::read($file);

                $from = explode(PHP_EOL, $tmpContent);
                $to = explode(PHP_EOL, $localContent);
                $diff = new StringDiff();
                $res = $diff->compare($from, $to);
                $comparisons[] = ["file"=>$file, "comparison"=>$res];
            }
            $this->addContent('comparisons', $comparisons);
            ftp_close($ftp);
        }
    }
}
