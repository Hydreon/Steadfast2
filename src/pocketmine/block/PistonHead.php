<?php

namespace pocketmine\block;

class PistonHead extends Transparent {

	protected $id = self::PISTON_HEAD;
	
	public function __construct($meta = 0) {
		parent::__construct($this->id, $meta);
	}
	
	public function canBeFlowedInto(){
		return false;
	}
	
}
