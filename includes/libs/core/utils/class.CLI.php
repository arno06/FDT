<?php

namespace core\utils
{

    use core\application\Core;

    class CLI
    {

        const RED = '0;31';
        const GREEN = '0;32';
        const YELLOW = '1;33';
        const BLUE = '0;34';
        const WHITE = '1;37';
        const RESET = '39';

        static public function progressBar(){
            return new CLIProgressBar();
        }

        static public function newLine(){
            return new CLILine();
        }

        static public function resetLine(){
            return new CLILine("\033[2K\r");
        }

        static public function exit($pExitCode = 0){
            echo "\r\n";
            Core::endApplication($pExitCode);
        }

        /**
         * @return bool
         */
        static public function isCurrentContext():bool{
            return PHP_SAPI == "cli";
        }
    }

    class CLILine
    {

        public function __construct($pInit = ""){
            echo $pInit;
        }

        public function setTextColor($pColor){
            echo "\e[".$pColor."m";
            return $this;
        }

        public function resetTextColor(){
            return $this->setTextColor(CLI::RESET);
        }

        public function out($pString){
            echo $pString;
            return $this;
        }

        public function endOfLine(){
            echo "\r\n";
        }
    }

    class CLIProgressBar
    {
        private $steps;
        public function __construct($pSteps = 20){
            $this->steps = $pSteps;
        }

        public function update($pProgress, $pMessageBefore = "", $pMessageAfter = ""){
            $out = "[";
            for($i = 0; $i<$this->steps; $i++){
                $percent = round($i/$this->steps * 100);
                $out .= ($percent<=$pProgress)?"*":"_";
            }
            $out .= "]";
            CLI::resetLine()->out($pMessageBefore)->out(" ".$out." ")->out($pMessageAfter);
        }
    }
}