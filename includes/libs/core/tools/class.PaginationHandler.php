<?php
namespace core\tools
{

    use core\application\Core;
    use core\application\Dictionary;
    use core\db\Query;
	use core\db\QueryCondition;

	/**
	 * Class PaginationHandler
	 * Gestionnaire de pagination coté controller (pas de gestion de la mise en page)
	 *
	 * @author Arnaud NICOLAS <arno06@gmail.com>
	 * @version .3
	 * @package core\tools
	 */
	class PaginationHandler
	{
		/**
		 * Nombre d'entrée &agrave; afficher par page
		 * @var int
		 */
		private $itemsByPage;

		/**
		 * Nombre de pages max
		 * @var int
		 */
		private $nbPages;

		/**
		 * Numero de la page en cours
		 * @var int
		 */
		private $currentPage;

		/**
		 * Nombre d'entrées maximum
		 * @var int
		 */
		private $nbItems;

		/**
		 * @var int
		 */
		public $first;

		/**
		 * @var int
		 */
		public $number;

		/**
		 * Constructor
		 * @param int $pCurrentPage		Page en cours
		 * @param int $pNbItemByPage	Nombre d'item par page
		 * @param int $pNbItemsMax		Nombre total d'item dans la base
		 */
		public function __construct($pCurrentPage, $pNbItemByPage, $pNbItemsMax)
		{
			$this->currentPage = $pCurrentPage>0?$pCurrentPage:1;
			$this->itemsByPage = $pNbItemByPage;
			$this->nbItems = $pNbItemsMax;
			$this->nbPages = ceil($this->nbItems/$this->itemsByPage);
			$this->first = (($this->currentPage-1)*$this->itemsByPage);
			$this->number = $this->itemsByPage;
		}

		/**
		 * Méthode de récupération des infos nécessaires &agrave; la mise en place de la pagination dans la vue
		 * @return array
		 */
		public function getPaginationInfo()
		{
			return array("nbPages"=>$this->nbPages, "currentPage"=>$this->currentPage);
		}

		/**
		 * @return QueryCondition
		 */
		public function getConditionLimit()
		{
			return Query::condition()->limit($this->first, $this->number);
		}

        public function display($params = array())
        {
            $info = $this->getPaginationInfo();
            $noPage = 0;
            $url = Core::$url."?";
            extract($params);
            if(empty($info)||$info["nbPages"]<2)
                return;
            if (isset($info["noPage"])) $noPage = $info["noPage"];
            $parameters = $_GET;
            $parameters["page"] = $info["currentPage"]-1;

            echo '<div class="pagination"><div class="previous">';
            echo '<a href="'.$url.http_build_query($parameters).'" class="button '.(($info["currentPage"]==1)?'disabled':'').'">'.Dictionary::term("global.pagination.previous").'</a> ';
            echo '</div><div class="pages">';
            if ($noPage)
            {
                echo $info["currentPage"]." / ".$info["nbPages"];
            }
            else
            {
                for($i = 1; $i<=$info["nbPages"]; ++$i)
                {
                    if($i==1||$i==$info["currentPage"]||$i==($info["currentPage"]+1)||$i==($info["currentPage"]-1)||$i==$info["nbPages"])
                    {
                        $parameters["page"] = $i;
                        if($i>1)
                            echo ' - ';
                        if($i==$info["currentPage"])
                            echo '<span class="current_page">'.$i.'</span>';
                        else
                            echo '<a href="'.$url.http_build_query($parameters).'" class="button">'.$i.'</a>';
                    }
                    if(($i == $info["currentPage"]+2 || $i == $info["currentPage"]-2)&&($i!=1&&$i!=$info["nbPages"]))
                        echo " - ... ";
                }
            }
            echo '</div>';
            $parameters["page"] = $info["currentPage"]+1;
            echo '<div class="next">';
            echo '<a href="'.$url.http_build_query($parameters).'" class="button '.(($info["currentPage"]==$info["nbPages"]||!$info["nbPages"])?'disabled':'').'">'.Dictionary::term("global.pagination.next").'</a>';
            echo "</div></div>";
        }
	}
}
