<?php

namespace pocketmine\entity\effects;

use pocketmine\entity\Effect;
use pocketmine\entity\Entity;
use pocketmine\Player;

class SlownessEffect extends Effect {

	public function add(Entity $entity, $modify = false) {
		parent::add($entity, $modify);
		if ($entity instanceof Player) {
			$newSpeedValue = $entity::DEFAULT_SPEED * (1 - ($this->amplifier + 1) * 0.15);
			$entity->updateSpeed($newSpeedValue);
		}
	}

	public function remove(Entity $entity) {
		parent::remove($entity);
		if ($entity instanceof Player) {
			$entity->updateSpeed($entity::DEFAULT_SPEED);
		}
	}

}
