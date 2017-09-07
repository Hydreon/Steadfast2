<?php

namespace pocketmine\block;

use pocketmine\item\Item;
use pocketmine\Player;
use pocketmine\math\Vector3;

class RedstoneTorchActive extends RedstoneTorch {
	
	protected $id = self::REDSTONE_TORCH_ACTIVE;
	
	public function __construct($meta = 0){
		$this->meta = $meta;
	}
	
	public function getName(){
		return "Glowing Redstone Torch";
	}
	
	public function getLightLevel() {
		return 7;
	}

	public function place(Item $item, Block $block, Block $target, $face, $fx, $fy, $fz, Player $player = null) {
		$result = parent::place($item, $block, $target, $face, $fx, $fy, $fz, $player);
		if (!$result) {
			return false;
		}
		$this->redstoneUpdate();
		return true;
	}
	
	protected function redstoneUpdate($power = 0) {
		foreach ($this->neighbors as $neighbor) {
			if (in_array($neighbor->getId(), self::REDSTONE_BLOCKS)) {
				$neighbor->redstoneUpdate(self::REDSTONE_POWER_MAX);
			} else if ($neighbor->isSolid()) {
				static $offsets = [
					self::DIRECTION_TOP => [0, 1, 0],
					self::DIRECTION_NORTH => [1, 0, 0],
					self::DIRECTION_SOUTH => [-1, 0, 0],
					self::DIRECTION_EAST => [0, 0, 1],
					self::DIRECTION_WEST => [0, 0, -1],
				];
				foreach ($offsets as $direction => $offset) {
					$blockId = $this->level->getBlockIdAt($this->x + $offset[0], $this->y + $offset[1], $this->z + $offset[2]);
					switch ($direction) {
						case self::DIRECTION_TOP:
							if (in_array($blockId, self::REDSTONE_BLOCKS)) {
								$block = $this->level->getBlock(new Vector3($this->x + $offset[0], $this->y + $offset[1], $this->z + $offset[2]));
								$block->redstoneUpdate(self::REDSTONE_POWER_MAX);
							}
							break;
						default:
							if ($blockId === self::REDSTONE_WIRE) {
								$block = $this->level->getBlock(new Vector3($this->x + $offset[0], $this->y + $offset[1], $this->z + $offset[2]));
								$neighbor->redstoneUpdate(self::REDSTONE_POWER_MAX);
							}
							break;
					}
				}
			}
		}
	}
	
}