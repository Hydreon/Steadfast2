<?php

namespace pocketmine\entity;

use pocketmine\level\format\FullChunk;
use pocketmine\level\Level;
use pocketmine\nbt\tag\Compound;
use pocketmine\network\multiversion\Entity as Multiversion;
use pocketmine\network\protocol\AddPaintingPacket;
use pocketmine\Player;

class Painting extends Entity {

	const NETWORK_ID = Multiversion::ID_PAINTING;

	/** @var string */
	protected $motive = "";
	/** @var integer */
	private $direction = 0;
	private $coords = ['x' => 0, 'y' => 0, 'z' => 0];

	public function __construct(FullChunk $chunk, Compound $nbt) {
		if (isset($nbt->Facing)) {
			$this->direction = $nbt->Facing->getValue();
		}
		if (isset($nbt->Motive)) {
			$this->motive = $nbt->Motive->getValue();
		}
		if (isset($nbt->TileX)) {
			$x = $nbt->TileX->getValue();
			$this->coords['x'] = $x;
		}
		if (isset($nbt->TileY)) {
			$y = $nbt->TileY->getValue();
			$this->coords['y'] = $y + 1;
		}
		if (isset($nbt->TileZ)) {
			$z = $nbt->TileZ->getValue();
			$this->coords['z'] = $z;
		}
		parent::__construct($chunk, $nbt);
		$this->fireTicks = 0;
		switch($this->direction) {
			case 0:
				$this->coords['x'] += 1;
				$this->coords['z'] += 0.05;
				break;
			case 1:
				$this->coords['x'] += 0.95;
				$this->coords['z'] += 1;
				break;
			case 2:
				$this->coords['z'] += 0.95;
				break;
			case 3:
				$this->coords['x'] += 0.05;
				break;
		}
	}

	public function spawnTo(Player $player) {
		if (!isset($this->hasSpawned[$player->getId()]) && isset($player->usedChunks[Level::chunkHash($this->chunk->getX(), $this->chunk->getZ())])) {
			$this->hasSpawned[$player->getId()] = $player;
			$pk = new AddPaintingPacket();
			$pk->eid = $this->getId();
			$pk->x = $this->coords['x'];
			$pk->y = $this->coords['y'];
			$pk->z = $this->coords['z'];
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
