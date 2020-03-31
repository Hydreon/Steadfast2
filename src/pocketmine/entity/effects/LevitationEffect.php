<?php

namespace pocketmine\entity\effects;

use pocketmine\entity\Effect;
use pocketmine\entity\Entity;
use pocketmine\math\Vector3;
use pocketmine\Player;

class LevitationEffect extends Effect {

	public function canTick() {
		return true;
	}

	public function applyEffect(Entity $entity) {
		// $y = 0.0045 * $this->amplifier;
		$y = 0.5;
		$entity->setMotion(new Vector3(0, $y, 0));
	}

	public function add(Entity $entity, $modify = false) {
		if ($entity instanceof Player) {
			$entity->setAllowFlight(true);
		}
		parent::add($entity, $modify);
	}

	public function remove(Entity $entity) {
		if ($entity instanceof Player) {
			$entity->setAllowFlight(false);
		}
		parent::remove($entity);
	}

}
