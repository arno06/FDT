<?php
namespace core\utils
{

    use core\application\Autoload;
    use core\application\Core;
    use core\system\File;
	use core\system\Folder;

	/**
	 * Class Logs
	 * Permet de gérer les fichiers de logs
	 *
	 * @author Arnaud NICOLAS <arno06@gmail.com>
	 * @version .1
	 * @package core\utils
	 */
	abstract class Logs
	{
		const NOTICE = "notice";
		const WARNING = "warning";
		const ERROR = "error";

		/**
		 * Méthode permettant d'enregistrer des données textuelles dans un fichier de Logs
		 * Définit le nom du dossier ainsi que celui du fichier en fonction de la date
		 * @param String $pMessage					Message &agrave; enregistrer dans le fichier
		 * @param String $pLevel					Niveau d'importance de l'information
		 * @return void
		 */
		static final public function write($pMessage, $pLevel= self::NOTICE)
		{
			$ip = $_SERVER["REMOTE_ADDR"];
			$folder = Autoload::$folder."/includes/logs/".date("m-y")."/";
			$file = date("d-m-y").".txt";
			$message = "[ ".date("H\hi\ms\s")." ] [".$ip."] [ ".Core::$application." ] [ ".$pLevel." ]\t\t".$pMessage."\r\n";
			Folder::create($folder);
			File::create($folder.$file);
			chmod($folder.$file, 0666);
			File::append($folder.$file, $message);
		}
	}
}
