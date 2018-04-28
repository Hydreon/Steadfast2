<?php

namespace pocketmine\block;

use pocketmine\item\Tool;

class Concrete extends Solid {

	protected $id = self::CONCRETE;

	public function __construct($meta = 0){
		$this->meta = $meta;
	}

	public function getHardness(){
		return 1.8;
	}

	public function getToolType(){
		return Tool::TYPE_PICKAXE;
	}

	public function getName(){
		return $this->getColorNameByMeta($this->meta) . " Concrete";
	}
}