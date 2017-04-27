<?php

namespace pocketmine\entity\effects;

use pocketmine\entity\Effect;
use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityRegainHealthEvent;

class RegenerationEffect extends Effect {

	public function canTick() {
		if ($this->amplifier < 0) {
			$this->amplifier = 0;
		}
		$interval = 40 >> $this->amplifier;
		if ($interval > 0) {
			return ($this->duration % $interval) === 0;
		}
		return true;
	}

	public function applyEffect(Entity $entity) {
		if($entity->getHealth() < $entity->getMaxHealth()){
			$ev = new EntityRegainHealthEvent($entity, 1, EntityRegainHealthEvent::CAUSE_MAGIC);
			$entity->heal($ev->getAmount(), $ev);
		}
	}

}
