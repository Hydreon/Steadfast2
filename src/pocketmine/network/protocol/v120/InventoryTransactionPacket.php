<?php

namespace pocketmine\network\protocol\v120;

use pocketmine\inventory\transactions\SimpleTransactionData;
use pocketmine\network\protocol\Info;
use pocketmine\network\protocol\Info331;
use pocketmine\network\protocol\PEPacket;

class InventoryTransactionPacket extends PEPacket {

	const NETWORK_ID = Info331::INVENTORY_TRANSACTION_PACKET;
	const PACKET_NAME = "INVENTORY_TRANSACTION_PACKET";
	
	const TRANSACTION_TYPE_NORMAL = 0;
	const TRANSACTION_TYPE_INVENTORY_MISMATCH = 1;
	const TRANSACTION_TYPE_ITEM_USE = 2;
	const TRANSACTION_TYPE_ITEM_USE_ON_ENTITY = 3;
	const TRANSACTION_TYPE_ITEM_RELEASE = 4;	
	
	const INV_SOURCE_TYPE_CONTAINER = 0;
	const INV_SOURCE_TYPE_GLOBAL = 1;
	const INV_SOURCE_TYPE_WORLD_INTERACTION = 2;
	const INV_SOURCE_TYPE_CREATIVE = 3;
	const INV_SOURCE_TYPE_CRAFT_SLOT = 100;
	const INV_SOURCE_TYPE_CRAFT = 99999;	
	
	const ITEM_RELEASE_ACTION_RELEASE = 0;
	const ITEM_RELEASE_ACTION_USE = 1;
	
	const ITEM_USE_ACTION_PLACE = 0;
	const ITEM_USE_ACTION_USE = 1;
	const ITEM_USE_ACTION_DESTROY = 2;
	
	const ITEM_USE_ON_ENTITY_ACTION_INTERACT = 0;
	const ITEM_USE_ON_ENTITY_ACTION_ATTACK = 1;
	const ITEM_USE_ON_ENTITY_ACTION_ITEM_INTERACT = 2;

	public $transactionType;
	/** @var SimpleTransactionData */
	public $transactions;
	public $actionType;
	public $position;
	public $face;
	public $slot;
	public $item;
	public $fromPosition;
	public $clickPosition;
	public $entityId;

	public function decode($playerProtocol) {	
		$this->getHeader($playerProtocol);
		if ($playerProtocol >= Info::PROTOCOL_419) {
			$unknown = $this->getVarInt();
			if ($unknown != 0) {
				$count = $this->getVarInt();
				for ($i = 0; $i < $count; $i++) {
					$invId = $this->getVarInt();
					$slotCount = $this->getVarInt();
					for ($j = 0; $j < $slotCount; $j++) {
						$slot = $this->getVarInt();
					}
				}
			}
		}	
		$this->transactionType = $this->getVarInt();
		// if ($playerProtocol >= Info::PROTOCOL_393) {
		// 	$this->getByte();
		// }		
		$this->transactions = $this->getTransactions($playerProtocol);
		$this->getComplexTransactions($playerProtocol);
	}

	public function encode($playerProtocol) {
		$this->reset($playerProtocol);
		/** @todo Доделать */
	}
	
	private function getTransactions($playerProtocol) {
		$transactions = [];
		$actionsCount = $this->getVarInt();
		for ($i = 0; $i < $actionsCount; $i++) {
			$tr = new SimpleTransactionData();
			$tr->sourceType = $this->getVarInt();
			switch ($tr->sourceType) {
				case self::INV_SOURCE_TYPE_CONTAINER;
					$tr->inventoryId = $this->getSignedVarInt();
					break;
				case self::INV_SOURCE_TYPE_GLOBAL: // ???
					break;
				case self::INV_SOURCE_TYPE_WORLD_INTERACTION:
					$tr->flags = $this->getVarInt(); // flags NoFlag = 0 WorldInteraction_Random = 1
					break;
				case self::INV_SOURCE_TYPE_CREATIVE:
					$tr->inventoryId = Protocol120::CONTAINER_ID_CREATIVE;
					break;
				case self::INV_SOURCE_TYPE_CRAFT:
				case self::INV_SOURCE_TYPE_CRAFT_SLOT:
					$tr->action = $this->getVarInt();
					break;
			}
			$tr->slot = $this->getVarInt();
			$tr->oldItem = $this->getSlot($playerProtocol);
			$tr->newItem = $this->getSlot($playerProtocol);	
			if ($playerProtocol == Info::PROTOCOL_419) {
				$this->getByte();
			}
			$transactions[] = $tr;
		}
		return $transactions;
	}



	private function getComplexTransactions($playerProtocol) {
		switch ($this->transactionType) {
			case self::TRANSACTION_TYPE_NORMAL:
			case self::TRANSACTION_TYPE_INVENTORY_MISMATCH:
				return;
			case self::TRANSACTION_TYPE_ITEM_USE:
				$this->actionType = $this->getVarInt();
				$this->position = [
					'x' => $this->getSignedVarInt(),
					'y' => $this->getVarInt(),
					'z' => $this->getSignedVarInt()
				];
				$this->face = $this->getSignedVarInt();
				$this->slot = $this->getSignedVarInt();
				$this->item = $this->getSlot($playerProtocol);
				$this->fromPosition = [
					'x' => $this->getLFloat(),
					'y' => $this->getLFloat(),
					'z' => $this->getLFloat()
				];
				$this->clickPosition = [
					'x' => $this->getLFloat(),
					'y' => $this->getLFloat(),
					'z' => $this->getLFloat()
				];
				return;
			case self::TRANSACTION_TYPE_ITEM_USE_ON_ENTITY:
				$this->entityId = $this->getVarInt();
				$this->actionType = $this->getVarInt();
				$this->slot = $this->getSignedVarInt();
				$this->item = $this->getSlot($playerProtocol);
				$this->fromPosition = [
					'x' => $this->getLFloat(),
					'y' => $this->getLFloat(),
					'z' => $this->getLFloat()
				];
				return;
			case self::TRANSACTION_TYPE_ITEM_RELEASE:
				$this->actionType = $this->getVarInt();
				$this->slot = $this->getSignedVarInt();
				$this->item = $this->getSlot($playerProtocol);
				$this->fromPosition = [
					'x' => $this->getLFloat(),
					'y' => $this->getLFloat(),
					'z' => $this->getLFloat()
				];
				return;
		}
	}

}
