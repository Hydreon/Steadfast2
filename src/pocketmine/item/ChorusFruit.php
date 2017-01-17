<?php

namespace pocketmine\item;

class ChorusFruit extends Item {
    
    public function __construct($meta = 0, $count = 1) {
		parent::__construct(self::CHORUS_FRUIT, 0, $count, "Chorus Fruit");
	}
    
}
