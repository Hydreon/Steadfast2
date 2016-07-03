<?php

namespace pocketmine\entity\animal\walking;

use pocketmine\entity\animal\WalkingAnimal;
use pocketmine\item\Item;
use pocketmine\Player;
use pocketmine\entity\Creature;

class Rabbit extends WalkingAnimal{
	const NETWORK_ID = 18;

	public $width = 0.4;
	public $height = 0.75;

	public function getSpeed(){
		return 1.2;
	}
	
	public function getName(){
		return "Rabbit";
	}

	public function initEntity(){
		parent::initEntity();

		$this->setMaxHealth(3);
	}

	public function targetOption(Creature $creature, float $distance){
		if($creature instanceof Player){
			return $creature->spawned && $creature->isAlive() && !$creature->closed && $creature->getInventory()->getItemInHand()->getId() == Item::SEEDS && $distance <= 49;
		}
		return false;
	}

	public function getDrops(){
		return [];
	}

}