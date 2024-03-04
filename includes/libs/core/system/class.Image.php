<?php
namespace core\system
{

    use core\application\Core;

    /**
     * Class Image
     * Permet de gérer les traitements en rapport avec les fichiers de type Image
     *
     * @author Arnaud NICOLAS <arno06@gmail.com>
     * @version 1.0
     * @package system
     */
    class Image extends TracingCommands
    {
        /**
         * @type string
         */
        const JPG = "jpg";

        /**
         * @type string
         */
        const JPEG = "jpeg";

        /**
         * @type string
         */
        const PNG = "png";

        /**
         * @type string
         */
        const GIF = "gif";

        /**
         * Largeur de l'image
         * @var int
         */
        public $width;

        /**
         * Hauteur de l'image
         * @var int
         */
        public $height;

        /**
         * Type d'image souhaitée
         * @var string
         */
        public $type;


        /**
         * constructor
         * @param int  $pWidth
         * @param int  $pHeight
         * @param string $pType
         * @param int  $pOverSampling
         */
        public function __construct($pWidth, $pHeight, $pType = self::JPG, $pOverSampling = 1)
        {
            parent::__construct();
            if($pOverSampling<1)
                $pOverSampling = 1;
            $this->width = $pWidth;
            $this->height = $pHeight;
            $this->type = $pType;
            $this->oversampling = $pOverSampling;
        }


        /**
         * @return void
         */
        private function draw()
        {
            $resource = imagecreatetruecolor($this->width*$this->oversampling, $this->height*$this->oversampling);
            if($this->type == self::PNG)
                $this->preparePNG($resource, $this->width*$this->oversampling, $this->height*$this->oversampling);

            $this->drawCommands($resource);

            if($this->oversampling>1)
            {
                $overSampled = imagecreatetruecolor($this->width, $this->height);
                if($this->type == self::PNG)
                    self::preparePNG($overSampled, $this->width, $this->height);
                imagecopyresampled($overSampled,$resource,0,0,0,0,$this->width, $this->height,$this->width*$this->oversampling, $this->height*$this->oversampling);
                imagedestroy($resource);
                $resource = &$overSampled;
            }

            switch($this->type)
            {
                case self::PNG:
                    imagepng($resource);
                    break;
                case self::JPG:
                case self::JPEG:
                    imagejpeg($resource);
                    break;
                case self::GIF:
                    imagegif($resource);
                    break;
            }
            imagedestroy($resource);
        }


        /**
         * @return void
         */
        public function render()
        {
            header('Content-Type: image/'.$this->type);
            $this->draw();
            Core::endApplication();
        }


        /**
         * @param $pFile
         * @return void
         */
        public function save($pFile)
        {
            ob_start();
            $this->draw();
            $rawdata = ob_get_contents();
            ob_end_clean();
            File::delete($pFile);
            File::create($pFile);
            File::append($pFile, $rawdata);
        }


        /**
         * @static
         * @param resource $pResource
         * @param int $pWidth
         * @param int $pHeight
         * @return void
         */
        static private function preparePNG(&$pResource, $pWidth, $pHeight)
        {
            imagesavealpha($pResource, true);
            imagealphablending($pResource, false);
            $transparent = imagecolorallocatealpha($pResource, 0, 0, 0, 127);
            imagefilledrectangle($pResource, 0, 0, ($pWidth)-1, ($pHeight)-1, $transparent);
            imagealphablending($pResource, true);
        }


