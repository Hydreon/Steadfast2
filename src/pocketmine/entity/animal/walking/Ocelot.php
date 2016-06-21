<?php

namespace pocketmine\entity\animal\walking;

use pocketmine\entity\animal\WalkingAnimal;
use pocketmine\Player;
use pocketmine\item\Item;
use pocketmine\entity\Creature;

class Ocelot extends WalkingAnimal{
	const NETWORK_ID = 22;

	public $width = 0.72;
	public $height = 0.9;

	public function getSpeed(){
		return 1.4;
	}

	public function initEntity(){
		parent::initEntity();

		$this->setMaxHealth(10);
	}

	public function getName(){
		return "Ocelot";
	}

	public function targetOption(Creature $creature, float $distance){
		if($creature instanceof Player){
			return $creature->spawned && $creature->isAlive() && !$creature->closed && $creature->getInventory()->getItemInHand()->getId() == Item::RAW_FISH && $distance <= 49;
		}
		return false;
	}

	public function getDrops(){
		return [];
	}
}
