<?php

namespace pocketmine\block;

use pocketmine\item\Tool;

abstract class GlazedTerracotta extends Solid {
	
	public function __construct($meta = 0) {
		$this->meta = $meta;
	}

	public function getHardness() {
		return 1.4;
	}

	public function getToolType() {
		return Tool::TYPE_PICKAXE;
	}
	
}
