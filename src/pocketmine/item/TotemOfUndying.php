<?php

namespace pocketmine\item;

class TotemOfUndying extends Item {
	
	public function __construct($meta = 0, $count = 1) {
		parent::__construct(self::TOTEM_OF_UNDYING, 0, $count, "Totem of Undying");
	}

	public function getMaxStackSize() {
		return 1;
	}

}