        /**
         * @param $pFinalImage
         * @param $pMaxWidth
         * @param $pMaxHeight
         * @return bool
         */
        public function createCache($pFinalImage, $pMaxWidth, $pMaxHeight)
        {
            $ressource = imagecreatetruecolor($this->width*$this->oversampling, $this->height*$this->oversampling);
            if($this->type == self::PNG)
                $this->preparePNG($ressource, $this->width*$this->oversampling, $this->height*$this->oversampling);

            $this->drawCommands($ressource);

            if($this->oversampling>1)
            {
                $overSampled = imagecreatetruecolor($this->width, $this->height);
                if($this->type == self::PNG)
                    self::preparePNG($overSampled, $this->width, $this->height);
                imagecopyresampled($overSampled,$ressource,0,0,0,0,$this->width, $this->height,$this->width*$this->oversampling, $this->height*$this->oversampling);
                imagedestroy($ressource);
                $ressource = &$overSampled;
            }

            $TailleRedim = self::getProportionResize($this->width, $this->height, $pMaxWidth, $pMaxHeight);
            $ImageTampon = imagecreatetruecolor($TailleRedim["width"], $TailleRedim["height"]);
            imagecopyresampled($ImageTampon,$ressource,0,0,0,0,$TailleRedim["width"], $TailleRedim["height"],$this->width, $this->height);
            imagedestroy($ressource);
            $ressource = &$ImageTampon;

            switch ($this->type) {
                case self::JPG:
                case self::JPEG:
                    imagejpeg($ressource, $pFinalImage, 100);
                    break;
                case self::GIF:
                    imagegif($ressource, $pFinalImage);
                    break;
                case self::PNG:
                    imagepng($ressource, $pFinalImage);
                    break;
                default:
                    return false;
                    break;
            }
            imagedestroy($ressource);
            chmod($pFinalImage, 0666);
            return true;
        }


        /**
         * Méthode static de creation d'une copie d'une image avec redimensionnement
         * @param String $pSourceImage				Fichier source
         * @param String $pFinalImage				Fichier que l'on souhaite créer
         * @param float $pMaxWidth					Largeur du nouveau fichier
         * @param float $pMaxHeight				Hauteur du nouveau fichier
         * @return Boolean
         */
        static public function createCopy($pSourceImage, $pFinalImage, $pMaxWidth, $pMaxHeight) {
            if (!file_exists($pSourceImage))
                return false;
            if (file_exists($pFinalImage))
                chmod($pFinalImage, 0666);
            if (!$type = self::isImage($pSourceImage))
                return false;
            $size = self::getSize($pSourceImage);
            $currentWidth = $size[0];
            $currentHeight = $size[1];

            $TailleRedim = self::getProportionResize($currentWidth, $currentHeight, $pMaxWidth, $pMaxHeight);
            $ImageTampon = imagecreatetruecolor($TailleRedim["width"], $TailleRedim["height"]);
            switch ($type) {
                case self::JPG:
                case self::JPEG:
                    $ImageTampon2 = imagecreatefromjpeg($pSourceImage);
                    imagecopyresampled($ImageTampon, $ImageTampon2, 0, 0, 0, 0, $TailleRedim["width"], $TailleRedim["height"], $currentWidth, $currentHeight);
                    imagejpeg($ImageTampon, $pFinalImage, 100);
                    break;
                case self::GIF:
                    $ImageTampon2 = imagecreatefromgif($pSourceImage);
                    imagecopyresampled($ImageTampon, $ImageTampon2, 0, 0, 0, 0, $TailleRedim["width"], $TailleRedim["height"], $currentWidth, $currentHeight);
                    imagegif($ImageTampon, $pFinalImage);
                    break;
                case self::PNG:
                    $ImageTampon2 = imagecreatefrompng($pSourceImage);
                    self::preparePNG($ImageTampon, $TailleRedim["width"], $TailleRedim["height"]);
                    imagecopyresampled($ImageTampon,$ImageTampon2,0,0,0,0,$TailleRedim["width"],$TailleRedim["height"],$currentWidth,$currentHeight);
                    imagepng($ImageTampon, $pFinalImage);
                    break;
                default:
                    return false;
                    break;
            }
            imagedestroy($ImageTampon);
            imagedestroy($ImageTampon2);
            chmod($pFinalImage, 0666);
            return true;
        }


