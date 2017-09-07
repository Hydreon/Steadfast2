<?php

namespace pocketmine\entity\monster\walking;

use pocketmine\entity\monster\WalkingMonster;
use pocketmine\entity\Ageable;
use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\Timings;
use pocketmine\item\Item;
use pocketmine\level\Level;

class Zombie extends WalkingMonster implements Ageable{
	const NETWORK_ID = 32;

	public $width = 0.72;
	public $height = 1.8;

	public function getSpeed(){
		return 1.1;
	}

	public function initEntity(){
		parent::initEntity();

		if($this->getDataProperty(self::DATA_AGEABLE_FLAGS) == null){
			$this->setDataProperty(self::DATA_AGEABLE_FLAGS, self::DATA_TYPE_BYTE, 0);
		}
		$this->setDamage([0, 3, 4, 6]);
	}

	public function getName(){
		return "Zombie";
	}

	public function isBaby(){
		return $this->getDataFlag(self::DATA_AGEABLE_FLAGS, self::DATA_FLAG_BABY);
	}

	public function setHealth($amount){
		parent::setHealth($amount);

		if($this->isAlive()){
			if(15 < $this->getHealth()){
				$this->setDamage([0, 2, 3, 4]);
			}else if(10 < $this->getHealth()){
				$this->setDamage([0, 3, 4, 6]);
			}else if(5 < $this->getHealth()){
				$this->setDamage([0, 3, 5, 7]);
			}else{
				$this->setDamage([0, 4, 6, 9]);
			}
		}
	}

	public function attackEntity(Entity $player){
		if($this->attackDelay > 10 && $this->distanceSquared($player) < 2){
			$this->attackDelay = 0;

			$ev = new EntityDamageByEntityEvent($this, $player, EntityDamageEvent::CAUSE_ENTITY_ATTACK, $this->getDamage());
			$player->attack($ev->getFinalDamage(), $ev);
		}
	}

	public function entityBaseTick($tickDiff = 1){
		//Timings::$timerEntityBaseTick->startTiming();

		$hasUpdate = parent::entityBaseTick($tickDiff);

		$time = $this->getLevel()->getTime() % Level::TIME_FULL;
		if(
			!$this->isOnFire()
			&& ($time < Level::TIME_NIGHT || $time > Level::TIME_SUNRISE)
		){
			$this->setOnFire(100);
		}

		//Timings::$timerEntityBaseTick->startTiming();
		return $hasUpdate;
	}

	public function getDrops(){
		$drops = [];
		if($this->lastDamageCause instanceof EntityDamageByEntityEvent){
			switch(mt_rand(0, 2)){
				case 0:
					$drops[] = Item::get(Item::FEATHER, 0, 1);
					break;
				case 1:
					$drops[] = Item::get(Item::CARROT, 0, 1);
					break;
				case 2:
					$drops[] = Item::get(Item::POTATO, 0, 1);
					break;
			}
		}
		return $drops;
	}
}
