<?php

/*
 *
 *  ____            _        _   __  __ _                  __  __ ____
 * |  _ \ ___   ___| | _____| |_|  \/  (_)_ __   ___      |  \/  |  _ \
 * | |_) / _ \ / __| |/ / _ \ __| |\/| | | '_ \ / _ \_____| |\/| | |_) |
 * |  __/ (_) | (__|   <  __/ |_| |  | | | | | |  __/_____| |  | |  __/
 * |_|   \___/ \___|_|\_\___|\__|_|  |_|_|_| |_|\___|     |_|  |_|_|
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author PocketMine Team
 * @link http://www.pocketmine.net/
 *
 *
*/

/**
 * Network-related classes
 */
namespace pocketmine\network;

use pocketmine\network\protocol\AddEntityPacket;
use pocketmine\network\protocol\AddItemEntityPacket;
use pocketmine\network\protocol\AddPaintingPacket;
use pocketmine\network\protocol\AddPlayerPacket;
use pocketmine\network\protocol\AdventureSettingsPacket;
use pocketmine\network\protocol\AnimatePacket;
use pocketmine\network\protocol\BatchPacket;
use pocketmine\network\protocol\ContainerClosePacket;
use pocketmine\network\protocol\ContainerOpenPacket;
use pocketmine\network\protocol\ContainerSetContentPacket;
use pocketmine\network\protocol\ContainerSetDataPacket;
use pocketmine\network\protocol\ContainerSetSlotPacket;
use pocketmine\network\protocol\CraftingDataPacket;
use pocketmine\network\protocol\CraftingEventPacket;
use pocketmine\network\protocol\DataPacket;
use pocketmine\network\protocol\DropItemPacket;
use pocketmine\network\protocol\FullChunkDataPacket;
use pocketmine\network\protocol\Info;
use pocketmine\network\protocol\SetEntityLinkPacket;
use pocketmine\network\protocol\TileEntityDataPacket;
use pocketmine\network\protocol\EntityEventPacket;
use pocketmine\network\protocol\ExplodePacket;
use pocketmine\network\protocol\HurtArmorPacket;
use pocketmine\network\protocol\Info as ProtocolInfo;
use pocketmine\network\protocol\Info105 as ProtocolInfo105;
use pocketmine\network\protocol\Info110 as ProtocolInfo110;
use pocketmine\network\protocol\Info120 as ProtocolInfo120;
use pocketmine\network\protocol\InteractPacket;
use pocketmine\network\protocol\LevelEventPacket;
use pocketmine\network\protocol\LevelSoundEventPacket;
use pocketmine\network\protocol\DisconnectPacket;
use pocketmine\network\protocol\LoginPacket;
use pocketmine\network\protocol\PlayStatusPacket;
use pocketmine\network\protocol\TextPacket;
use pocketmine\network\protocol\MoveEntityPacket;
use pocketmine\network\protocol\MovePlayerPacket;
use pocketmine\network\protocol\PlayerActionPacket;
use pocketmine\network\protocol\MobArmorEquipmentPacket;
use pocketmine\network\protocol\MobEquipmentPacket;
use pocketmine\network\protocol\RemoveBlockPacket;
use pocketmine\network\protocol\RemoveEntityPacket;
use pocketmine\network\protocol\RespawnPacket;
use pocketmine\network\protocol\SetDifficultyPacket;
use pocketmine\network\protocol\SetEntityDataPacket;
use pocketmine\network\protocol\SetEntityMotionPacket;
use pocketmine\network\protocol\SetSpawnPositionPacket;
use pocketmine\network\protocol\SetTimePacket;
use pocketmine\network\protocol\StartGamePacket;
use pocketmine\network\protocol\TakeItemEntityPacket;
use pocketmine\network\protocol\TileEventPacket;
use pocketmine\network\protocol\TransferPacket;
use pocketmine\network\protocol\UpdateBlockPacket;
use pocketmine\network\protocol\UseItemPacket;
use pocketmine\network\protocol\PlayerListPacket;
use pocketmine\network\protocol\v120\PlayerSkinPacket;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\MainLogger;
use pocketmine\network\protocol\ChunkRadiusUpdatePacket;
use pocketmine\network\protocol\RequestChunkRadiusPacket;
use pocketmine\utils\BinaryStream;
use pocketmine\network\protocol\SetCommandsEnabledPacket;
use pocketmine\network\protocol\AvailableCommandsPacket;
use pocketmine\network\protocol\CommandStepPacket;
use pocketmine\network\protocol\ResourcePackDataInfoPacket;
use pocketmine\network\protocol\ResourcePacksInfoPacket;
use pocketmine\network\protocol\ClientToServerHandshakePacket;
use pocketmine\network\protocol\ResourcePackClientResponsePacket;
use pocketmine\network\protocol\v120\CommandRequestPacket;
use pocketmine\network\protocol\v120\InventoryContentPacket;
use pocketmine\network\protocol\v120\InventoryTransactionPacket;
use pocketmine\network\protocol\v120\ModalFormResponsePacket;
use pocketmine\network\protocol\v120\PlayerHotbarPacket;
use pocketmine\network\protocol\v120\PurchaseReceiptPacket;
use pocketmine\network\protocol\v120\ServerSettingsRequestPacket;
use pocketmine\network\protocol\v120\SubClientLoginPacket;
use pocketmine\network\protocol\ResourcePackChunkRequestPacket;
use pocketmine\network\protocol\PlayerInputPacket;