        /**
         * Méthode de redimensionnement d'une image existante
         * @param String $pSourceImage				Chemin de l'image &agrave; redimensionner
         * @param float $pMaxWidth						Largeur maximale souhaitée
         * @param float $pMaxHeight					Hauteur maximale souhaitée
         * @return Boolean
         */
        static public function resize($pSourceImage, $pMaxWidth, $pMaxHeight) {
            $size = self::getSize($pSourceImage);
            $currentWidth = $size[0];
            $currentHeight = $size[1];
            if (($pMaxWidth > $currentWidth) && ($pMaxHeight > $currentHeight))
                return true;
            $TailleRedim = self::getProportionResize($currentWidth, $currentHeight, $pMaxWidth, $pMaxHeight);
            return self::createCopy($pSourceImage, $pSourceImage, $TailleRedim["width"], $TailleRedim["height"]);
        }


        /**
         * Méthode de calcul de dimension apr&egrave;s redimensionnement en concervant les proportions
         * @param Number $pWidth			Largeur actuelle
         * @param Number $pHeight			Hauteur actuelle
         * @param float $pMaxWidth			Largeur max
         * @param float $pMaxHeight		Hauteur max
         * @return array
         */
        static public function getProportionResize($pWidth, $pHeight, $pMaxWidth, $pMaxHeight) {
            $TestW = round($pMaxHeight / $pHeight * $pWidth);
            $TestH = round($pMaxWidth / $pWidth * $pHeight);
            if ($TestW > $pMaxWidth) {
                $width = $pMaxWidth;
                $height = $TestH;
            } elseif ($TestH > $pMaxHeight) {
                $width = $TestW;
                $height = $pMaxHeight;
            } else {
                $width = $pMaxWidth;
                $height = $pMaxHeight;
            }
            return array("width"=>$width, "height"=>$height);
        }


        /**
         * Récup&egrave;re la hauteur et la largeur d'un fichier
         * @param String $pSourceImage				Fichier source dont on souhaite récupérer la taille
         * @return array
         */
        static public function getSize($pSourceImage) {
            return getimagesize($pSourceImage);
        }


        /**
         * Méthode permettant de vérifier si le fichier est bien une image (jpg, gif ou png)
         * @param String $pSourceImage				Fichier source
         * @return String
         */
        static public function isImage($pSourceImage) {
            $extract = array();
            if (preg_match('/^.*\.('.self::JPEG.'|'.self::JPG.'|'.self::GIF.'|'.self::PNG.')$/i', $pSourceImage, $extract))
                return strtolower($extract[1]);
            return "";
        }
    }
    /**
     * Class TracingCommands
     *
     * @author Arnaud NICOLAS <arno06@gmail.com>
     * @version .1
     * @package system
     */
    class TracingCommands
    {

        /**
         * @type string
         */
        const COMMAND_MOVETO            = "command_moveto";

        /**
         * @type string
         */
        const COMMAND_LINETO            = "command_lineto";

        /**
         * @type string
         */
        const COMMAND_SETLINESTYLE      = "command_setlinestyle";

        /**
         * @type string
         */
        const COMMAND_BEGINFILL         = "command_beginfill";

        /**
         * @type string
         */
        const COMMAND_ENDFILL           = "command_endfill";

        /**
         * @type string
         */
        const COMMAND_DRAWCIRCLE        = "command_drawcircle";

        /**
         * @type string
         */
        const COMMAND_DRAWELLIPSE       = "command_drawellipse";

        /**
         * @type string
         */
        const COMMAND_DRAWTEXT          = "command_drawtext";

        /**
         * @type string
         */
        const COMMAND_DRAWRECT          = "command_drawrect";

        /**
         * @type string
         */
        const COMMAND_SETPIXEL          = "command_setpixel";

        /**
         * @type string
         */
        const COMMAND_DRAWIMAGE         = "command_drawimage";

        /**
         * @type string
         */
        const COMMAND_CREATEIMAGE         = "command_createimage";

        /**
         * @var array
         */
        private $command;

        /**
         * @var int
         */
        protected $oversampling = 1;


        /**
         * Constructor
         */
        public function __construct()
        {
            $this->command = array();
        }


