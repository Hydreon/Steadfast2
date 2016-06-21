<?php

namespace pocketmine\entity\animal\walking;

use pocketmine\entity\animal\WalkingAnimal;
use pocketmine\entity\Rideable;
use pocketmine\item\Item;
use pocketmine\Player;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\entity\Creature;

class Pig extends WalkingAnimal implements Rideable{
	const NETWORK_ID = 12;

	public $width = 1.45;
	public $height = 1.12;

	public function getName(){
		return "Pig";
	}

	public function initEntity(){
		parent::initEntity();

		$this->setMaxHealth(10);
	}

	public function targetOption(Creature $creature, float $distance){
		if($creature instanceof Player){
			return $creature->spawned && $creature->isAlive() && !$creature->closed && $creature->getInventory()->getItemInHand()->getId() == Item::CARROT && $distance <= 49;
		}
		return false;
	}

	public function getDrops(){
		if($this->lastDamageCause instanceof EntityDamageByEntityEvent){
			return [Item::get(Item::RAW_PORKCHOP, 0, 1)];
		}
		return [];
	}

}