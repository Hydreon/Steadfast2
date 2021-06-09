<?php

namespace pocketmine\network\multiversion;

use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\nbt\tag\Compound;
use pocketmine\nbt\tag\Enum;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\ShortTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\network\protocol\Info;
use function file_get_contents;
use function json_decode;
use function strpos;

class BlockPallet {

	public static $blockNamesIds = [];
	
	public static function initAll() {
		$result = [];
		$folderPath = __DIR__ . "/data/";
		self::$blockNamesIds = json_decode(file_get_contents($folderPath . '/block_id_map.json'), true);

		$palletFiles = array_diff(scandir($folderPath), ['..', '.']);
		foreach ($palletFiles as $fileName) {
			if(strpos($fileName, "BlockPallet") !== 0){
				continue;
			}
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
		//for 419 pallet
		$palletData = $palletData['blocks'];
		$palletTag =  new Enum("", []);
		$lastData = null;
		$currentId = null;
		foreach ($palletData as $runtimeID => $blockInfo) {
			if ($protocolNumber >= Info::PROTOCOL_419) {
				$blockInfo['id'] = self::$blockNamesIds[$blockInfo['name']]??-1;
				if ($blockInfo['id'] !== $currentId) {
					$currentId = $blockInfo['id'];
					$lastData = -1;
					$blockInfo['data'] = -1;
				}
				$blockInfo['data'] = ++$lastData;
			}
			if (isset($blockInfo['data'])) {
				$this->pallet[$blockInfo['id']][$blockInfo['data']] = $runtimeID;
				$this->palletReverted[$runtimeID] = [$blockInfo['id'], $blockInfo['data'], $blockInfo['name']];
			}
			$states = new Compound("states", []);
			foreach ($blockInfo['states'] as $stateName => $state) {
				switch ($state['type']) {
					case NBT::TAG_Byte:
					case 'byte':
						$states->{$stateName} = new ByteTag($stateName, $state['val']??$state['value']);
						break;
					case 'int';
					case NBT::TAG_Int:
						$states->{$stateName} = new IntTag($stateName, $state['val']??$state['value']);
						break;
					case 'string':
					case NBT::TAG_String:
						$states->{$stateName} = new StringTag($stateName, $state['val']??$state['value']);
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
