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
        const DEBUG = true;

        public $original;

        public $new;

        public $additions = [];

        public $deletions = [];

        private $ranges = [];

        public $identicals = false;

        public function __construct($pOriginal, $pTo){
            $this->original = $pOriginal;
            $this->new = $pTo;
            $this->compare();
        }

        private function _trace($pMessage){
            if(!self::DEBUG){
                return;
            }
            trace_r($pMessage);
        }

        private function compare(){
            $diff = 0;
            for($i = 0, $max = count($this->original); $i<$max; $i++){
                $l = $this->original[$i];
                $l2 = $this->new[$i + $diff]??"";
                if(empty(trim($l))){
                    if(!empty(trim($l2)))
                        $diff -= 1;
                    //$this->_trace("ignoring empty string");
                    continue;
                }
                $this->_trace($i." checking ".$l." vs ".($i+$diff)." ".$l2);
                if(trim($l) === trim($l2)){
                    //$this->_trace(" equal ");
                    continue;
                }
                $this->_trace("  else deletion ");
                for($k = $i + $diff; $k<count($this->new); $k++){
                    $l2 = $this->new[$k];
                    if(trim($l) == trim($l2)){
                        $this->_trace(" found further on ".$k." ".$l2." ".$diff);
                        $this->_trace(' adding between '.($k-(1)).' && '.(($k-(1))+($k - ($i+$diff))));
                        for($j = 0, $maxj = $k - ($i+$diff); $j<$maxj; $j++){
                            $this->additions[] = array('content'=>$this->new[$k-($j+1)], 'index'=>$k-($j+1), 'precision'=>'alreadyin');
                            $diff += 1;
                            $this->_trace("  added ".($k-($j+1))." ".$this->new[$k-($j+1)]);
                        }
                        $this->_trace(" after additions ".$diff);
                        continue 2;
                    }
                }
                foreach($this->additions as $addition){
                    if(trim($addition['content']) === trim($l)){
                        $d = $addition['index'] - $i;
                        if(abs($d)>10){
                            $this->_trace(' diff '.$diff);
                            continue;
                        }
                        $diff = $d;
                        $this->_trace(" found in additions ".$addition['content']." new diff ".$diff);
                        $this->_trace($addition);
                        $refIndex = $addition['index'];
                        $additions = [];
                        foreach($this->additions as $idx=>$add){
                            if($add['index']<$refIndex){
                                $additions[] = $add;
                            }
                            $this->_trace('not added '.$add['content']);
                        }
                        $this->additions = $additions;
                        $this->_trace($this->additions);
                        continue 2;
                    }
                }
                $this->_trace(" not found therefor deleted");
                $this->deletions[] = array('content'=>$l, 'index'=>$i);
                $diff -= 1;
            }

            for($i+=$diff; $i<count($this->new); $i++){
                $this->additions[] = array('content'=>$this->new[$i], 'index'=>$i);
            }

            $this->additions = array_map(function($pEntry){return $pEntry['index'];}, $this->additions);
            $this->deletions = array_map(function($pEntry){return $pEntry['index'];}, $this->deletions);

            $modifs = array_merge($this->additions, $this->deletions);

            $this->identicals = empty($modifs);

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
        }
    }
}