        /**
         * Méthode de définition du style de ligne souhaité
         * @param int $pR
         * @param int $pG
         * @param int $pB
         * @param int $pSize
         * @return void
         */
        public function setLineStyle($pR = 0, $pG = 0, $pB = 0, $pSize = 1)
        {
            $this->command[] = array("type"=>self::COMMAND_SETLINESTYLE,"r"=>$pR, "g"=>$pG, "b"=>$pB, "size"=>$pSize);
        }


        /**
         * Méthode de définition de la couleur de remplissage
         * @param number  $pR
         * @param number  $pG
         * @param number  $pB
         * @return void
         */
        public function beginFill($pR, $pG, $pB)
        {
            $this->command[] = array("type"=>self::COMMAND_BEGINFILL, "r"=>$pR, "g"=>$pG, "b"=>$pB);
        }


        /**
         * @param $pSrc
         * @param null $pWidth
         * @param null $pHeight
         * @param int $pX
         * @param int $pY
         */
        public function drawImage($pSrc, $pWidth = null, $pHeight = null, $pX = 0, $pY = 0)
        {
            $srcSize = Image::getSize($pSrc);
            if(!$pWidth)
                $pWidth = $srcSize[0];
            if(!$pHeight)
                $pHeight = $srcSize[1];
            $this->command[] = array("type"=>self::COMMAND_DRAWIMAGE, "src"=>$pSrc, "srcWidth"=>$srcSize[0], "srcHeight"=>$srcSize[1], "width"=>$pWidth, "height"=>$pHeight, "x"=>$pX, "y"=>$pY);
        }

        /**
         * @param $pSrc
         * @param $pWidth
         * @param $pHeight
         * @param $pPadding
         */
        public function createImage($pSrc, $pWidth, $pHeight, $pPadding)
        {
            $this->command[] = array("type"=>self::COMMAND_CREATEIMAGE, "src"=>$pSrc, "width"=>$pWidth, "height"=>$pHeight, "padding"=>$pPadding);
        }


        /**
         * Méthode permettant de mettre fin au remplissage
         * @return void
         */
        public function endFill()
        {
            $this->command[] = array("type"=>self::COMMAND_ENDFILL);
        }


        /**
         * @param int  $pX
         * @param int  $pY
         * @return void
         */
        public function moveTo($pX, $pY)
        {
            $this->command[] = array("type"=>self::COMMAND_MOVETO, "x"=>$pX, "y"=>$pY);
        }


        /**
         * @param int  $pX
         * @param int  $pY
         * @return void
         */
        public function lineTo($pX, $pY)
        {
            $this->command[] = array("type"=>self::COMMAND_LINETO,"x"=>$pX, "y"=>$pY);
        }


        /**
         * Méthode de dessin d'un texte sur l'image
         * @param string  $pString
         * @param int  $pSize
         * @param string  $pFont
         * @param int $pX
         * @param int $pY
         * @param int $pR
         * @param int $pG
         * @param int $pB
         * @param int $pRotation
         * @return void
         */
        public function drawText($pString, $pSize, $pFont, $pX=0, $pY=0, $pR=0, $pG=0, $pB=0, $pRotation = 0)
        {
            $this->command[] = array("type"=>self::COMMAND_DRAWTEXT, "text"=>$pString, "size"=>$pSize, "font"=>$pFont, "x"=>$pX, "y"=>$pY, "r"=>$pR, "g"=>$pG, "b"=>$pB, "rotation"=>$pRotation);
        }


        /**
         * Méthode de dessin d'un cercle
         * @param int $pX
         * @param int $pY
         * @param int $pRadius
         * @return void
         */
        public function drawCircle($pX, $pY, $pRadius)
        {
            $this->command[] = array("type"=>self::COMMAND_DRAWCIRCLE, "x"=>$pX, "y"=>$pY, "width"=>$pRadius*2, "height"=>$pRadius*2);
        }


