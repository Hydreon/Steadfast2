<?php
namespace pocketmine\block;

class RedstoneTorch extends Torch{
	
	protected $id = self::REDSTONE_TORCH;
	
	public function __construct($meta = 0){
		$this->meta = $meta;
	}
	
	public function getName(){
		return "Redstone Torch";
	}
	
}