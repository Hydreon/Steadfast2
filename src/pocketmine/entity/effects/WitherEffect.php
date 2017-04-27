<?php

namespace pocketmine\entity\effects;

use pocketmine\entity\Effect;
use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityDamageEvent;

class WitherEffect extends Effect {

	public function canTick() {
		if ($this->amplifier < 0) {
			$this->amplifier = 0;
		}
		$interval = 50 >> $this->amplifier;
		if ($interval > 0) {
			return ($this->duration % $interval) === 0;
		}
		return true;
	}

	public function applyEffect(Entity $entity) {
		$ev = new EntityDamageEvent($entity, EntityDamageEvent::CAUSE_MAGIC, 1);
		$entity->attack($ev->getFinalDamage(), $ev);
	}
	
}
