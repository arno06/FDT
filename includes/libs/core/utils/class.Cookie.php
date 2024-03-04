<?php
namespace core\utils
{
    use core\application\Configuration;

    /**
     * Class Cookie Permet une gestion simple des cookie
     *
     * @author Arnaud NICOLAS <arno06@gmail.com>
     * @version .2
     * @package core\utils
     */
    class Cookie
    {
        /**
         * Méthode de définition d'un nouveau cookie
         * @param string $pId
         * @param string $pValue
         * @param string $pTime
         * @param string|null $pDomain
         * @return void
         */
        static public function set($pId, $pValue, $pTime = "default", $pDomain = null)
        {
            $ids = explode(".", $pId);
            $t = "";
            $cookies = &$_COOKIE;
            for($i = 0, $max = count($ids); $i<$max;$i++)
            {
                $n = $ids[$i];
                if($i == $max-1)
                {
                    $cookies[$n] = $pValue;
                }
                else{
                    if(!isset($cookies[$n]) || !is_array($cookies[$n]))
                        $cookies[$n] = array();
                    $cookies = &$cookies[$n];
                }
                $t.= ($i>0?"[":"").$ids[$i].($i>0?"]":"");
            }
            if($pTime == "default")
                $pTime = time() + 3600;
            setcookie($t, $pValue, $pTime, "/".(!empty(Configuration::$server_folder)?Configuration::$server_folder."/":""), $pDomain);
        }

        /**
         * Méthode de récupération de la valeur d'un cookie
         * Renvoie false si inexistant
         * @param  string $pId
         * @return string|bool
         */
        static public function get($pId)
        {
            return Stack::get($pId, $_COOKIE);
        }

        /**
         * Méthode de suppression d'un cookie
         * @param string $pId
         * @return void
         */
        static public function delete($pId)
        {
            $ids = explode(".", $pId);
            self::set($pId, "", time()-3600);
            if(count($ids) === 1)
            {
                unset($_COOKIE[$pId]);
            }
        }
    }
}