class Network {

	public static $BATCH_THRESHOLD = 512;

	/** @var \SplFixedArray */
	private $packetPool;
	
	/** @var \SplFixedArray */
	private $packetPool105;
	
	/** @var \SplFixedArray */
	private $packetPool110;
	
	/** @var \SplFixedArray */
	private $packetPool120;

	/** @var Server */
	private $server;

	/** @var SourceInterface[] */
	private $interfaces = [];

	/** @var AdvancedSourceInterface[] */
	private $advancedInterfaces = [];

	private $upload = 0;
	private $download = 0;

	private $name;

	public function __construct(Server $server){

		$this->registerPackets();
		$this->registerPackets105();
		$this->registerPackets110();
		$this->registerPackets120();

		$this->server = $server;

	}

	public function addStatistics($upload, $download){
		$this->upload += $upload;
		$this->download += $download;
	}

	public function getUpload(){
		return $this->upload;
	}

	public function getDownload(){
		return $this->download;
	}

	public function resetStatistics(){
		$this->upload = 0;
		$this->download = 0;
	}

	/**
	 * @return SourceInterface[]
	 */
	public function getInterfaces(){
		return $this->interfaces;
	}

	public function setCount($count, $maxcount = 31360) {
		$this->server->mainInterface->setCount($count, $maxcount);
	}

	public function processInterfaces() {
		foreach($this->interfaces as $interface) {
			try {
				$interface->process();
			}catch(\Exception $e){
				$logger = $this->server->getLogger();
				if(\pocketmine\DEBUG > 1){
					if($logger instanceof MainLogger){
						$logger->logException($e);
					}
				}

				$interface->emergencyShutdown();
				$this->unregisterInterface($interface);
				$logger->critical("Network error: ".$e->getMessage());
			}
		}
	}

	/**
	 * @param SourceInterface $interface
	 */
	public function registerInterface(SourceInterface $interface) {
		$this->interfaces[$hash = spl_object_hash($interface)] = $interface;
		if($interface instanceof AdvancedSourceInterface) {
			$this->advancedInterfaces[$hash] = $interface;
			$interface->setNetwork($this);
		}
		$interface->setName($this->name);
	}

	/**
	 * @param SourceInterface $interface
	 */
	public function unregisterInterface(SourceInterface $interface) {
		unset($this->interfaces[$hash = spl_object_hash($interface)], $this->advancedInterfaces[$hash]);
	}

	/**
	 * Sets the server name shown on each interface Query
	 *
	 * @param string $name
	 */
	public function setName($name) {
		$this->name = (string)$name;
		foreach($this->interfaces as $interface) {
			$interface->setName($this->name);
		}
	}

	public function getName(){
		return $this->name;
	}

	public function updateName() {
		foreach($this->interfaces as $interface) {
			$interface->setName($this->name);
		}
	}

	/**
	 * @param int        $id 0-255
	 * @param DataPacket $class
	 */
	public function registerPacket($id, $class){
		$this->packetPool[$id] = new $class;
	}
	
	/**
	 * @param int        $id 0-255
	 * @param DataPacket $class
	 */
	public function registerPacket105($id, $class){
		$this->packetPool105[$id] = new $class;
	}
	
	/**
	 * @param int        $id 0-255
	 * @param DataPacket $class
	 */
	public function registerPacket110($id, $class){
		$this->packetPool110[$id] = new $class;
	}
	
	/**
	 * @param int        $id 0-255
	 * @param DataPacket $class
	 */
	public function registerPacket120($id, $class){
		$this->packetPool120[$id] = new $class;
	}
	
	public function getServer(){
		return $this->server;
	}
			
	public function processBatch(BatchPacket $packet, Player $p){
		$str = @\zlib_decode($packet->payload, 1024 * 1024 * 64); //Max 64MB
		if ($str === false) {
			return;
		}
		try{
			$stream = new BinaryStream($str);
			$length = strlen($str);
			while ($stream->getOffset() < $length) {
				$buf = $stream->getString();
				if(strlen($buf) === 0){
					throw new \InvalidStateException("Empty or invalid BatchPacket received");
				}
//				var_dump("Recive: 0x" . (ord($buf{0}) < 16 ? '0' . dechex(ord($buf{0})) : dechex(ord($buf{0}))));
				if (($pk = $this->getPacket(ord($buf{0}), $p->getPlayerProtocol())) !== null) {
					if ($pk::NETWORK_ID === Info::BATCH_PACKET) {
						throw new \InvalidStateException("Invalid BatchPacket inside BatchPacket");
					}
					$pk->setBuffer($buf, 1);
					try {
						$pk->decode($p->getPlayerProtocol());
					}catch(\Exception $e){
						file_put_contents("logs/" . date('Y.m.d') . "_decode_error.log", $e->getMessage() . "\n", FILE_APPEND | LOCK_EX);
						return;
					}
					$p->handleDataPacket($pk);
					if ($pk->getOffset() <= 0) {
						return;
					}
				} else {
//					echo "UNKNOWN PACKET: 0x" . (ord($buf{0}) < 16 ? '0' . dechex(ord($buf{0})) : dechex(ord($buf{0}))) . PHP_EOL;
//					echo "Buffer DEC: ".$buf.PHP_EOL;
//					echo "Buffer HEX: ".bin2hex($buf).PHP_EOL;
				}
			}
		}catch(\Exception $e){
			if(\pocketmine\DEBUG > 1){
				$logger = $this->server->getLogger();
				if($logger instanceof MainLogger){
					$logger->debug("BatchPacket " . " 0x" . bin2hex($packet->payload));
					$logger->logException($e);
				}
			}
		}
	}

