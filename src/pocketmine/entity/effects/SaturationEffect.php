<?php

namespace pocketmine\entity\effects;

use pocketmine\entity\Effect;
use pocketmine\entity\Entity;
use pocketmine\Player;

class SaturationEffect extends Effect {

	public function canTick() {
		if ($this->amplifier < 0) {
			$this->amplifier = 0;
		}
		$interval = 20 >> $this->amplifier;
		if ($interval > 0) {
			return ($this->duration % $interval) === 0;
		}
		return true;
	}

	public function applyEffect(Entity $entity) {
		if ($entity instanceof Player && $entity->getServer()->foodEnabled) {
			$entity->setFood($entity->getFood() + 1);
		}
	}

}
