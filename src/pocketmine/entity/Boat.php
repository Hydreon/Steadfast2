<?php

namespace pocketmine\entity;
use pocketmine\level\format\FullChunk;
use pocketmine\nbt\tag\Compound;
use pocketmine\math\Vector3;

class Boat extends Vehicle {

	const NETWORK_ID = 90;

	public $height = 0.7;
	public $width = 1.6;
	protected $riderOffset = [0, 0.6, 0];
	protected $afterMovement = false;
	protected $interactText = "Board";
	
	public function __construct(FullChunk $chunk, Compound $nbt) {
		parent::__construct($chunk, $nbt);
		$this->setDataFlag(self::DATA_FLAGS, self::DATA_FLAG_HAS_COLLISION, true, self::DATA_TYPE_LONG, false);
		$this->setDataFlag(self::DATA_FLAGS, self::DATA_FLAG_AFFECTED_BY_GRAVITY, true, self::DATA_TYPE_LONG, false);
	}
	
	public function initEntity() {
		$this->setMaxHealth(10);
		$this->setHealth($this->getMaxHealth());
		parent::initEntity();
	}

	public function getName() {
		return "Boat";
	}

	public function onUpdate($currentTick) {
		if ($this->closed !== false) {
			return false;
		}

		if ($this->dead === true) {
			$this->removeAllEffects();
			$this->despawnFromAll();
			$this->close();
			return false;
		}
		$tickDiff = $currentTick - $this->lastUpdate;
		if ($tickDiff < 1) {
			return true;
		}

		$this->lastUpdate = $currentTick;

		$hasUpdate = false;
		if ($this->afterMovement) {
			$this->afterMovement = false;
			$this->updateMovement();
		}
		return $hasUpdate;
	}
	
	public function updateByOwner($x, $y, $z, $yaw, $pitch) {
		$this->setPositionAndRotation(new Vector3($x, $y, $z), $yaw, $pitch);
		$this->afterMovement = true;
		$this->scheduleUpdate();
	}

}
