<?php

/*
 *
 *  ____            _        _   __  __ _                  __  __ ____
 * |  _ \ ___   ___| | _____| |_|  \/  (_)_ __   ___      |  \/  |  _ \
 * | |_) / _ \ / __| |/ / _ \ __| |\/| | | '_ \ / _ \_____| |\/| | |_) |
 * |  __/ (_) | (__|   <  __/ |_| |  | | | | | |  __/_____| |  | |  __/
 * |_|   \___/ \___|_|\_\___|\__|_|  |_|_|_| |_|\___|     |_|  |_|_|
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author PocketMine Team
 * @link http://www.pocketmine.net/
 *
 *
*/

namespace pocketmine\item;

use pocketmine\entity\Attribute;
use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityEatItemEvent;
use pocketmine\network\protocol\EntityEventPacket;
use pocketmine\Player;
use pocketmine\Server;

abstract class Food extends Item implements FoodSource{
	public function canBeConsumed() : bool{
		return true;
	}

	public function canBeConsumedBy(Player $player) : bool{
		return $player->getFood() < $player->getAttribute(Attribute::HUNGER)->getMaxValue();
	}

	public function getResidue(){
		if($this->getCount() === 1){
			return Item::get(0);
		}else{
			$new = clone $this;
			$new->count--;
			return $new;
		}
	}

	public function getAdditionalEffects() : array{
		return [];
	}

	public function onConsume(Player $human){
		$pk = new EntityEventPacket();
		$pk->eid = $human->getId();
		$pk->event = EntityEventPacket::USE_ITEM;
		$human->dataPacket($pk);
		Server::broadcastPacket($human->getViewers(), $pk);

		$ev = new EntityEatItemEvent($human, $this);

		$human->getAttribute(Attribute::SATURATION)->addValue($ev->getSaturationRestore());
		$human->setFood($human->getFood() + $ev->getFoodRestore());

		foreach($ev->getAdditionalEffects() as $effect){
			$human->addEffect($effect);
		}

		$human->getInventory()->setItemInHand($ev->getResidue());
	}
}