        /**
         * Méthode de dessin d'une ellipse
         * @param int $pX
         * @param int $pY
         * @param int $pWidth
         * @param int $pHeight
         * @return void
         */
        public function drawEllipse($pX, $pY, $pWidth, $pHeight)
        {
            $this->command[] = array("type"=>self::COMMAND_DRAWELLIPSE, "x"=>$pX, "y"=>$pY, "width"=>$pWidth, "height"=>$pHeight);
        }


        /**
         * Méthode de dessin d'un rectangle
         * @param int $pX
         * @param int $pY
         * @param int $pWidth
         * @param int $pHeight
         * @return void
         */
        public function drawRectangle($pX, $pY, $pWidth, $pHeight)
        {
            $this->moveTo($pX, $pY);
            $this->lineTo($pX+$pWidth, $pY);
            $this->lineTo($pX+$pWidth, $pY+$pHeight);
            $this->lineTo($pX, $pY+$pHeight);
            $this->lineTo($pX, $pY);
        }


        /**
         * @param int $pX
         * @param int $pY
         * @param int $pR
         * @param int $pG
         * @param int $pB
         * @return void
         */
        public function setPixel($pX, $pY, $pR = 0, $pG = 0, $pB = 0)
        {
            $this->command[] = array("type"=>self::COMMAND_SETPIXEL, "x"=>$pX, "y"=>$pY, "r"=>$pR, "g"=>$pG, "b"=>$pB);
        }


