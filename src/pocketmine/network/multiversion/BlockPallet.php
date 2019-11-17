<?php

namespace pocketmine\network\multiversion;

use pocketmine\utils\BinaryStream;
use pocketmine\network\protocol\Info;
use pocketmine\nbt\tag\Compound;
use pocketmine\nbt\tag\Enum;
use pocketmine\nbt\tag\ShortTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\nbt\NBT;

class BlockPallet {
	
	public static function initAll() {
		$result = [];
		$folderPath = __DIR__ . "/data/";
		$palletFiles = array_diff(scandir($folderPath), ['..', '.']);
		foreach ($palletFiles as $fileName) {
			$parts = explode(".", $fileName);
			$protocolNumber = (int) substr($parts[0], 11);
			$pallet = new BlockPallet($folderPath . $fileName, $protocolNumber);
			$result[$protocolNumber] = $pallet;
		}
		krsort($result);
		return $result;
	}
	
	private $pallet = [];
	private $palletReverted = [];
	private $dataForPackets = "";

	public function __construct($path, $protocolNumber) {
		$palletData = json_decode(file_get_contents($path), true);
		if ($protocolNumber >= Info::PROTOCOL_370) {
			$palletTag =  new Enum("", []);
			foreach ($palletData as $runtimeID => $blockInfo) {
				if (isset($blockInfo['data'])) {
					$this->pallet[$blockInfo['id']][$blockInfo['data']] = $runtimeID;
					$this->palletReverted[$runtimeID] = [$blockInfo['id'], $blockInfo['data'], $blockInfo['name']];
				}
				$states = new Compound("states", []);
				foreach ($blockInfo['states'] as $stateName => $state) {
					switch ($state['type']) {
						case NBT::TAG_Byte:
							$states->{$stateName} = new ByteTag($stateName, $state['val']);
							break;
						case NBT::TAG_Int:
							$states->{$stateName} = new IntTag($stateName, $state['val']);
							break;
						case NBT::TAG_String:
							$states->{$stateName} = new StringTag($stateName, $state['val']);
							break;
						default:
							var_dump("Block Pallet Initialization Error");
							break;
					}					
				}
				$nbt = new Compound("", [
					"block" => new Compound("block", [
						"name" => new StringTag("name", $blockInfo['name']),						
						"states" => $states,
						"version" => new IntTag("version", 17629199)
					]),
					"id" => new ShortTag("id", $blockInfo['id'])
				]);
				$palletTag->{$runtimeID} = $nbt;
			}
			$nbt = new NBT();
			$nbt->writeTag($palletTag);
			$this->dataForPackets = $nbt->buffer;
		} else {
			$bs = new BinaryStream();
			$bs->putVarInt(count($palletData));
			foreach ($palletData as $runtimeID => $blockInfo) {
				if (isset($blockInfo['runtimeID'])) {
					$this->pallet[$blockInfo['id']][$blockInfo['data']] = $blockInfo['runtimeID'];
					$this->palletReverted[$blockInfo['runtimeID']] = [$blockInfo['id'], $blockInfo['data'], $blockInfo['name']];
				} else {
					$this->pallet[$blockInfo['id']][$blockInfo['data']] = $runtimeID;
					$this->palletReverted[$runtimeID] = [$blockInfo['id'], $blockInfo['data'], $blockInfo['name']];
				}
				$bs->putString($blockInfo['name']);
				$bs->putLShort($blockInfo['data']);
				if ($protocolNumber >= Info::PROTOCOL_360) {
					$bs->putLShort($blockInfo['id']);
				}
			}
			$this->dataForPackets = $bs->getBuffer();
		}
	}
	
	public function getBlockDataByRuntimeID($runtimeID) {
		if (isset($this->palletReverted[$runtimeID])) {
			return $this->palletReverted[$runtimeID];
		}
		return [0, 0, ""];
	}
	
	public function getBlockRuntimeIDByData($id, $meta) {
		if (isset($this->pallet[$id][$meta])) {
			return $this->pallet[$id][$meta];
		}
		return 0;
	}
	
	public function getDataForPackets() {
		return $this->dataForPackets;
	}
	
}
