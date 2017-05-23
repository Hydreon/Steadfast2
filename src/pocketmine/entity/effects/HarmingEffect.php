<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace pocketmine\entity\effects;

use pocketmine\entity\InstantEffect;
use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityDamageEvent;

class HarmingEffect extends InstantEffect {
	
	public function canTick() {
		return true;
	}

	public function applyEffect(Entity $entity) {
		$level = $this->amplifier + 1;
		$ev = new EntityDamageEvent($entity, EntityDamageEvent::CAUSE_MAGIC, min([$entity->getHealth(), 6 * $level]));
		$entity->attack($ev->getFinalDamage(), $ev);
	}
	
}
