<?php

namespace pocketmine\block;

use pocketmine\item\Item;
use pocketmine\item\Tool;
use pocketmine\math\AxisAlignedBB;
use pocketmine\Player;

class Slab2 extends Transparent{
	
	const RED_SANDSTONE = 0;
	const PURPUR = 1;
	const PRISMARINE = 2;
	const PRISMARINE_BRICK = 3;
	const DARK_PRISMARINE = 4;
	const MOSSY_COBBLESTONE = 5;
	const SMOOTH_SANDSTONE = 6;
	const RED_NETHER_BRICK = 7;
	
	protected $id = self::STONE_SLAB2;

	public function __construct($meta = 0){
		$this->meta = $meta;
	}

	public function getHardness(){
		return 2;
	}

	public function getName(){
		static $names = [
			0 => "Red Sandstone",
			1 => "Purpur",
			2 => "Prismarine",
			3 => "Prismarine Bricks",
			4 => "Dark Prismarine",
			5 => "Mossy Cobblestone",
			6 => "Smooth Sandstone",
			7 => "Red Nether Brick",
		];
		return (($this->meta & 0x08) > 0 ? "Upper " : "") . $names[$this->meta & 0x07] . " Slab";
	}

	protected function recalculateBoundingBox(){

		if(($this->meta & 0x08) > 0){
			return new AxisAlignedBB(
				$this->x,
				$this->y + 0.5,
				$this->z,
				$this->x + 1,
				$this->y + 1,
				$this->z + 1
			);
		}else{
			return new AxisAlignedBB(
				$this->x,
				$this->y,
				$this->z,
				$this->x + 1,
				$this->y + 0.5,
				$this->z + 1
			);
		}
	}

	public function place(Item $item, Block $block, Block $target, $face, $fx, $fy, $fz, Player $player = null){
		$this->meta &= 0x07;
		if($face === 0){
			if($target->getId() === self::STONE_SLAB2 and ($target->getDamage() & 0x08) === 0x08 and ($target->getDamage() & 0x07) === ($this->meta & 0x07)){
				$this->getLevel()->setBlock($target, Block::get(Item::DOUBLE_STONE_SLAB2, $this->meta), true);

				return true;
			}elseif($block->getId() === self::STONE_SLAB2 and ($block->getDamage() & 0x07) === ($this->meta & 0x07)){
				$this->getLevel()->setBlock($block, Block::get(Item::DOUBLE_STONE_SLAB2, $this->meta), true);

				return true;
			}else{
				$this->meta |= 0x08;
			}
		}elseif($face === 1){
			if($target->getId() === self::STONE_SLAB2 and ($target->getDamage() & 0x08) === 0 and ($target->getDamage() & 0x07) === ($this->meta & 0x07)){
				$this->getLevel()->setBlock($target, Block::get(Item::DOUBLE_STONE_SLAB2, $this->meta), true);

				return true;
			}elseif($block->getId() === self::STONE_SLAB2 and ($block->getDamage() & 0x07) === ($this->meta & 0x07)){
				$this->getLevel()->setBlock($block, Block::get(Item::DOUBLE_STONE_SLAB2, $this->meta), true);

				return true;
			}
			//TODO: check for collision
		}else{
			if($block->getId() === self::STONE_SLAB2){
				if(($block->getDamage() & 0x07) === ($this->meta & 0x07)){
					$this->getLevel()->setBlock($block, Block::get(Item::DOUBLE_STONE_SLAB2, $this->meta), true);

					return true;
				}

				return false;
			}else{
				if($fy > 0.5){
					$this->meta |= 0x08;
				}
			}
		}

		if($block->getId() === self::STONE_SLAB2 and ($target->getDamage() & 0x07) !== ($this->meta & 0x07)){
			return false;
		}
		$this->getLevel()->setBlock($block, $this, true, true);

		return true;
	}

	public function getDrops(Item $item){
		if($item->isPickaxe() >= 1){
			return [
				[$this->id, $this->meta & 0x07, 1],
			];
		}else{
			return [];
		}
	}



	public function getToolType(){
		return Tool::TYPE_PICKAXE;
	}
}