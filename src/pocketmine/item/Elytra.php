<?php

namespace pocketmine\item;

class Elytra extends Armor {

	const SLOT_NUMBER = 1;

	public function __construct($meta = 0, $count = 1) {
		parent::__construct(self::ELYTRA, $meta, $count, "Elytra");
	}

}
