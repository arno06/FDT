<?php
namespace core\models
{
    use core\application\BaseModel;
    use core\application\Configuration;
    use core\application\Core;
    use core\db\Query;

    /**
     * Model de gestion des authentifications
     *
     * @author Arnaud NICOLAS <arno06@gmail.com>
     * @version 1.0
     * @package models
     */
    class ModelAuthentication extends BaseModel
    {
        static private $instance;

        static public $data;

        public function __construct()
        {
            parent::__construct(sprintf(Configuration::$authentication_tableName,Core::$application), Configuration::$authentication_tableId);
        }

        static public function checkLoginAndHash($pLogin, $pHash){
            if(empty($pLogin)||empty($pHash))
                return false;

            $instance = self::getInstance();

            if($result = $instance->one(Query::condition()->andWhere(Configuration::$authentication_fieldLogin, Query::EQUAL, $pLogin)))
            {
                if($result[Configuration::$authentication_fieldPassword] == $pHash)
                {
                    self::$data = $result;
                    return true;
                }
            }
            return false;
        }

        static public function isUser($pLogin, $pMdp)
        {
            if(empty($pLogin)||empty($pMdp))
                return false;

            $instance = self::getInstance();

            if($result = $instance->one(Query::condition()->andWhere(Configuration::$authentication_fieldLogin, Query::EQUAL, $pLogin)))
            {
                if(password_verify($pMdp, $result[configuration::$authentication_fieldPassword]))
                {
                    self::$data = $result;
                    return true;
                }
            }
            return false;
        }

        public function createUser($pLogin, $pPassword, $pPermissions = 1){
            $data = array(Configuration::$authentication_fieldLogin=>$pLogin,
                Configuration::$authentication_fieldPassword=>password_hash($pPassword, PASSWORD_BCRYPT, array("cost"=>10)),
                Configuration::$authentication_fieldPermissions=>$pPermissions,
            );
            return $this->insert($data);
        }

        /**
         * @return ModelAuthentication
         */
        static public function getInstance()
        {
            if(!self::$instance)
                self::$instance = new ModelAuthentication();
            return self::$instance;
        }
    }
}
