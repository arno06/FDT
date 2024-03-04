<?php
namespace core\application\authentication
{
    use \core\application\Configuration;
    use core\application\Core;
    use core\models\ModelAuthentication;

    /**
     * Class Authentication
     * Permet de gérer les différentes sessions d'identifications via un Login, un Mot de passe et un jeton "unique"
     * @author Arnaud NICOLAS <arno06@gmail.com>
     * @version .2
     * @package application
     * @subpackage authentication
     */
    class Authentication
    {
        /**
         * Nom de base de la variable de session
         * @var String
         */
        protected $sessionVar = "Authentication_";

        /**
         * Indique la valeur des permissions alouées &agrave; l'utilisateur
         * @var int
         */
        public $permissions;

        /**
         * Mot de passe
         * @var String
         */
        protected $mdp_user;

        /**
         * Login
         * @var String
         */
        protected $login_user;

        /**
         * Jeton
         * @var String
         */
        protected $token;

        /**
         * Données de l'utilisateur si son authentication est vérifiée
         * @var	array
         */
        public $data;

        /**
         * Constructor
         */
        public function __construct()
        {
            $this->sessionVar .= Core::$application;
            if(!isset($_SESSION[$this->sessionVar])
                ||!is_array($_SESSION[$this->sessionVar]))
            {
                return;
            }
            $this->parseSessionVar();
            $this->checkIfLogged();
        }

        /**
         * Méthode de vérification de l'identité de l'utilisateur (dans le cas où on aura detecté une session correspondante)
         * @return void
         */
        public function checkIfLogged()
        {
            if(!$this->login_user||!$this->mdp_user||!$this->token)
            {
                $this->checkIfNoLogged();
                return;
            }
            $token = $this->getToken($this->mdp_user);
            if(ModelAuthentication::checkLoginAndHash($this->login_user, $this->mdp_user)&&$token==$this->token)
            {
                $this->permissions = ModelAuthentication::$data[Configuration::$authentication_fieldPermissions];
                $this->data = ModelAuthentication::$data;
            }
            else
                $this->unsetAuthentication();
        }

        /**
         * @return void
         */
        private function checkIfNoLogged()
        {
            ModelAuthentication::checkLoginAndHash($this->login_user, $this->mdp_user);
            $this->data = ModelAuthentication::$data;
        }


        /**
         * Méthode de définition des variables de session pour l'instance d'authentication en cours
         * @param  $pLogin
         * @param  $pMdp
         * @param bool $pAdmin
         * @return bool
         */
        public function setAuthentication($pLogin, $pMdp, $pAdmin = false)
        {
            if(ModelAuthentication::isUser($pLogin, $pMdp))
            {
                $lvl = AuthenticationHandler::$permissions[AuthenticationHandler::USER];
                if($pAdmin)
                    $lvl = AuthenticationHandler::$permissions[AuthenticationHandler::ADMIN];
                $isAutorized = $lvl&ModelAuthentication::$data[Configuration::$authentication_fieldPermissions];

                if($isAutorized)
                {
                    $pass = ModelAuthentication::$data[Configuration::$authentication_fieldPassword];
                    $token = $this->getToken($pass);
                    $_SESSION[$this->sessionVar] = array("login_user"=>$pLogin, "mdp_user"=>$pass,"token"=>$token);
                    return true;
                }
            }
            return false;
        }


        /**
         * Méthode de parsing des variables de la session d'authentication en cours
         * @return void
         */
        protected function parseSessionVar()
        {
            foreach($_SESSION[$this->sessionVar] as $name=>$value)
            {
                if(property_exists("core\\application\\Authentication\\Authentication",$name))
                    $this->$name = $value;
            }
        }


        /**
         * Méthode de suppression des variables de session pour l'instance d'authentication en cours
         * @return void
         */
        public function unsetAuthentication()
        {
            $_SESSION[$this->sessionVar] = array();
            unset($_SESSION[$this->sessionVar]);
        }


        /**
         * Méthode de définition du jeton
         * @param String $pMdp		Mot de passe hashé
         * @return String
         */
        protected function getToken($pMdp)
        {
            return md5($_SERVER["REMOTE_ADDR"].$pMdp);
        }
    }
}
