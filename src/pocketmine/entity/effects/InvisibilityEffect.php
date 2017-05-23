<?php

namespace pocketmine\entity\effects;

use pocketmine\entity\Effect;
use pocketmine\entity\Entity;

class InvisibilityEffect extends Effect {

	public function add(Entity $entity, $modify = false) {
		parent::add($entity, $modify);
		$entity->setDataFlag(Entity::DATA_FLAGS, Entity::DATA_FLAG_INVISIBLE, true);
		$entity->setDataFlag(Entity::DATA_FLAGS, Entity::DATA_FLAG_SHOW_NAMETAG, false);
	}

	public function remove(Entity $entity) {
		parent::remove($entity);
		$entity->setDataFlag(Entity::DATA_FLAGS, Entity::DATA_FLAG_INVISIBLE, false);
		$entity->setDataFlag(Entity::DATA_FLAGS, Entity::DATA_FLAG_SHOW_NAMETAG, true);
	}

}
