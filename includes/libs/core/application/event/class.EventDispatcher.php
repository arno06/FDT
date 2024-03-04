<?php
namespace core\application\event
{
	/**
	* Class EventDispatcher
	*
	* @author Arnaud NICOLAS <arno06@gmail.com>
	* @version .1
	* @package core\application
	* @subpackage event
	*/
	class EventDispatcher
	{
		/**
		 * @var array
		 */
		private $listeners = array();


		/**
		 * Méthode de définition d'un nouvel EventListener, le listener attendu est le nom d'une méthode (String) dont on peut définir le contexte
		 * @param string $pType
		 * @param string $pListener
		 * @param null $pContext
		 * @return void
		 */
		public function addEventListener($pType, $pListener, $pContext = NULL)
		{
			if(!isset($this->listeners[$pType])||!is_array($this->listeners[$pType]))
				$this->listeners[$pType] = array();
			if(!$pContext)
				$pContext = $this;
			$this->listeners[$pType][] = array("listener"=>$pListener, "context"=>$pContext);
		}


		/**
		 * Méthode de déclenchement d'un év&egrave;nement, appel basiquement l'ensemble des listeners définis
		 * @param Event $pEvent
		 * @return void
		 */
		public function dispatchEvent(Event $pEvent)
		{
			$type = $pEvent->type;
			if(!isset($this->listeners[$type])||!is_array($this->listeners[$type]))
				return;
			$pEvent->target = $this;
			for($i = 0,$max = count($this->listeners[$type]); $i<$max; $i++)
			{
				$lst = $this->listeners[$type][$i]["listener"];
				$ctx = $this->listeners[$type][$i]["context"];
				try
				{
					$ctx->$lst($pEvent);
				}
				catch(Exception $e){}
			}
		}


		/**
		 * Méthode de suppression d'un EventListener, stop l'écoute d'un év&egrave;nement du Listener en fonction de son contexte
		 * @param string $pType
		 * @param string $pListener
		 * @param null $pContext
		 * @return void
		 */
		public function removeEventListener($pType, $pListener, $pContext = NULL)
		{
			if(!isset($this->listeners[$pType])||!is_array($this->listeners[$pType]))
				return;
			if(!$pContext)
				$pContext = $this;
			$listener = array();
			for($i = 0,$max = count($this->listeners[$pType]); $i<$max; $i++)
			{
				$lst = $this->listeners[$pType][$i]["listener"];
				$ctx = $this->listeners[$pType][$i]["context"];
				if($lst!==$pListener || $ctx !== $pContext)
					$listener[] = $this->listeners[$pType][$i];
			}
			$this->listeners[$pType] = $listener;
		}


		/**
		 * @return void
		 */
		public function removeAllEventListeners()
		{
			$this->listeners = array();
		}
	}
}
