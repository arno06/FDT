<?php

namespace core\tools\docs
{
    class PHPDocHelpers{
        /**
         * @param string $pVarName
         * @param string $pComments
         * @return bool|string
         */
        static public function extractDocVar($pVarName, $pComments)
        {
            if(preg_match('/@'.$pVarName.'\s*([0-9a-z\_\[\]\/\"\=\:\^\@\(\)\\\\{\}\-\.\?\,\&]+)\s*/i', $pComments, $matches))
            {
                return $matches[1];
            }
            return false;
        }
    }
}