        /**
         * @param resource  $pResource
         * @return void
         */
        protected function drawCommands($pResource)
        {
            $tmp = array("x"=>"0", "y"=>"0");
            $path = array();
            $drawingPolygon = false;
            $fill_color = -1;
            $line_color = -1;
            $props = array("x", "y", "width", "height", "size");
            $mProps = count($props);
            $hasGraduate = false;
            for($i = 0, $max = count($this->command); $i<$max;$i++)
            {
                $cmd = $this->command[$i];
                if(!isset($cmd["type"]))
                    continue;
                for($k = 0;$k<$mProps;$k++)
                    $cmd[$props[$k]] = $cmd[$props[$k]] * $this->oversampling;
                switch($cmd["type"])
                {
                    case self::COMMAND_DRAWIMAGE:
                        $type = Image::isImage($cmd["src"]);
                        if(empty($type))
                            trigger_error("L'image à copier ne correspond pas à un type compatible", E_USER_ERROR);
                        $res = null;
                        switch($type)
                        {
                            case Image::PNG:
                                $res = imagecreatefrompng($cmd["src"]);
                                break;
                            case Image::JPEG:
                            case Image::JPG:
                                $res = imagecreatefromjpeg($cmd["src"]);
                                break;
                            case Image::GIF:
                                $res = imagecreatefromgif($cmd["src"]);
                                break;
                        }
                        imagecopyresampled($pResource, $res, $cmd["x"], $cmd["y"], 0, 0, $cmd["width"], $cmd["height"], $cmd["srcWidth"], $cmd["srcHeight"]);
                        break;
                    case self::COMMAND_CREATEIMAGE:
                        $type = Image::isImage($cmd["src"]);
                        if(empty($type))
                            trigger_error("L'image à copier ne correspond pas à un type compatible", E_USER_ERROR);
                        $res = null;
                        switch($type)
                        {
                            case Image::PNG:
                                $res = imagecreatefrompng($cmd["src"]);
                                break;
                            case Image::JPEG:
                            case Image::JPG:
                                $res = imagecreatefromjpeg($cmd["src"]);
                                break;
                            case Image::GIF:
                                $res = imagecreatefromgif($cmd["src"]);
                                break;
                        }

                        for($x = 0 ; $x < 50 ; $x++)
                        {
                            for($y = 0 ; $y < 50 ; $y++)
                            {
                                $color = imagecolorat($res, $x, $y);
                                $r = ($color >> 16) & 0xFF;
                                $g = ($color >> 8) & 0xFF;
                                $b = $color & 0xFF;
                                if ($r < 30 && $g < 30 && $b < 30)
                                {
                                    $hasGraduate = true;
                                    break 2;
                                }
                            }
                        }

                        $rgb = imagecolorat($res, $cmd["width"]/2, 0);
                        $r = ($rgb >> 16) & 0xFF;
                        $g = ($rgb >> 8) & 0xFF;
                        $b = $rgb & 0xFF;

                        $background = imagecolorallocate($pResource, $r, $g, $b);
                        imagefill($pResource, 0, 0, $background);
                        imagecopy($pResource, $res, $cmd["padding"][3], $cmd["padding"][0], 0, 0, $cmd["width"], $cmd["height"]);

                        break;
                    case self::COMMAND_SETLINESTYLE:
                        $line_color = imagecolorallocate($pResource, $cmd["r"], $cmd["g"], $cmd["b"]);
                        imagesetstyle($pResource, array($line_color));
                        imagesetthickness ($pResource, $cmd["size"]);
                        break;
                    case self::COMMAND_BEGINFILL:
                        $fill_color = imagecolorallocate($pResource, $cmd["r"], $cmd["g"], $cmd["b"]);
                        $drawingPolygon = true;
                        $path = array();
                        break;
                    case self::COMMAND_ENDFILL:
                        if(count($path)<3||!$drawingPolygon)
                        {
                            $drawingPolygon = false;
                            $fill_color = -1;
                            $path = array();
                            continue;
                        }
                        if($fill_color>-1)
                            imagefilledpolygon($pResource, $path, count($path)/2, $fill_color);
                        if($line_color>-1)
                            imagepolygon($pResource, $path, count($path)/2, $line_color);
                        $drawingPolygon = false;
                        $fill_color = -1;
                        $path = array();
                        break;
                    case self::COMMAND_MOVETO:
                        if($drawingPolygon)
                            array_push($path, $cmd["x"], $cmd["y"]);
                        $tmp = array("x"=>$cmd["x"], "y"=>$cmd["y"]);
                        break;
                    case self::COMMAND_LINETO:
                        if ($hasGraduate)
                            break;

                        if($drawingPolygon)
                            array_push($path, $cmd["x"], $cmd["y"]);
                        else
                            imageline($pResource, $tmp["x"], $tmp["y"], $cmd["x"], $cmd["y"], IMG_COLOR_STYLED);
                        $tmp = array("x"=>$cmd["x"], "y"=>$cmd["y"]);
                        break;
                    case self::COMMAND_DRAWCIRCLE:
                    case self::COMMAND_DRAWELLIPSE:
                        if($fill_color>-1)
                            imagefilledellipse($pResource, $cmd["x"], $cmd["y"], $cmd["width"], $cmd["height"], $fill_color);
                        if($line_color>-1)
                            imageellipse($pResource, $cmd["x"], $cmd["y"], $cmd["width"], $cmd["height"], $line_color);
                        break;
                    case self::COMMAND_DRAWTEXT:
                        $c = imagecolorallocate($pResource, $cmd["r"], $cmd["g"], $cmd["b"]);
                        imagettftext($pResource, $cmd["size"], $cmd["rotation"], $cmd["x"], $cmd["y"], $c, $cmd["font"], $cmd["text"]);
                        break;
                    case self::COMMAND_DRAWRECT:
                        if($fill_color>-1)
                            imagefilledrectangle($pResource, $cmd["x"], $cmd["y"], $cmd["x1"], $cmd["y1"], $fill_color);
                        if($line_color>-1)
                            imagerectangle($pResource, $cmd["x"], $cmd["y"], $cmd["x1"], $cmd["y1"], $line_color);
                        break;
                    case self::COMMAND_SETPIXEL:
                        $c = imagecolorallocate($pResource, $cmd["r"], $cmd["g"], $cmd["b"]);
                        imagesetpixel($pResource, $cmd["x"], $cmd["y"], $c);
                        break;
                    default:
                        continue;
                        break;
                }
            }
        }
    }
}
