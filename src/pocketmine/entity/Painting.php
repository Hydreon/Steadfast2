<?php

namespace pocketmine\entity;

use pocketmine\entity\Entity;
use pocketmine\level\format\FullChunk;
use pocketmine\level\Level;
use pocketmine\nbt\tag\Compound;
use pocketmine\network\multiversion\Entity as Multiversion;
use pocketmine\network\protocol\AddPaintingPacket;
use pocketmine\Player;

class Painting extends Entity {

	const NETWORK_ID = Multiversion::ID_PAINTING;

	/** @var string */
	private $motive = "";
	/** @var integer */
	private $direction = 0;
	/** @var integer */
	private $tileX = 0;
	/** @var integer */
	private $tileY = 0;
	/** @var integer */
	private $tileZ = 0;

	public function __construct(FullChunk $chunk, Compound $nbt) {
		if (isset($nbt->Facing)) {
			$this->direction = $nbt->Facing->getValue();
		}
		if (isset($nbt->Motive)) {
			$this->motive = $nbt->Motive->getValue();
		}
		if (isset($nbt->TileX)) {
			$this->tileX = $nbt->TileX->getValue();
		}
		if (isset($nbt->TileY)) {
			$this->tileY = $nbt->TileY->getValue();
		}
		if (isset($nbt->TileZ)) {
			$this->tileZ = $nbt->TileZ->getValue();
		}
		parent::__construct($chunk, $nbt);
		$this->fireTicks = 0;
		switch($this->direction) {
			case 0:
				$this->tileZ -= 1;
				break;
			case 1:
				$this->tileX += 1;
				break;
			case 2:
				$this->tileZ += 1;
				break;
			case 3:
				$this->tileX -= 1;
				break;
		}
	}

	public function spawnTo(Player $player) {
		if (!isset($this->hasSpawned[$player->getId()]) && isset($player->usedChunks[Level::chunkHash($this->chunk->getX(), $this->chunk->getZ())])) {
			$this->hasSpawned[$player->getId()] = $player;
			$pk = new AddPaintingPacket();
			$pk->eid = $this->getId();
			$pk->x = $this->tileX;
			$pk->y = $this->tileY;
			$pk->z = $this->tileZ;
			$pk->direction = $this->direction;
			$pk->title = $this->motive;
			$player->dataPacket($pk);
		}
	}

	public function setHealth($amount) {
	}
	
	public function onUpdate($currentTick) {
		return false;
	}

}
