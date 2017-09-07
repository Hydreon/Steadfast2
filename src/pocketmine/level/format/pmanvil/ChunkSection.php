<?php

namespace pocketmine\level\format\pmanvil;

use pocketmine\nbt\tag\Compound;

class ChunkSection implements \pocketmine\level\format\ChunkSection {

	private $y;
	private $blocks;
	private $data;
	private $blockLight;
	private $skyLight;

	public function __construct(Compound $nbt) {
		$this->y = (int) $nbt["Y"];
		$this->blocks = (string) $nbt["Blocks"];
		$this->data = (string) $nbt["Data"];
		$this->blockLight = (string) $nbt["BlockLight"];
		$this->skyLight = (string) $nbt["SkyLight"];
	}

	public function getY() {
		return $this->y;
	}

	public function getBlockId($x, $y, $z) {
		return ord($this->blocks{($x << 8) + ($z << 4) + $y});
	}

	public function setBlockId($x, $y, $z, $id) {
		$this->blocks{($x << 8) + ($z << 4) + $y} = chr($id);
	}

	public function getBlockData($x, $y, $z) {
		$m = ord($this->data{($x << 7) + ($z << 3) + ($y >> 1)});
		if (($y & 1) === 0) {
			return $m & 0x0F;
		} else {
			return $m >> 4;
		}
	}

	public function setBlockData($x, $y, $z, $data) {
		$i = ($x << 7) + ($z << 3) + ($y >> 1);
		$old_m = ord($this->data{$i});
		if (($y & 1) === 0) {
			$this->data{$i} = chr(($old_m & 0xf0) | ($data & 0x0f));
		} else {
			$this->data{$i} = chr((($data & 0x0f) << 4) | ($old_m & 0x0f));
		}
	}

	public function getBlock($x, $y, $z, &$blockId, &$meta = null) {
		$full = $this->getFullBlock($x, $y, $z);
		$blockId = $full >> 4;
		$meta = $full & 0x0f;
	}

	public function getFullBlock($x, $y, $z) {
		$i = ($x << 8) + ($z << 4) + $y;
		if (($y & 1) === 0) {
			return (ord($this->blocks{$i}) << 4) | (ord($this->data{$i >> 1}) & 0x0F);
		} else {
			return (ord($this->blocks{$i}) << 4) | (ord($this->data{$i >> 1}) >> 4);
		}
	}

	public function setBlock($x, $y, $z, $blockId = null, $meta = null) {
		$i = ($x << 8) + ($z << 4) + $y;

		$changed = false;

		if ($blockId !== null) {
			$blockId = chr($blockId);
			if ($this->blocks{$i} !== $blockId) {
				$this->blocks{$i} = $blockId;
				$changed = true;
			}
		}

		if ($meta !== null) {
			$i >>= 1;
			$old_m = ord($this->data{$i});
			if (($y & 1) === 0) {
				$this->data{$i} = chr(($old_m & 0xf0) | ($meta & 0x0f));
				if (($old_m & 0x0f) !== $meta) {
					$changed = true;
				}
			} else {
				$this->data{$i} = chr((($meta & 0x0f) << 4) | ($old_m & 0x0f));
				if ((($old_m & 0xf0) >> 4) !== $meta) {
					$changed = true;
				}
			}
		}

		return $changed;
	}

	public function getBlockSkyLight($x, $y, $z) {
		$sl = ord($this->skyLight{($x << 7) + ($z << 3) + ($y >> 1)});
		if (($y & 1) === 0) {
			return $sl & 0x0F;
		} else {
			return $sl >> 4;
		}
	}

	public function setBlockSkyLight($x, $y, $z, $level) {
		$i = ($x << 7) + ($z << 3) + ($y >> 1);
		$old_sl = ord($this->skyLight{$i});
		if (($y & 1) === 0) {
			$this->skyLight{$i} = chr(($old_sl & 0xf0) | ($level & 0x0f));
		} else {
			$this->skyLight{$i} = chr((($level & 0x0f) << 4) | ($old_sl & 0x0f));
		}
	}

	public function getBlockLight($x, $y, $z) {
		$l = ord($this->blockLight{($x << 7) + ($z << 3) + ($y >> 1)});
		if (($y & 1) === 0) {
			return $l & 0x0F;
		} else {
			return $l >> 4;
		}
	}

	public function setBlockLight($x, $y, $z, $level) {
		$i = ($x << 7) + ($z << 3) + ($y >> 1);
		$old_l = ord($this->blockLight{$i});
		if (($y & 1) === 0) {
			$this->blockLight{$i} = chr(($old_l & 0xf0) | ($level & 0x0f));
		} else {
			$this->blockLight{$i} = chr((($level & 0x0f) << 4) | ($old_l & 0x0f));
		}
	}

	public function getBlockIdColumn($x, $z) {
		return substr($this->blocks, ($x << 8) | ($z << 4), 16);
	}

	public function getBlockDataColumn($x, $z) {
		return substr($this->data, ($x << 7) | ($z << 3), 8);
	}

	public function getBlockSkyLightColumn($x, $z) {
		return substr($this->skyLight, ($x << 7) | ($z << 3), 8);
	}

	public function getBlockLightColumn($x, $z) {
		return substr($this->blockLight, ($x << 7) | ($z << 3), 8);
	}

	public function getIdArray() {
		return $this->blocks;
	}

	public function getDataArray() {
		return $this->data;
	}

	public function getSkyLightArray() {
		return $this->skyLight;
	}

	public function getLightArray() {
		return $this->blockLight;
	}

}
