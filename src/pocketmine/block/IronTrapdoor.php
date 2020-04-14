<?php
namespace pocketmine\block;

use pocketmine\item\Tool;

class IronTrapdoor extends Trapdoor{
	
	protected $id = self::IRON_TRAPDOOR;

	protected $newMaskOpened = 0x0d;
	
	public function getName(){
		return "Iron Trapdoor";
	}
	
	public function getHardness(){
		return 5;
	}
	
	public function getToolType(){
		return Tool::TYPE_PICKAXE;
	}
	
}