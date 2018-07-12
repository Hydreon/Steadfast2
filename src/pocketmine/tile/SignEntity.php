<?php

namespace pocketmine\tile;

use pocketmine\entity\Entity;
use pocketmine\math\AxisAlignedBB;
use pocketmine\level\Level;
use pocketmine\Player;
use pocketmine\network\protocol\AddEntityPacket;
use pocketmine\network\protocol\RemoveEntityPacket;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\item\Item;
use pocketmine\network\protocol\Info;

class SignEntity extends Entity {

	protected $metadata;
	protected $sign;

	public function __construct($sign, $level, $x, $y, $z) {
		$this->id = Entity::$entityCount++;
		$this->sign = $sign;
		$this->chunk = $level->getChunk($x >> 4, $z >> 4);
		$this->setLevel($level);
		$this->server = $level->getServer();
		$this->server->addSpawnedEntity($this);
		$this->x = $x + 0.5;
		$this->y = $y + 0.25;
		$this->z = $z + 0.5;
		$this->boundingBox = new AxisAlignedBB(0, 0, 0, 0, 0, 0);
		$this->chunk->addEntity($this);
		$this->level->addEntity($this);
		$this->metadata = [
			self::DATA_FLAGS => [self::DATA_TYPE_LONG, (1 << self::DATA_FLAG_INVISIBLE) /* | (1 << self::DATA_FLAG_ONFIRE) */],
			self::DATA_HEIGHT => [self::DATA_TYPE_FLOAT, 0.4],
		];
		$this->scheduleUpdate();
	}

	public function spawnTo(Player $player) {
		if (!isset($this->hasSpawned[$player->getId()]) && isset($player->usedChunks[Level::chunkHash($this->chunk->getX(), $this->chunk->getZ())])) {
			$this->hasSpawned[$player->getId()] = $player;
			if ($player->getOriginalProtocol() != Info::PROTOCOL_260) {
				return;
			}
			$pk = new AddEntityPacket();
			$pk->eid = $this->getID();
			$pk->type = 37;
			$pk->x = $this->x;
			$pk->y = $this->y;
			$pk->z = $this->z;
			$pk->speedX = 0;
			$pk->speedY = 0;
			$pk->speedZ = 0;
			$pk->yaw = 0;
			$pk->pitch = 0;
			$pk->metadata = $this->metadata;
			$player->dataPacket($pk);
		}
	}

	public function despawnFrom(Player $player) {
		if (isset($this->hasSpawned[$player->getId()])) {
			unset($this->hasSpawned[$player->getId()]);
			if ($player->getOriginalProtocol() != Info::PROTOCOL_260) {
				return;
			}
			$pk = new RemoveEntityPacket();
			$pk->eid = $this->getId();
			$player->dataPacket($pk);
		}
	}

	public function onUpdate($currentTick) {
		return false;
	}

	public function attack($damage, EntityDamageEvent $source) {
		if ($source instanceof EntityDamageByEntityEvent) {
			$player = $source->getDamager();
			if ($player instanceof Player) {
				$item = Item::get(Item::AIR);
				$this->level->useItemOn($this->sign, $item, 0, 0, 0, 0, $player);
			}
		}
	}

	public function isNeedSaveOnChunkUnload() {
		return false;
	}

	public function interact($player) {
		$item = Item::get(Item::AIR);
		$this->level->useItemOn($this->sign, $item, 0, 0, 0, 0, $player);
	}
	
}
