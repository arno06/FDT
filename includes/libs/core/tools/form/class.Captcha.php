<?php
namespace core\tools\form
{
	use core\system\Image;
	use core\utils\SimpleRandom;

	/**
	 * Class Captcha
	 *
	 * @author Arnaud NICOLAS <arno06@gmail.com>
	 * @version .1
	 * @package tools
	 * @subpackage form
	 */
	class Captcha
	{
		/**
		 * @type string
		 */
		const DEFAULT_FONT = "includes/libs/core/tools/form/font.LinLibertine.ttf";

		/**
		 * @type string
		 */
		const SESSION_VAR_NAME = "captcha";

        /**
         * @type string
         */
        const DEFAULT_TYPE = "random";

		/**
		 * @var int
		 */
		public $width = 100;

		/**
		 * @var int
		 */
		public $height = 30;

		/**
		 * @var string
		 */
		public $backgroundColor = "#ffffff";

		/**
		 * @var bool
		 */
		public $transparent = false;

		/**
		 * @var int
		 */
		public $rotation = 15;

        /**
         * @var array
         */
        public $fontSizeRange = [13];

        /**
         * @var int
         */
        public $valueMax = 20;

		/**
		 * @var array
		 */
		private $fonts = array(Captcha::DEFAULT_FONT);

		/**
		 * @var array
		 */
		private $fontColors = array();

		/**
		 * @var
		 */
		private $length;

		/**
		 * @var string
		 */
		private $name = "";

		/**
		 * @var string
		 */
		private $value;

        /**
         * @var string
         */
        private $displayedValue;

        /**
         * @var string
         */
        private $type;


        /**
         * Captcha constructor.
         * @param int $pLength
         * @param string $pName
         * @param string $pType
         */
		public function __construct($pLength, $pName, $pType = self::DEFAULT_TYPE)
		{
			$this->length = $pLength;
			$this->name = $pName;
            $this->type = strtolower($pType);
            $method = "generate".ucfirst($this->type)."Value";
            if(!method_exists($this, $method)){
                $method = "generateRandomValue";
            }
            $this->{$method}();
		}


        private function generateRandomValue()
        {
            $this->value = SimpleRandom::string($this->length);
            $this->displayedValue = $this->value;
        }

        private function generateCalculusValue()
        {
            $this->value = rand(0, $this->valueMax);
            $operations = array(
                "addition",
                "substraction"
            );

            $operator = $operations[rand(0, count($operations)-1)];

            switch($operator){
                case "substraction":
                    $y = rand($this->value+1, $this->value*2);
                    $z = $this->value + $y;
                    $this->displayedValue = $z."-".$y;
                    break;
                case "addition":
                    $y = rand(1, $this->value-1);
                    $z = $this->value - $y;
                    $this->displayedValue = $y."+".$z;
                    break;
            }
        }

		/**
		 * @param string $pColor  Format #rrggbb
		 * @return void
		 */
		public function addFontColor($pColor)
		{
			$this->fontColors[] = $pColor;
		}


		/**
		 * @param string $pTTFFile
		 * @return void
		 */
		public function addFontFace($pTTFFile)
		{
			$this->fonts[] = $pTTFFile;
		}


		/**
		 * @return string
		 */
		public function getValue()
		{
			return $_SESSION[self::SESSION_VAR_NAME][$this->name];
		}


		/**
		 * @return void
		 */
		public function unsetSessionVar()
		{
			unset($_SESSION[self::SESSION_VAR_NAME][$this->name]);
			if(empty($_SESSION[self::SESSION_VAR_NAME]))
				unset($_SESSION[self::SESSION_VAR_NAME]);
		}


		/**
		 * @return void
		 */
		public function render()
		{
			if(empty($this->fontColors))
				$this->fontColors[] = "#000000";
            $fontSizeMin = 12;
            $fontSizeMax = 12;
			if(count($this->fontSizeRange)==2){
                $fontSizeMin = $this->fontSizeRange[0];
                $fontSizeMax = $this->fontSizeRange[1];
            }
            if(count($this->fontSizeRange)==1){
                $fontSizeMin = $this->fontSizeRange[0];
                $fontSizeMax = $this->fontSizeRange[0];
            }
			$distance = $this->width/$this->length;
			$_SESSION[self::SESSION_VAR_NAME][$this->name]=$this->value;
			$img = new Image($this->width, $this->height, Image::PNG, 1);
			if(!$this->transparent)
				$img->beginFill(hexdec(substr($this->backgroundColor, 1,2)), hexdec(substr($this->backgroundColor, 3,2)), hexdec(substr($this->backgroundColor, 5,2)));
			$img->drawRectangle(0, 0, $this->width, $this->height);
			$img->endFill();
			for($i = 0, $max = strlen($this->displayedValue); $i<$max;$i++)
			{
				$c = $this->fontColors[rand(0, count($this->fontColors)-1)];
				$f = $this->fonts[rand(0, count($this->fonts)-1)];
				$s = rand($fontSizeMin, $fontSizeMax);
				$img->drawText(substr($this->displayedValue, $i, 1), $s, $f, ($distance/4) + $i*$distance, $s + (($this->height-$s)/2), hexdec(substr($c, 1,2)),hexdec(substr($c, 3,2)),hexdec(substr($c, 5,2)), rand(-$this->rotation,$this->rotation));
			}
			$img->render();
		}
	}
}
