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

use pocketmine\block\Block;
use pocketmine\entity\Entity;
use pocketmine\Server;
use pocketmine\nbt\tag\Compound;
use pocketmine\nbt\tag\IntTag;
use pocketmine\item\enchantment\Enchantment;

abstract class Tool extends Item {

	const TIER_WOODEN = 1;
	const TIER_GOLD = 2;
	const TIER_STONE = 3;
	const TIER_IRON = 4;
	const TIER_DIAMOND = 5;
	const TYPE_NONE = 0;
	const TYPE_SWORD = 1;
	const TYPE_SHOVEL = 2;
	const TYPE_PICKAXE = 3;
	const TYPE_AXE = 4;
	const TYPE_SHEARS = 5;

	public function __construct($id, $meta = 0, $count = 1, $name = "Unknown") {
		parent::__construct($id, $meta, $count, $name);
		$this->checkDamage();
	}

	public function getMaxStackSize() {
		return 1;
	}

	/**
	 * TODO: Move this to each item
	 *
	 * @param Entity|Block $object
	 *
	 * @return bool
	 */
	public function useOn($object) {
		static $isUnbreakable = null;
		if (is_null($isUnbreakable)) {
			$isUnbreakable = Server::getInstance()->getConfigBoolean("unbreakable-tools", false);
		}
		if (!$isUnbreakable) {
			$ench = $this->getEnchantment(Enchantment::TYPE_UNBREAKING);
			if (!is_null($ench)) {
				$enchLevel = $ench->getLevel();
				$chance = 100 / ($enchLevel + 1);
				if (mt_rand(1, 100) > $chance) {
					return true;
				}
			}
			if ($this->isHoe()) {
				if (($object instanceof Block) and ( $object->getId() === self::GRASS or $object->getId() === self::DIRT)) {
					$this->meta++;
					$this->checkDamage();
				}
			} elseif (($object instanceof Entity) and ! $this->isSword()) {
				$this->meta += 2;
				$this->checkDamage();
			} else {
				$this->meta++;
				$this->checkDamage();
			}
		}
		return true;
	}

	/**
	 * TODO: Move this to each item
	 *
	 * @return int|bool
	 */
	public function getMaxDurability() {

		$levels = [
			2 => 33,
			1 => 60,
			3 => 132,
			4 => 251,
			5 => 1562,
			self::FLINT_STEEL => 65,
			self::SHEARS => 239,
			self::BOW => 385,
		];

		if (($type = $this->isPickaxe()) === false) {
			if (($type = $this->isAxe()) === false) {
				if (($type = $this->isSword()) === false) {
					if (($type = $this->isShovel()) === false) {
						if (($type = $this->isHoe()) === false) {
							$type = $this->id;
						}
					}
				}
			}
		}

		return $levels[$type];
	}

	public function isPickaxe() {
		return false;
	}

	public function isAxe() {
		return false;
	}

	public function isSword() {
		return false;
	}

	public function isShovel() {
		return false;
	}

	public function isHoe() {
		return false;
	}

	public function isShears() {
		return ($this->id === self::SHEARS);
	}

	public function isTool() {
		return ($this->id === self::FLINT_STEEL or $this->id === self::SHEARS or $this->id === self::BOW or $this->isPickaxe() !== false or $this->isAxe() !== false or $this->isShovel() !== false or $this->isSword() !== false);
	}
	
	public function setDamage($meta) {
		parent::setDamage($meta);
		$this->checkDamage();
	}

	public function checkDamage() {
		if ($this->meta == 0) {
			if ($this->hasCompound()) {
				$tag = $this->getNamedTag();
				if (isset($tag->Damage)) {
					unset($tag->Damage);
					parent::setCompound($tag);
				}
			}
		} else {
			if (!$this->hasCompound()) {
				$tag = new Compound("", []);
			} else {
				$tag = $this->getNamedTag();
			}
			$tag->Damage = new IntTag("Damage", $this->meta);
			parent::setCompound($tag);
		}
	}
	
	public function setCompound($tags) {
		if($tags instanceof Compound){
			if (isset($tags['Damage'])) {
				$this->meta = $tags['Damage'];
			}
		}
		parent::setCompound($tags);
		$this->checkDamage();
		return $this;
	}

}