	/**
	 * @param $id
	 *
	 * @return DataPacket
	 */
	public function getPacket($id, $playerProtocol){
		/** @var DataPacket $class */
		switch ($playerProtocol) {
			case Info::PROTOCOL_120:
			case Info::PROTOCOL_200:
			case Info::PROTOCOL_220:
			case Info::PROTOCOL_221:
			case Info::PROTOCOL_240:
			case Info::PROTOCOL_260:
				$class = $this->packetPool120[$id];
				break;
			case Info::PROTOCOL_110:
				$class = $this->packetPool110[$id];
				break;
			case Info::PROTOCOL_105:
				$class = $this->packetPool105[$id];
				break;
			default:
				$class = $this->packetPool[$id];
				break;
		}
		if($class !== null){
			return clone $class;
		}
		return null;
	}
	

	/**
	 * @param string $address
	 * @param int    $port
	 * @param string $payload
	 */
	public function sendPacket($address, $port, $payload){
		foreach($this->advancedInterfaces as $interface){
			$interface->sendRawPacket($address, $port, $payload);
		}
	}

	/**
	 * Blocks an IP address from the main interface. Setting timeout to -1 will block it forever
	 *
	 * @param string $address
	 * @param int    $timeout
	 */
	public function blockAddress($address, $timeout = 300){
		foreach($this->advancedInterfaces as $interface){
			$interface->blockAddress($address, $timeout);
		}
	}

	private function registerPackets(){
		$this->packetPool = new \SplFixedArray(256);
		$this->registerPacket(ProtocolInfo::LOGIN_PACKET, LoginPacket::class);
		$this->registerPacket(ProtocolInfo::PLAY_STATUS_PACKET, PlayStatusPacket::class);
		$this->registerPacket(ProtocolInfo::DISCONNECT_PACKET, DisconnectPacket::class);
		$this->registerPacket(ProtocolInfo::BATCH_PACKET, BatchPacket::class);
		$this->registerPacket(ProtocolInfo::TEXT_PACKET, TextPacket::class);
		$this->registerPacket(ProtocolInfo::SET_TIME_PACKET, SetTimePacket::class);
		$this->registerPacket(ProtocolInfo::START_GAME_PACKET, StartGamePacket::class);
		$this->registerPacket(ProtocolInfo::ADD_PLAYER_PACKET, AddPlayerPacket::class);
		$this->registerPacket(ProtocolInfo::ADD_ENTITY_PACKET, AddEntityPacket::class);
		$this->registerPacket(ProtocolInfo::REMOVE_ENTITY_PACKET, RemoveEntityPacket::class);
		$this->registerPacket(ProtocolInfo::ADD_ITEM_ENTITY_PACKET, AddItemEntityPacket::class);
		$this->registerPacket(ProtocolInfo::TAKE_ITEM_ENTITY_PACKET, TakeItemEntityPacket::class);
		$this->registerPacket(ProtocolInfo::MOVE_ENTITY_PACKET, MoveEntityPacket::class);
		$this->registerPacket(ProtocolInfo::MOVE_PLAYER_PACKET, MovePlayerPacket::class);
		$this->registerPacket(ProtocolInfo::REMOVE_BLOCK_PACKET, RemoveBlockPacket::class);
		$this->registerPacket(ProtocolInfo::UPDATE_BLOCK_PACKET, UpdateBlockPacket::class);
		$this->registerPacket(ProtocolInfo::ADD_PAINTING_PACKET, AddPaintingPacket::class);
		$this->registerPacket(ProtocolInfo::EXPLODE_PACKET, ExplodePacket::class);
		$this->registerPacket(ProtocolInfo::LEVEL_EVENT_PACKET, LevelEventPacket::class);
		$this->registerPacket(ProtocolInfo::LEVEL_SOUND_EVENT_PACKET, LevelSoundEventPacket::class);
		$this->registerPacket(ProtocolInfo::TILE_EVENT_PACKET, TileEventPacket::class);
		$this->registerPacket(ProtocolInfo::ENTITY_EVENT_PACKET, EntityEventPacket::class);
		$this->registerPacket(ProtocolInfo::MOB_EQUIPMENT_PACKET, MobEquipmentPacket::class);
		$this->registerPacket(ProtocolInfo::MOB_ARMOR_EQUIPMENT_PACKET, MobArmorEquipmentPacket::class);
		$this->registerPacket(ProtocolInfo::INTERACT_PACKET, InteractPacket::class);
		$this->registerPacket(ProtocolInfo::USE_ITEM_PACKET, UseItemPacket::class);
		$this->registerPacket(ProtocolInfo::PLAYER_ACTION_PACKET, PlayerActionPacket::class);
		$this->registerPacket(ProtocolInfo::HURT_ARMOR_PACKET, HurtArmorPacket::class);
		$this->registerPacket(ProtocolInfo::SET_ENTITY_DATA_PACKET, SetEntityDataPacket::class);
		$this->registerPacket(ProtocolInfo::SET_ENTITY_MOTION_PACKET, SetEntityMotionPacket::class);
		$this->registerPacket(ProtocolInfo::SET_ENTITY_LINK_PACKET, SetEntityLinkPacket::class);
		$this->registerPacket(ProtocolInfo::SET_SPAWN_POSITION_PACKET, SetSpawnPositionPacket::class);
		$this->registerPacket(ProtocolInfo::ANIMATE_PACKET, AnimatePacket::class);
		$this->registerPacket(ProtocolInfo::RESPAWN_PACKET, RespawnPacket::class);
		$this->registerPacket(ProtocolInfo::DROP_ITEM_PACKET, DropItemPacket::class);
		$this->registerPacket(ProtocolInfo::CONTAINER_OPEN_PACKET, ContainerOpenPacket::class);
		$this->registerPacket(ProtocolInfo::CONTAINER_CLOSE_PACKET, ContainerClosePacket::class);
		$this->registerPacket(ProtocolInfo::CONTAINER_SET_SLOT_PACKET, ContainerSetSlotPacket::class);
		$this->registerPacket(ProtocolInfo::CONTAINER_SET_DATA_PACKET, ContainerSetDataPacket::class);
		$this->registerPacket(ProtocolInfo::CONTAINER_SET_CONTENT_PACKET, ContainerSetContentPacket::class);
		$this->registerPacket(ProtocolInfo::CRAFTING_DATA_PACKET, CraftingDataPacket::class);
		$this->registerPacket(ProtocolInfo::CRAFTING_EVENT_PACKET, CraftingEventPacket::class);
		$this->registerPacket(ProtocolInfo::ADVENTURE_SETTINGS_PACKET, AdventureSettingsPacket::class);
		$this->registerPacket(ProtocolInfo::TILE_ENTITY_DATA_PACKET, TileEntityDataPacket::class);
		$this->registerPacket(ProtocolInfo::FULL_CHUNK_DATA_PACKET, FullChunkDataPacket::class);
		$this->registerPacket(ProtocolInfo::SET_COMMANDS_ENABLED_PACKET, SetCommandsEnabledPacket::class);
		$this->registerPacket(ProtocolInfo::SET_DIFFICULTY_PACKET, SetDifficultyPacket::class);
		$this->registerPacket(ProtocolInfo::PLAYER_LIST_PACKET, PlayerListPacket::class);
		$this->registerPacket(ProtocolInfo::REQUEST_CHUNK_RADIUS_PACKET, RequestChunkRadiusPacket::class);
		$this->registerPacket(ProtocolInfo::CHUNK_RADIUS_UPDATE_PACKET, ChunkRadiusUpdatePacket::class);
		$this->registerPacket(ProtocolInfo::AVAILABLE_COMMANDS_PACKET, AvailableCommandsPacket::class);
		$this->registerPacket(ProtocolInfo::COMMAND_STEP_PACKET, CommandStepPacket::class);
		$this->registerPacket(ProtocolInfo::TRANSFER_PACKET, TransferPacket::class);
		$this->registerPacket(ProtocolInfo::CLIENT_TO_SERVER_HANDSHAKE_PACKET, ClientToServerHandshakePacket::class);
		$this->registerPacket(ProtocolInfo::RESOURCE_PACK_DATA_INFO_PACKET, ResourcePackDataInfoPacket::class);
		$this->registerPacket(ProtocolInfo::RESOURCE_PACKS_INFO_PACKET, ResourcePacksInfoPacket::class);
		$this->registerPacket(ProtocolInfo::RESOURCE_PACKS_CLIENT_RESPONSE_PACKET, ResourcePackClientResponsePacket::class);
		$this->registerPacket(ProtocolInfo::RESOURCE_PACK_CHUNK_REQUEST_PACKET, ResourcePackChunkRequestPacket::class);
		$this->registerPacket(ProtocolInfo::PLAYER_INPUT_PACKET, PlayerInputPacket::class);
	}
	
