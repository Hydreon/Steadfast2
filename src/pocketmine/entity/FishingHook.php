<?php
namespace pocketmine\entity;

use pocketmine\Player;
use pocketmine\network\protocol\AddEntityPacket;
use pocketmine\level\Level;
use pocketmine\level\format\FullChunk;
use pocketmine\nbt\tag\Compound;
use pocketmine\math\Vector3;

class FishingHook extends Entity {

	const NETWORK_ID = 77;

	public $width = 0.25;
	public $length = 0.25;
	public $height = 0.25;
	protected $gravity = 0.1;
	protected $drag = 0.05;
	protected $owner = null;

	public function __construct(FullChunk $chunk, Compound $nbt, Player $owner = null) {
		parent::__construct($chunk, $nbt);
		$this->owner = $owner;
		$this->setDataFlag(self::DATA_FLAGS, self::DATA_FLAG_HAS_COLLISION, true, self::DATA_TYPE_LONG, false);
		$this->setDataFlag(self::DATA_FLAGS, self::DATA_FLAG_AFFECTED_BY_GRAVITY, true, self::DATA_TYPE_LONG, false);
		if (!is_null($this->owner)) {
			$this->setDataProperty(self::DATA_OWNER_EID, self::DATA_TYPE_UNSIGNED_LONG, $this->owner->getId(), false);
		}
	}

	public function spawnTo(Player $player) {
		if (!isset($this->hasSpawned[$player->getId()]) && isset($player->usedChunks[Level::chunkHash($this->chunk->getX(), $this->chunk->getZ())])) {
			$this->hasSpawned[$player->getId()] = $player;
			$pk = new AddEntityPacket();
			$pk->eid = $this->getId();
			$pk->type = FishingHook::NETWORK_ID;
			$pk->x = $this->x;
			$pk->y = $this->y;
			$pk->z = $this->z;
			$pk->speedX = $this->motionX;
			$pk->speedY = $this->motionY;
			$pk->speedZ = $this->motionZ;
			$pk->yaw = $this->yaw;
			$pk->pitch = $this->pitch;
//			$pk->metadata = $this->dataProperties;
			$player->dataPacket($pk);
		}
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
		$hasUpdate = $this->entityBaseTick($tickDiff);
		if ($this->isAlive()) {
			if (!$this->onGround) {
				$this->motionY -= $this->gravity;
				$this->move($this->motionX, $this->motionY, $this->motionZ);
				$this->updateMovement();
			}
		}
		return $hasUpdate || $this->motionX != 0 || $this->motionY != 0 || $this->motionZ != 0;
	}

	public function move($dx, $dy, $dz) {
		if ($dx == 0 && $dz == 0 && $dy == 0) {
			return true;
		}
		$this->boundingBox->offset($dx, $dy, $dz);
		$block = $this->level->getBlock(new Vector3($this->x, $this->y + $dy, $this->z));
		if ($dy < 0 && !$block->isTransparent()) {
			$newY = (int) $this->y;
			for ($tempY = (int) $this->y; $tempY > (int) ($this->y + $dy); $tempY--) {
				$block = $this->level->getBlock(new Vector3($this->x, $tempY, $this->z));
				if ($block->isTransparent()) {
					$newY = $tempY;
				}
			}
			$this->onGround = true;
			$this->motionY = 0;
			$this->motionX = 0;
			$this->motionZ = 0;
			$addY = $this->boundingBox->maxY - $this->boundingBox->minY - 1;
			$this->setComponents($this->x + $dx, $newY + $addY, $this->z + $dz);
		} else {
			$this->setComponents($this->x + $dx, $this->y + $dy, $this->z + $dz);
		}
	}

}
