<?php

namespace pocketmine\item;

class BlazePowder extends Item {

	public function __construct($meta = 0, $count = 1) {
		parent::__construct(self::BLAZE_POWDER, $meta, $count, self::$names[self::BLAZE_POWDER]);
	}

}
