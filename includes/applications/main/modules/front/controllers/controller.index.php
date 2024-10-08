<?php
namespace app\main\controllers\front
{

    use app\main\models\ModelEnv;
    use app\main\src\application\FDTController;
    use core\application\Autoload;
    use core\system\File;
    use core\utils\StringDiff;

    class index extends FDTController
    {

        public function __construct()
        {
        }

        public function index(){
            $m = new ModelEnv();
            $this->addContent('envs', $m->all());
            Autoload::addComponent('FDT');
        }

        public function filesComparison(){
            $files = $_POST['files'];
            if(!$this->initConnection()){
                $this->addContent('error', true);
                return;
            }
            $local_folder = $_POST['local_folder'];
            $identicals = 0;
            $comparisons = [];
            foreach($files as $idx=>$file){
                $filePath = str_replace($local_folder.'/', '', $file);
                $isBinary = !$this->isTextType($file);
                $localTmp = 'includes/applications/main/_cache/'.$idx.'.tmp';
                if(ftp_get($this->ftp, $localTmp, $filePath, !$isBinary?FTP_ASCII:FTP_BINARY)){
                    if(!$isBinary){
                        $tmpContent = File::read($localTmp);
                    }else{
                        $tmpContent = sha1_file($localTmp);
                    }
                    File::delete($localTmp);
                }else{
                    $tmpContent = "";
                }

                if(!$isBinary){
                    $localContent = File::read($file);

                    $from = explode(PHP_EOL, $tmpContent);
                    $to = explode(PHP_EOL, $localContent);
                    $diff = new StringDiff();
                    $res = $diff->compare($from, $to);
                    if($res->identicals){
                        $identicals++;
                    }
                }else{
                    $localContent = sha1_file($file);
                    $res = new GenericDiff();
                    if($localContent === $tmpContent){
                        $identicals++;
                        $res->identicals = true;
                    }
                }
                $comparisons[] = ["file"=>$file, "comparison"=>$res, "isBinary"=>$isBinary];
            }
            if(count($files) === $identicals){
                $this->addContent('identicals', true);
            }
            $this->addContent('comparisons', $comparisons);
            ftp_close($this->ftp);
        }

        public function invalidateOPCache(){
            $files = $_POST['files'];
            $domain = $_POST['domain'];
            if(!$this->initConnection()){
                $this->addContent('error', true);
                return;
            }
            $local_folder = $_POST['local_folder'];
            $function_calls = [];
            foreach($files as $idx=>$file) {
                $filePath = str_replace($local_folder . '/', '', $file);
                if(preg_match('/\.tpl$/i', $filePath, $matches)){
                    $function_calls = ['opcache_reset();'];
                    break;
                }
                if(!preg_match('/\.php$/i', $filePath, $matches)){
                    continue;
                }
                $function_calls[] = 'opcache_invalidate("'.$filePath.'", true);';
            }
            $invalidates = implode(PHP_EOL, $function_calls);
            $invalidateScript = <<<PHP
<?php
//
$invalidates
echo "Cache invalidé";
exit();
PHP;

            $local = 'fdt_invalidate.php';
            File::create($local);
            File::append($local, $invalidateScript);
            if(ftp_put($this->ftp, $local, $local, FTP_ASCII)){
                file_get_contents($domain.$local);
                ftp_delete($this->ftp, $local);
            }
            File::delete($local);
            $this->addContent('invalidated', true);
        }
    }

    class GenericDiff{
        public $identicals = false;
    }
}
