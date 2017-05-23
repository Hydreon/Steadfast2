<?php

namespace pocketmine\entity\effects;

use pocketmine\entity\Effect;
use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityDamageEvent;

class PoisonEffect extends Effect {
	
	public function canTick() {
		if ($this->amplifier < 0) {
			$this->amplifier = 0;
		}
		$interval = 25 >> $this->amplifier;
		if ($interval > 0) {
			return ($this->duration % $interval) === 0;
		}
		return true;
	}
	
	public function applyEffect(Entity $entity) {
		if ($entity->getHealth() > 1) {
			$ev = new EntityDamageEvent($entity, EntityDamageEvent::CAUSE_MAGIC, 1);
			$entity->attack($ev->getFinalDamage(), $ev);
		}
	}
	
}
