<?php

namespace pocketmine\block\redstoneBehavior;

abstract class FlowableRedstoneComponent extends TransparentRedstoneComponent {
	
	public function canBeFlowedInto(){
		return true;
	}

	public function getHardness(){
		return 0;
	}

	public function getResistance(){
		return 0;
	}

	public function isSolid(){
		return false;
	}

	public function getBoundingBox(){
		return null;
	}
	
}