	private function registerPackets105(){
		$this->packetPool105 = new \SplFixedArray(256);
		$this->registerPacket105(ProtocolInfo105::LOGIN_PACKET, LoginPacket::class);
		$this->registerPacket105(ProtocolInfo105::PLAY_STATUS_PACKET, PlayStatusPacket::class);
		$this->registerPacket105(ProtocolInfo105::DISCONNECT_PACKET, DisconnectPacket::class);
		$this->registerPacket105(ProtocolInfo105::BATCH_PACKET, BatchPacket::class);
		$this->registerPacket105(ProtocolInfo105::TEXT_PACKET, TextPacket::class);
		$this->registerPacket105(ProtocolInfo105::SET_TIME_PACKET, SetTimePacket::class);
		$this->registerPacket105(ProtocolInfo105::START_GAME_PACKET, StartGamePacket::class);
		$this->registerPacket105(ProtocolInfo105::ADD_PLAYER_PACKET, AddPlayerPacket::class);
		$this->registerPacket105(ProtocolInfo105::ADD_ENTITY_PACKET, AddEntityPacket::class);
		$this->registerPacket105(ProtocolInfo105::REMOVE_ENTITY_PACKET, RemoveEntityPacket::class);
		$this->registerPacket105(ProtocolInfo105::ADD_ITEM_ENTITY_PACKET, AddItemEntityPacket::class);
		$this->registerPacket105(ProtocolInfo105::TAKE_ITEM_ENTITY_PACKET, TakeItemEntityPacket::class);
		$this->registerPacket105(ProtocolInfo105::MOVE_ENTITY_PACKET, MoveEntityPacket::class);
		$this->registerPacket105(ProtocolInfo105::MOVE_PLAYER_PACKET, MovePlayerPacket::class);
		$this->registerPacket105(ProtocolInfo105::REMOVE_BLOCK_PACKET, RemoveBlockPacket::class);
		$this->registerPacket105(ProtocolInfo105::UPDATE_BLOCK_PACKET, UpdateBlockPacket::class);
		$this->registerPacket105(ProtocolInfo105::ADD_PAINTING_PACKET, AddPaintingPacket::class);
		$this->registerPacket105(ProtocolInfo105::EXPLODE_PACKET, ExplodePacket::class);
		$this->registerPacket105(ProtocolInfo105::LEVEL_EVENT_PACKET, LevelEventPacket::class);
		$this->registerPacket105(ProtocolInfo105::LEVEL_SOUND_EVENT_PACKET, LevelSoundEventPacket::class);
		$this->registerPacket105(ProtocolInfo105::TILE_EVENT_PACKET, TileEventPacket::class);
		$this->registerPacket105(ProtocolInfo105::ENTITY_EVENT_PACKET, EntityEventPacket::class);
		$this->registerPacket105(ProtocolInfo105::MOB_EQUIPMENT_PACKET, MobEquipmentPacket::class);
		$this->registerPacket105(ProtocolInfo105::MOB_ARMOR_EQUIPMENT_PACKET, MobArmorEquipmentPacket::class);
		$this->registerPacket105(ProtocolInfo105::INTERACT_PACKET, InteractPacket::class);
		$this->registerPacket105(ProtocolInfo105::USE_ITEM_PACKET, UseItemPacket::class);
		$this->registerPacket105(ProtocolInfo105::PLAYER_ACTION_PACKET, PlayerActionPacket::class);
		$this->registerPacket105(ProtocolInfo105::HURT_ARMOR_PACKET, HurtArmorPacket::class);
		$this->registerPacket105(ProtocolInfo105::SET_ENTITY_DATA_PACKET, SetEntityDataPacket::class);
		$this->registerPacket105(ProtocolInfo105::SET_ENTITY_MOTION_PACKET, SetEntityMotionPacket::class);
		$this->registerPacket105(ProtocolInfo105::SET_ENTITY_LINK_PACKET, SetEntityLinkPacket::class);
		$this->registerPacket105(ProtocolInfo105::SET_SPAWN_POSITION_PACKET, SetSpawnPositionPacket::class);
		$this->registerPacket105(ProtocolInfo105::ANIMATE_PACKET, AnimatePacket::class);
		$this->registerPacket105(ProtocolInfo105::RESPAWN_PACKET, RespawnPacket::class);
		$this->registerPacket105(ProtocolInfo105::DROP_ITEM_PACKET, DropItemPacket::class);
		$this->registerPacket105(ProtocolInfo105::CONTAINER_OPEN_PACKET, ContainerOpenPacket::class);
		$this->registerPacket105(ProtocolInfo105::CONTAINER_CLOSE_PACKET, ContainerClosePacket::class);
		$this->registerPacket105(ProtocolInfo105::CONTAINER_SET_SLOT_PACKET, ContainerSetSlotPacket::class);
		$this->registerPacket105(ProtocolInfo105::CONTAINER_SET_DATA_PACKET, ContainerSetDataPacket::class);
		$this->registerPacket105(ProtocolInfo105::CONTAINER_SET_CONTENT_PACKET, ContainerSetContentPacket::class);
		$this->registerPacket105(ProtocolInfo105::CRAFTING_DATA_PACKET, CraftingDataPacket::class);
		$this->registerPacket105(ProtocolInfo105::CRAFTING_EVENT_PACKET, CraftingEventPacket::class);
		$this->registerPacket105(ProtocolInfo105::ADVENTURE_SETTINGS_PACKET, AdventureSettingsPacket::class);
		$this->registerPacket105(ProtocolInfo105::TILE_ENTITY_DATA_PACKET, TileEntityDataPacket::class);
		$this->registerPacket105(ProtocolInfo105::FULL_CHUNK_DATA_PACKET, FullChunkDataPacket::class);
		$this->registerPacket105(ProtocolInfo105::SET_COMMANDS_ENABLED_PACKET, SetCommandsEnabledPacket::class);
		$this->registerPacket105(ProtocolInfo105::SET_DIFFICULTY_PACKET, SetDifficultyPacket::class);
		$this->registerPacket105(ProtocolInfo105::PLAYER_LIST_PACKET, PlayerListPacket::class);
		$this->registerPacket105(ProtocolInfo105::REQUEST_CHUNK_RADIUS_PACKET, RequestChunkRadiusPacket::class);
		$this->registerPacket105(ProtocolInfo105::CHUNK_RADIUS_UPDATE_PACKET, ChunkRadiusUpdatePacket::class);
		$this->registerPacket105(ProtocolInfo105::AVAILABLE_COMMANDS_PACKET, AvailableCommandsPacket::class);
		$this->registerPacket105(ProtocolInfo105::COMMAND_STEP_PACKET, CommandStepPacket::class);
		$this->registerPacket105(ProtocolInfo105::TRANSFER_PACKET, TransferPacket::class);
		$this->registerPacket105(ProtocolInfo105::CLIENT_TO_SERVER_HANDSHAKE_PACKET, ClientToServerHandshakePacket::class);
		$this->registerPacket105(ProtocolInfo105::RESOURCE_PACK_DATA_INFO_PACKET, ResourcePackDataInfoPacket::class);
		$this->registerPacket105(ProtocolInfo105::RESOURCE_PACKS_INFO_PACKET, ResourcePacksInfoPacket::class);
		$this->registerPacket105(ProtocolInfo105::RESOURCE_PACKS_CLIENT_RESPONSE_PACKET, ResourcePackClientResponsePacket::class);
		$this->registerPacket105(ProtocolInfo105::RESOURCE_PACK_CHUNK_REQUEST_PACKET, ResourcePackChunkRequestPacket::class);
		$this->registerPacket105(ProtocolInfo105::PLAYER_INPUT_PACKET, PlayerInputPacket::class);
	}
	
	
	private function registerPackets110(){
		$this->packetPool110 = new \SplFixedArray(256);
		$this->registerPacket110(ProtocolInfo110::LOGIN_PACKET, LoginPacket::class);
		$this->registerPacket110(ProtocolInfo110::PLAY_STATUS_PACKET, PlayStatusPacket::class);
		$this->registerPacket110(ProtocolInfo110::DISCONNECT_PACKET, DisconnectPacket::class);
		$this->registerPacket110(ProtocolInfo110::TEXT_PACKET, TextPacket::class);
		$this->registerPacket110(ProtocolInfo110::SET_TIME_PACKET, SetTimePacket::class);
		$this->registerPacket110(ProtocolInfo110::START_GAME_PACKET, StartGamePacket::class);
		$this->registerPacket110(ProtocolInfo110::ADD_PLAYER_PACKET, AddPlayerPacket::class);
		$this->registerPacket110(ProtocolInfo110::ADD_ENTITY_PACKET, AddEntityPacket::class);
		$this->registerPacket110(ProtocolInfo110::REMOVE_ENTITY_PACKET, RemoveEntityPacket::class);
		$this->registerPacket110(ProtocolInfo110::ADD_ITEM_ENTITY_PACKET, AddItemEntityPacket::class);
		$this->registerPacket110(ProtocolInfo110::TAKE_ITEM_ENTITY_PACKET, TakeItemEntityPacket::class);
		$this->registerPacket110(ProtocolInfo110::MOVE_ENTITY_PACKET, MoveEntityPacket::class);
		$this->registerPacket110(ProtocolInfo110::MOVE_PLAYER_PACKET, MovePlayerPacket::class);
		$this->registerPacket110(ProtocolInfo110::REMOVE_BLOCK_PACKET, RemoveBlockPacket::class);
		$this->registerPacket110(ProtocolInfo110::UPDATE_BLOCK_PACKET, UpdateBlockPacket::class);
		$this->registerPacket110(ProtocolInfo110::ADD_PAINTING_PACKET, AddPaintingPacket::class);
		$this->registerPacket110(ProtocolInfo110::EXPLODE_PACKET, ExplodePacket::class);
		$this->registerPacket110(ProtocolInfo110::LEVEL_EVENT_PACKET, LevelEventPacket::class);
		$this->registerPacket110(ProtocolInfo110::LEVEL_SOUND_EVENT_PACKET, LevelSoundEventPacket::class);
		$this->registerPacket110(ProtocolInfo110::TILE_EVENT_PACKET, TileEventPacket::class);
		$this->registerPacket110(ProtocolInfo110::ENTITY_EVENT_PACKET, EntityEventPacket::class);
		$this->registerPacket110(ProtocolInfo110::MOB_EQUIPMENT_PACKET, MobEquipmentPacket::class);
		$this->registerPacket110(ProtocolInfo110::MOB_ARMOR_EQUIPMENT_PACKET, MobArmorEquipmentPacket::class);
		$this->registerPacket110(ProtocolInfo110::INTERACT_PACKET, InteractPacket::class);
		$this->registerPacket110(ProtocolInfo110::USE_ITEM_PACKET, UseItemPacket::class);
		$this->registerPacket110(ProtocolInfo110::PLAYER_ACTION_PACKET, PlayerActionPacket::class);
		$this->registerPacket110(ProtocolInfo110::HURT_ARMOR_PACKET, HurtArmorPacket::class);
		$this->registerPacket110(ProtocolInfo110::SET_ENTITY_DATA_PACKET, SetEntityDataPacket::class);
		$this->registerPacket110(ProtocolInfo110::SET_ENTITY_MOTION_PACKET, SetEntityMotionPacket::class);
		$this->registerPacket110(ProtocolInfo110::SET_ENTITY_LINK_PACKET, SetEntityLinkPacket::class);
		$this->registerPacket110(ProtocolInfo110::SET_SPAWN_POSITION_PACKET, SetSpawnPositionPacket::class);
		$this->registerPacket110(ProtocolInfo110::ANIMATE_PACKET, AnimatePacket::class);
		$this->registerPacket110(ProtocolInfo110::RESPAWN_PACKET, RespawnPacket::class);
		$this->registerPacket110(ProtocolInfo110::DROP_ITEM_PACKET, DropItemPacket::class);
		$this->registerPacket110(ProtocolInfo110::CONTAINER_OPEN_PACKET, ContainerOpenPacket::class);
		$this->registerPacket110(ProtocolInfo110::CONTAINER_CLOSE_PACKET, ContainerClosePacket::class);
		$this->registerPacket110(ProtocolInfo110::CONTAINER_SET_SLOT_PACKET, ContainerSetSlotPacket::class);
		$this->registerPacket110(ProtocolInfo110::CONTAINER_SET_DATA_PACKET, ContainerSetDataPacket::class);
		$this->registerPacket110(ProtocolInfo110::CONTAINER_SET_CONTENT_PACKET, ContainerSetContentPacket::class);
		$this->registerPacket110(ProtocolInfo110::CRAFTING_DATA_PACKET, CraftingDataPacket::class);
		$this->registerPacket110(ProtocolInfo110::CRAFTING_EVENT_PACKET, CraftingEventPacket::class);
		$this->registerPacket110(ProtocolInfo110::ADVENTURE_SETTINGS_PACKET, AdventureSettingsPacket::class);
		$this->registerPacket110(ProtocolInfo110::TILE_ENTITY_DATA_PACKET, TileEntityDataPacket::class);
		$this->registerPacket110(ProtocolInfo110::FULL_CHUNK_DATA_PACKET, FullChunkDataPacket::class);
		$this->registerPacket110(ProtocolInfo110::SET_COMMANDS_ENABLED_PACKET, SetCommandsEnabledPacket::class);
		$this->registerPacket110(ProtocolInfo110::SET_DIFFICULTY_PACKET, SetDifficultyPacket::class);
		$this->registerPacket110(ProtocolInfo110::PLAYER_LIST_PACKET, PlayerListPacket::class);
		$this->registerPacket110(ProtocolInfo110::REQUEST_CHUNK_RADIUS_PACKET, RequestChunkRadiusPacket::class);
		$this->registerPacket110(ProtocolInfo110::CHUNK_RADIUS_UPDATE_PACKET, ChunkRadiusUpdatePacket::class);
		$this->registerPacket110(ProtocolInfo110::AVAILABLE_COMMANDS_PACKET, AvailableCommandsPacket::class);
		$this->registerPacket110(ProtocolInfo110::COMMAND_STEP_PACKET, CommandStepPacket::class);
		$this->registerPacket110(ProtocolInfo110::TRANSFER_PACKET, TransferPacket::class);
		$this->registerPacket110(ProtocolInfo110::CLIENT_TO_SERVER_HANDSHAKE_PACKET, ClientToServerHandshakePacket::class);
		$this->registerPacket110(ProtocolInfo110::RESOURCE_PACK_DATA_INFO_PACKET, ResourcePackDataInfoPacket::class);
		$this->registerPacket110(ProtocolInfo110::RESOURCE_PACKS_INFO_PACKET, ResourcePacksInfoPacket::class);
		$this->registerPacket110(ProtocolInfo110::RESOURCE_PACKS_CLIENT_RESPONSE_PACKET, ResourcePackClientResponsePacket::class);
		$this->registerPacket110(ProtocolInfo110::RESOURCE_PACK_CHUNK_REQUEST_PACKET, ResourcePackChunkRequestPacket::class);
		$this->registerPacket110(ProtocolInfo110::PLAYER_INPUT_PACKET, PlayerInputPacket::class);
	
	}
	
