<?php

namespace pocketmine\block;

use pocketmine\item\Tool;

class ConcretePowder extends Solid {

	protected $id = self::CONCRETE_POWDER;

	public function __construct($meta = 0){
		$this->meta = $meta;
	}

	public function getHardness(){
		return 0.5;
	}

	public function getToolType(){
		return Tool::TYPE_SHOVEL;
	}

	public function getName(){
		return $this->getColorNameByMeta($this->meta) . " Concrete Powder";
	}
}