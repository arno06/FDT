<?php

namespace core\utils
{

    use core\system\File;

    class StringDiff
    {

        public function __construct()
        {

        }

        public function compare($pFrom, $pTo){
            return new DiffResult($pFrom, $pTo);
        }
    }

    class DiffResult
    {
        public $original;

        public $new;

        public $additions = [];

        public $deletions = [];

        private $ranges = [];

        public function __construct($pOriginal, $pTo){
            $this->original = $pOriginal;
            $this->new = $pTo;
            $this->compare();
        }

        private function compare(){

            $diff = 0;
            for($i = 0, $max = count($this->original); $i<$max; $i++){
                $l = $this->original[$i];
                $l2 = $this->new[$i + $diff];
                if(empty(trim($l))){
                    if(!empty(trim($l2)))
                        $diff -= 1;
                    continue;
                }
                if($l === $l2){
                    continue;
                }
                for($k = $i + $diff; $k<count($this->new); $k++){
                    $l2 = $this->new[$k];
                    if($l === $l2){
                        for($j = 0, $maxj = $k - ($i+$diff); $j<$maxj; $j++){
                            $this->additions[] = array('content'=>$this->new[$k-($j+1)], 'index'=>$k-($j+1));
                            $diff += 1;
                        }
                        continue 2;
                    }
                }
                $this->deletions[] = array('content'=>$l, 'index'=>$i);
                $diff -= 1;
            }

            for($i+=$diff; $i<count($this->new); $i++){
                $this->additions[] = array('content'=>$this->new[$i], 'index'=>$i);
            }

            $this->additions = array_map(function($pEntry){return $pEntry['index'];}, $this->additions);
            $this->deletions = array_map(function($pEntry){return $pEntry['index'];}, $this->deletions);

            $modifs = array_merge($this->additions, $this->deletions);

            sort($modifs);

            foreach($modifs as $index){
                $within = false;
                foreach($this->ranges as &$range){
                    if(in_array($index, range($range[0], $range[1]))){
                        $within = true;
                        $range[0] = min($range[0], $index-3);
                        $range[1] = max($range[1], $index+4);
                        break;
                    }
                    if(in_array($index, range($range[0]-3, $range[1]))){
                        $within = true;
                        $range[0] = $index-3;
                        break;
                    }
                    if(in_array($index, range($range[0], $range[1]+3))){
                        $within = true;
                        $range[1] = $index+4;
                        break;
                    }
                }
                if($within){
                    continue;
                }
                $this->ranges[] = [$index-3, $index+4];
            }
        }

        public function getTotalAdditions(){
            return count($this->additions);
        }

        public function getTotalDeletions(){
            return count($this->deletions);
        }

        public function formatOriginal(){
            return $this->formatLines($this->original, $this->deletions, "deleted");
        }

        public function formatNew(){
            return $this->formatLines($this->new, $this->additions, "added");
        }

        private function formatLines($pLines, $pChanged, $pClass){
            $return = [];
            foreach($this->ranges as $range){
                $r = "";
                for($i = max($range[0], 0); $i<min($range[1], count($pLines)); $i++){
                    $r .= '<div class="line'.(in_array($i, $pChanged)?' '.$pClass:'').'"><div class="number">'.($i+1).'</div><pre class="data">'.htmlentities($pLines[$i]).'</pre></div>';
                }
                $return[] = $r;
            }

            return implode('<div class="hidden_lines"></div>', $return);
            /*
             * $return = "";
             * for($i = 0, $max = count($pLines); $i<$max; $i++){
                $return .= '<div class="line'.(in_array($i, $pChanged)?' '.$pClass:'').'"><div class="number">'.($i+1).'</div><pre class="data">'.htmlentities($pLines[$i]).'</pre></div>';
            }*/
        }
    }
}