<?php

/**
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
 * @link   http://www.pocketmine.net/
 *
 *
 */

namespace pocketmine\event\entity;

use pocketmine\entity\Effect;
use pocketmine\entity\Entity;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\Player;

class EntityDamageByEntityEvent extends EntityDamageEvent{

	/** @var Entity */
	private $damager;
	/** @var float */
	private $knockBack;

	/**
	 * @param Entity    $damager
	 * @param Entity    $entity
	 * @param int       $cause
	 * @param int|int[] $damage
	 * @param float     $knockBack
	 */
	public function __construct(Entity $damager, Entity $entity, $cause, $damage, $knockBack = 0.4){
		$this->damager = $damager;
		$this->knockBack = $knockBack;
		parent::__construct($entity, $cause, $damage);
		$this->addAttackerModifiers($damager);
	}

	protected function addAttackerModifiers(Entity $damager){
		if($damager->hasEffect(Effect::STRENGTH)){
			$this->setDamage($this->getDamage(self::MODIFIER_BASE) * 0.3 * ($damager->getEffect(Effect::STRENGTH)->getAmplifier() + 1), self::MODIFIER_STRENGTH);
		}

		if($damager->hasEffect(Effect::WEAKNESS)){
			$this->setDamage(-($this->getDamage(self::MODIFIER_BASE) * 0.2 * ($damager->getEffect(Effect::WEAKNESS)->getAmplifier() + 1)), self::MODIFIER_WEAKNESS);
		}
		
		if ($damager instanceof Player) {
			$baseDamage = $this->getDamage(self::MODIFIER_BASE);
			$weapon = $damager->getInventory()->getItemInHand();
			$weaponEnchantments = $weapon->getEnchantments();
			if (isset($weaponEnchantments[Enchantment::TYPE_WEAPON_SHARPNESS])) {
				$effect = $weaponEnchantments[Enchantment::TYPE_WEAPON_SHARPNESS];
				$effectLevel = $effect->getLevel();
				if ($effectLevel > 0) {
					$additionalDamage = 1;
					if ($effectLevel > 1) {
						$additionalDamage += ($effectLevel - 1) * 0.25;
					}
					$this->setDamage($additionalDamage, self::MODIFIER_EFFECT_SHARPNESS);
				}
			}
			if (isset($weaponEnchantments[Enchantment::TYPE_WEAPON_SMITE])) {
				/** @todo add check for entity on arthropod */
				$effect = $weaponEnchantments[Enchantment::TYPE_WEAPON_SMITE];
				$additionalDamage = 0;
				$this->setDamage($additionalDamage, self::MODIFIER_EFFECT_SMITE);
			}
			if (isset($weaponEnchantments[Enchantment::TYPE_WEAPON_ARTHROPODS])) {
				/** @todo add check for entity on undead */
				$effect = $weaponEnchantments[Enchantment::TYPE_WEAPON_ARTHROPODS];
				$additionalDamage = 0;
				$this->setDamage($additionalDamage, self::MODIFIER_EFFECT_ARTHROPODOS);
			}
			if (isset($weaponEnchantments[Enchantment::TYPE_WEAPON_KNOCKBACK])) {
				$effect = $weaponEnchantments[Enchantment::TYPE_WEAPON_KNOCKBACK];
				// not sure
				$this->knockBack += $effect->getLevel() * 0.3;
			}
		}
	}

	/**
	 * @return Entity
	 */
	public function getDamager(){
		return $this->damager;
	}
	/**
	 * @return float
	 */
	public function getKnockBack(){
		return $this->knockBack;
	}
	/**
	 * @param float $knockBack
	 */
	public function setKnockBack($knockBack){
		$this->knockBack = $knockBack;
	}
}
