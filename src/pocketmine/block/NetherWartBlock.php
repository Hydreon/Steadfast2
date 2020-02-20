<?php

namespace pocketmine\block;

class NetherWartBlock extends Solid {

	protected $id = self::NETHER_WART_BLOCK_BLOCK;

	public function __construct() {
		
	}

	public function getHardness() {
		return 1;
	}

	public function getName() {
		return "Nether Wart Block";
	}

}