	private function registerPackets120() {
		$this->packetPool120 = new \SplFixedArray(256);
		$this->registerPacket120(ProtocolInfo120::PLAY_STATUS_PACKET, PlayStatusPacket::class);
		$this->registerPacket120(ProtocolInfo120::DISCONNECT_PACKET, DisconnectPacket::class);
		$this->registerPacket120(ProtocolInfo120::TEXT_PACKET, TextPacket::class);
		$this->registerPacket120(ProtocolInfo120::SET_TIME_PACKET, SetTimePacket::class);
		$this->registerPacket120(ProtocolInfo120::START_GAME_PACKET, StartGamePacket::class);
		$this->registerPacket120(ProtocolInfo120::ADD_PLAYER_PACKET, AddPlayerPacket::class);
		$this->registerPacket120(ProtocolInfo120::ADD_ENTITY_PACKET, AddEntityPacket::class);
		$this->registerPacket120(ProtocolInfo120::REMOVE_ENTITY_PACKET, RemoveEntityPacket::class);
		$this->registerPacket120(ProtocolInfo120::ADD_ITEM_ENTITY_PACKET, AddItemEntityPacket::class);
		$this->registerPacket120(ProtocolInfo120::TAKE_ITEM_ENTITY_PACKET, TakeItemEntityPacket::class);
		$this->registerPacket120(ProtocolInfo120::MOVE_ENTITY_PACKET, MoveEntityPacket::class);
		$this->registerPacket120(ProtocolInfo120::MOVE_PLAYER_PACKET, MovePlayerPacket::class);
		$this->registerPacket120(ProtocolInfo120::UPDATE_BLOCK_PACKET, UpdateBlockPacket::class);
		$this->registerPacket120(ProtocolInfo120::ADD_PAINTING_PACKET, AddPaintingPacket::class);
		$this->registerPacket120(ProtocolInfo120::EXPLODE_PACKET, ExplodePacket::class);
		$this->registerPacket120(ProtocolInfo120::LEVEL_EVENT_PACKET, LevelEventPacket::class);
		$this->registerPacket120(ProtocolInfo120::LEVEL_SOUND_EVENT_PACKET, LevelSoundEventPacket::class);
		$this->registerPacket120(ProtocolInfo120::TILE_EVENT_PACKET, TileEventPacket::class);
		$this->registerPacket120(ProtocolInfo120::ENTITY_EVENT_PACKET, EntityEventPacket::class);
		$this->registerPacket120(ProtocolInfo120::MOB_EQUIPMENT_PACKET, MobEquipmentPacket::class);
		$this->registerPacket120(ProtocolInfo120::MOB_ARMOR_EQUIPMENT_PACKET, MobArmorEquipmentPacket::class);
		$this->registerPacket120(ProtocolInfo120::INTERACT_PACKET, InteractPacket::class);
		$this->registerPacket120(ProtocolInfo120::PLAYER_ACTION_PACKET, PlayerActionPacket::class);
		$this->registerPacket120(ProtocolInfo120::HURT_ARMOR_PACKET, HurtArmorPacket::class);
		$this->registerPacket120(ProtocolInfo120::SET_ENTITY_DATA_PACKET, SetEntityDataPacket::class);
		$this->registerPacket120(ProtocolInfo120::SET_ENTITY_MOTION_PACKET, SetEntityMotionPacket::class);
		$this->registerPacket120(ProtocolInfo120::SET_ENTITY_LINK_PACKET, SetEntityLinkPacket::class);
		$this->registerPacket120(ProtocolInfo120::SET_SPAWN_POSITION_PACKET, SetSpawnPositionPacket::class);
		$this->registerPacket120(ProtocolInfo120::ANIMATE_PACKET, AnimatePacket::class);
		$this->registerPacket120(ProtocolInfo120::RESPAWN_PACKET, RespawnPacket::class);
		$this->registerPacket120(ProtocolInfo120::CONTAINER_OPEN_PACKET, ContainerOpenPacket::class);
		$this->registerPacket120(ProtocolInfo120::CONTAINER_CLOSE_PACKET, ContainerClosePacket::class);
		$this->registerPacket120(ProtocolInfo120::CONTAINER_SET_DATA_PACKET, ContainerSetDataPacket::class);
		$this->registerPacket120(ProtocolInfo120::CRAFTING_DATA_PACKET, CraftingDataPacket::class);
		$this->registerPacket120(ProtocolInfo120::CRAFTING_EVENT_PACKET, CraftingEventPacket::class);
		$this->registerPacket120(ProtocolInfo120::ADVENTURE_SETTINGS_PACKET, AdventureSettingsPacket::class);
		$this->registerPacket120(ProtocolInfo120::TILE_ENTITY_DATA_PACKET, TileEntityDataPacket::class);
		$this->registerPacket120(ProtocolInfo120::FULL_CHUNK_DATA_PACKET, FullChunkDataPacket::class);
		$this->registerPacket120(ProtocolInfo120::SET_COMMANDS_ENABLED_PACKET, SetCommandsEnabledPacket::class);
		$this->registerPacket120(ProtocolInfo120::SET_DIFFICULTY_PACKET, SetDifficultyPacket::class);
		$this->registerPacket120(ProtocolInfo120::PLAYER_LIST_PACKET, PlayerListPacket::class);
		$this->registerPacket120(ProtocolInfo120::REQUEST_CHUNK_RADIUS_PACKET, RequestChunkRadiusPacket::class);
		$this->registerPacket120(ProtocolInfo120::CHUNK_RADIUS_UPDATE_PACKET, ChunkRadiusUpdatePacket::class);
		$this->registerPacket120(ProtocolInfo120::AVAILABLE_COMMANDS_PACKET, AvailableCommandsPacket::class);
		$this->registerPacket120(ProtocolInfo120::TRANSFER_PACKET, TransferPacket::class);
		$this->registerPacket120(ProtocolInfo120::CLIENT_TO_SERVER_HANDSHAKE_PACKET, ClientToServerHandshakePacket::class);
		$this->registerPacket120(ProtocolInfo120::RESOURCE_PACK_DATA_INFO_PACKET, ResourcePackDataInfoPacket::class);
		$this->registerPacket120(ProtocolInfo120::RESOURCE_PACKS_INFO_PACKET, ResourcePackDataInfoPacket::class);
		$this->registerPacket120(ProtocolInfo120::RESOURCE_PACKS_CLIENT_RESPONSE_PACKET, ResourcePackClientResponsePacket::class);
		$this->registerPacket120(ProtocolInfo120::RESOURCE_PACK_CHUNK_REQUEST_PACKET, ResourcePackChunkRequestPacket::class);
		$this->registerPacket120(ProtocolInfo120::PLAYER_INPUT_PACKET, PlayerInputPacket::class);
		// new
		$this->registerPacket120(ProtocolInfo120::INVENTORY_TRANSACTION_PACKET, InventoryTransactionPacket::class);
		$this->registerPacket120(ProtocolInfo120::INVENTORY_CONTENT_PACKET, InventoryContentPacket::class);
		$this->registerPacket120(ProtocolInfo120::PLAYER_HOTBAR_PACKET, PlayerHotbarPacket::class);
		$this->registerPacket120(ProtocolInfo120::COMMAND_REQUEST_PACKET, CommandRequestPacket::class);
		$this->registerPacket120(ProtocolInfo120::PLAYER_SKIN_PACKET, PlayerSkinPacket::class);
		$this->registerPacket120(ProtocolInfo120::MODAL_FORM_RESPONSE_PACKET, ModalFormResponsePacket::class);
		$this->registerPacket120(ProtocolInfo120::SERVER_SETTINGS_REQUEST_PACKET, ServerSettingsRequestPacket::class);
		$this->registerPacket120(ProtocolInfo120::PURCHASE_RECEIPT_PACKET, PurchaseReceiptPacket::class);
		$this->registerPacket120(ProtocolInfo120::SUB_CLIENT_LOGIN_PACKET, SubClientLoginPacket::class);		
	}
}