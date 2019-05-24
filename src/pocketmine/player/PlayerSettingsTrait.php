<?php

namespace pocketmine\player;

use pocketmine\network\protocol\AdventureSettingsPacket;

trait PlayerSettingsTrait {

	private $canBreakBlocks = true;
	private $canBuildBlocks = true;
	private $canAttackPlayers = true;
	private $canAttackMobs = true;
	private $canOpenContainers = true; // not integrated yet
	private $canUseDoorsAndSwitches = true; // not integrated yet

	public function canBreakBlocks() {
		return $this->canBreakBlocks;
	}

	public function canBuildBlocks() {
		return $this->canBuildBlocks;
	}

	public function canAttackPlayers() {
		return $this->canAttackPlayers;
	}

	public function canAttackMobs() {
		return $this->canAttackMobs;
	}

	public function canOpenContainers() {
		return $this->canOpenContainers;
	}

	public function canUserDoorsAndSwitches() {
		return $this->canUseDoorsAndSwitches;
	}

	public function setCanBreakBlocks($value) {
		$this->canBreakBlocks = (bool) $value;
	}

	public function setCanBuildBlocks($value) {
		$this->canBuildBlocks = (bool) $value;
	}

	public function setCanAttackPlayers($value) {
		$this->canAttackPlayers = (bool) $value;
	}

	public function setCanAttackMobs($value) {
		$this->canAttackMobs = (bool) $value;
	}

	public function setCanOpenContainers($value) {
		$this->canOpenContainers = (bool) $value;
	}

	public function setCanUserDoorsAndSwitches($value) {
		$this->canUseDoorsAndSwitches = (bool) $value;
	}

	public function getActionFlags() {
		$flags = 0;
		if ($this->canBreakBlocks()) {
			$flags |= AdventureSettingsPacket::ACTION_FLAG_MINE;
		}
		if ($this->canBuildBlocks()) {
			$flags |= AdventureSettingsPacket::ACTION_FLAG_BUILD;
		}
		if ($this->canAttackPlayers()) {
			$flags |= AdventureSettingsPacket::ACTION_FLAG_ATTACK_PLAYERS;
		}
		if ($this->canAttackMobs()) {
			$flags |= AdventureSettingsPacket::ACTION_FLAG_ATTACK_MOBS;
		}
		if ($this->canOpenContainers()) {
			$flags |= AdventureSettingsPacket::ACTION_FLAG_OPEN_CONTAINERS;
		}
		if ($this->canUserDoorsAndSwitches()) {
			$flags |= AdventureSettingsPacket::ACTION_FLAG_DOORS_AND_SWITCHES;
		}
		return $flags;
	}

}