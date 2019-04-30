<?php

namespace pocketmine\entity;

use pocketmine\entity\Entity;
use pocketmine\level\format\FullChunk;
use pocketmine\level\Level;
use pocketmine\nbt\tag\Compound;
use pocketmine\network\multiversion\Entity as Multiversion;
use pocketmine\network\protocol\AddPaintingPacket;
use pocketmine\Player;
use pocketmine\network\protocol\Info;

class Painting extends Entity {

	const NETWORK_ID = Multiversion::ID_PAINTING;
	
	const COORD_TYPE_1 = 1;
	const COORD_TYPE_2 = 2;

	/** @var string */
	protected $motive = "";
	/** @var integer */
	private $direction = 0;
	private $coords = [
		self::COORD_TYPE_1 => ['x' => 0, 'y' => 0, 'z' => 0],
		self::COORD_TYPE_2 => ['x' => 0, 'y' => 0, 'z' => 0]
	];

	public function __construct(FullChunk $chunk, Compound $nbt) {
		if (isset($nbt->Facing)) {
			$this->direction = $nbt->Facing->getValue();
		}
		if (isset($nbt->Motive)) {
			$this->motive = $nbt->Motive->getValue();
		}
		if (isset($nbt->TileX)) {
			$x = $nbt->TileX->getValue();
			$this->coords[self::COORD_TYPE_1]['x'] = $x;
			$this->coords[self::COORD_TYPE_2]['x'] = $x;
		}
		if (isset($nbt->TileY)) {
			$y = $nbt->TileY->getValue();
			$this->coords[self::COORD_TYPE_1]['y'] = $y;
			$this->coords[self::COORD_TYPE_2]['y'] = $y + 1;
		}
		if (isset($nbt->TileZ)) {
			$z = $nbt->TileZ->getValue();
			$this->coords[self::COORD_TYPE_1]['z'] = $z;
			$this->coords[self::COORD_TYPE_2]['z'] = $z;
		}
		parent::__construct($chunk, $nbt);
		$this->fireTicks = 0;
		switch($this->direction) {
			case 0:
				$this->coords[self::COORD_TYPE_2]['x'] += 1;
				$this->coords[self::COORD_TYPE_2]['z'] += 0.05;
				$this->coords[self::COORD_TYPE_1]['z'] -= 1;
				break;
			case 1:
				$this->coords[self::COORD_TYPE_2]['x'] += 0.95;
				$this->coords[self::COORD_TYPE_2]['z'] += 1;
				$this->coords[self::COORD_TYPE_1]['x'] += 1;
				break;
			case 2:
				$this->coords[self::COORD_TYPE_2]['z'] += 0.95;
				$this->coords[self::COORD_TYPE_1]['z'] += 1;
				break;
			case 3:
				$this->coords[self::COORD_TYPE_2]['x'] += 0.05;
				$this->coords[self::COORD_TYPE_1]['x'] -= 1;
				break;
		}
	}

	public function spawnTo(Player $player) {
		if (!isset($this->hasSpawned[$player->getId()]) && isset($player->usedChunks[Level::chunkHash($this->chunk->getX(), $this->chunk->getZ())])) {
			$this->hasSpawned[$player->getId()] = $player;
			$pk = new AddPaintingPacket();
			$pk->eid = $this->getId();
			if ($player->getPlayerProtocol() >= Info::PROTOCOL_360) {
				$type = self::COORD_TYPE_2;
			} else {
				$type = self::COORD_TYPE_1;
			}
			$pk->x = $this->coords[$type]['x'];
			$pk->y = $this->coords[$type]['y'];
			$pk->z = $this->coords[$type]['z'];
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
