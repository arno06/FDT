<?php
namespace core\application
{
	/**
	 * Interface de controller (front et backoffice)
	 *
	 * @author Arnaud NICOLAS <arno06@gmail.com>
	 * @version .1
	 * @package core\application
	 */
	interface InterfaceController
	{
		/**
		 * Constructor
		 */
		function __construct();

		/**
		 * Méthode appelée par défault lorsqu'aucune action n'a été appelée
		 * @return void
		 */
		function index();
	}
}
