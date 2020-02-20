<?php

namespace pocketmine\block;

use pocketmine\item\Tool;

class MagmaBlock extends Solid {

	protected $id = self::MAGMA;

	public function __construct() {
		
	}

	public function getHardness() {
		return 0.5;
	}

	public function getName() {
		return "Magma Block";
	}

	public function getToolType() {
		return Tool::TYPE_PICKAXE;
	}

}
