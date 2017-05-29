<?php


namespace pocketmine\item;


class Egg extends Item{
	public function __construct($meta = 0, $count = 1){
		parent::__construct(self::EGG, 0, $count, "Egg");
	}

	public function getMaxStackSize(){
		return 16;
	}

}