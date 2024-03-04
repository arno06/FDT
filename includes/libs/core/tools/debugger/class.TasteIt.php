<?php
namespace core\tools\debugger
{

    use core\application\Core;
    use core\system\Image;
	use core\db\Query;


	class TasteIt
	{

		static public function clock()
		{
			$o = array("x"=>100, "y"=>100);
			$p = 30;
			$img = new Image(200, 220, Image::PNG, 4);
			$img->setLineStyle(0,0,0,1);
			$img->beginFill(225, 225, 225);
			$img->drawCircle($o["x"],$o["y"],99);
			$img->endFill();
			$img->drawCircle($o["x"],$o["y"],98);
			$img->drawCircle($o["x"],$o["y"],98.5);
			$img->setLineStyle(0,0,0,2);
			for($i = 0, $max = 12; $i<$max;$i++)
			{
				$d = $p*$i;
				$r = $d * (M_PI/180);
				$img->moveTo($o["x"]+cos($r) * 87, $o["y"]+sin($r) * 87);
				$img->lineTo($o["x"]+cos($r) * 98, $o["y"]+sin($r) * 98);
			}

			$img->setLineStyle(0,0,0,1);
			for($i = 0, $max = 60, $p = 6; $i<$max;$i++)
			{
				$d = $p*$i;
				$r = $d * (M_PI/180);
				$img->moveTo($o["x"]+cos($r) * 93, $o["y"]+sin($r) * 93);
				$img->lineTo($o["x"]+cos($r) * 98, $o["y"]+sin($r) * 98);
			}

			$s = date("s")*1;
			$m = date("i")*1+($s/60);
			$h = (date("g")*1)+($m/60)+2;

			$d = ($h * 30) - 90;
			$r = $d * (M_PI/180);
			$img->setLineStyle(0,0,0,3);
			$img->moveTo($o["x"], $o["y"]);
			$img->lineTo($o["x"]+cos($r) * 60, $o["y"]+sin($r) * 60);

			$d = ($m * 6) - 90;
			$r = $d * (M_PI/180);
			$img->setLineStyle(0,0,0,2);
			$img->moveTo($o["x"], $o["y"]);
			$img->lineTo($o["x"]+cos($r) * 70, $o["y"]+sin($r) * 70);

			$d = ($s * 6) - 90;
			$r = $d * (M_PI/180);
			$img->setLineStyle(0,0,0,1);
			$img->moveTo($o["x"], $o["y"]);
			$img->lineTo($o["x"]+cos($r) * 75, $o["y"]+sin($r) * 75);

			$img->beginFill(127,127,127);
			$img->drawCircle($o["x"], $o["y"], 3);
			$img->endFill();

			$img->render();
            Core::endApplication();
		}

		static public function graph()
		{
			$data = array(10, 5, 5, 1, 3, 14, 9, 5, 6, 10,10,9,10,13,12,5);
			$maxY = 15;

			$mx = 25;
			$my = 20;
			$width = 500;
			$height = 300;
			$rwidth = $width - ($mx*2);
			$rheight = $height - ($my*2);
			$rows = 5;
			$cols = 5;

			$img = new Image($width, $height, Image::PNG, 4);

			$pas = $rheight/$rows;
			$img->setLineStyle(200,200,200,1);
			for($i = 1; $i<$rows; $i++)
			{
				$img->moveTo($mx, $my+($pas*$i));
				$img->lineTo($mx+$rwidth, $my+($pas*$i));
			}
			$pas = $rwidth/$cols;
			for($i = 1; $i<$cols;$i++)
			{
				$img->moveTo($mx+($pas*$i), $my);
				$img->lineTo($mx+($pas*$i), $my+$rheight);
			}
			$img->setLineStyle(0, 0, 0, 2);
			$img->drawRectangle($mx, $my, $rwidth, $rheight);

			$max = count($data);
			$pas = $rwidth/($max-1);
			$img->setLineStyle(255, 90, 90, 2);
			$img->moveTo($mx, $rheight+(($data[0]/$maxY)*($my-$rheight)));
			for($i = 1; $i<$max;$i++)
			{
				$img->lineTo($mx+($i*$pas), $rheight+(($data[$i]/$maxY)*($my-$rheight)));
				$img->beginFill(255, 90, 90);
				$img->drawCircle($mx+(($i-1)*$pas), $rheight+(($data[$i-1]/$maxY)*($my-$rheight)), 4);
				$img->beginFill(255, 255, 255);
				$img->drawCircle($mx+(($i-1)*$pas), $rheight+(($data[$i-1]/$maxY)*($my-$rheight)), 3);
				$img->endFill();
			}
			$img->beginFill(255, 90, 90);
			$img->drawCircle($mx+(($i-1)*$pas), $rheight+(($data[$i-1]/$maxY)*($my-$rheight)), 4);
			$img->beginFill(255, 255, 255);
			$img->drawCircle($mx+(($i-1)*$pas), $rheight+(($data[$i-1]/$maxY)*($my-$rheight)), 3);
			$img->endFill();

			$img->render();
            Core::endApplication();
		}

		static public function sample()
		{
			$i = new Image(200, 200, Image::PNG, 2);
			/**
			 * Repere
			 */
			$i->setLineStyle(255,0,0,2);
			$i->moveTo(0,100);
			$i->lineTo(200,100);
			$i->moveTo(100,0);
			$i->lineTo(100,200);
			/**
			 * Carre
			 */
			$i->setLineStyle(127,127,127,2);
			$i->beginFill(255,255,255);
			$i->drawRectangle(50,50,100,100);
			$i->endFill();
			/**
			 * Cercle
			 */
			$i->setLineStyle(0,0,0,0);
			$i->beginFill(0,0,255);
			$i->drawCircle(50, 50, 10);
			$i->endFill();
			/**
			 * texte
			 */
			$i->drawText("x", 10, "files/visitor2.ttf", 0,97);
			$i->drawText("y", 10, "files/visitor2.ttf", 87,10);
			$i->drawText("class.Image.php", 10, "files/arial.ttf", 65,140,45,45,45,45);
			$i->render();
		}

		static public function debugger()
		{
			trace("premiere action trace");

			trigger_error("Une notice trigger par le code", E_USER_NOTICE);

			for($i = 0;$i<20;$i++)
			{
				trigger_error("Un warning trigger &agrave; chaque itération", E_USER_WARNING);
				if($i%3 == 0 )
					trace("i : ".$i);
			}

			trace("derni&egrave;re action trace");

			Query::select("*", "ma_table")
				->join("mon_autre_table")
				->andWhere("un_champ", Query::EQUAL, "une valeur")
				->limit(0, 1)
				->groupBy("some_id")
				->execute();
		}
	}
}
