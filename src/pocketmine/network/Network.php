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
use pocketmine\network\protocol\ContainerClosePacket;
use pocketmine\network\protocol\ContainerOpenPacket;
use pocketmine\network\protocol\ContainerSetDataPacket;
use pocketmine\network\protocol\CraftingDataPacket;
use pocketmine\network\protocol\CraftingEventPacket;
use pocketmine\network\protocol\DataPacket;
use pocketmine\network\protocol\FullChunkDataPacket;
use pocketmine\network\protocol\Info;
use pocketmine\network\protocol\MapInfoRequestPacket;
use pocketmine\network\protocol\SetEntityLinkPacket;
use pocketmine\network\protocol\SpawnExperienceOrbPacket;
use pocketmine\network\protocol\TileEntityDataPacket;
use pocketmine\network\protocol\EntityEventPacket;
use pocketmine\network\protocol\ExplodePacket;
use pocketmine\network\protocol\HurtArmorPacket;
use pocketmine\network\protocol\Info120 as ProtocolInfo120;
use pocketmine\network\protocol\Info310 as ProtocolInfo310;
use pocketmine\network\protocol\Info331 as ProtocolInfo331;
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
use pocketmine\network\protocol\PlayerListPacket;
use pocketmine\network\protocol\v120\PlayerSkinPacket;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\MainLogger;
use pocketmine\network\protocol\ChunkRadiusUpdatePacket;
use pocketmine\network\protocol\RequestChunkRadiusPacket;
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
use pocketmine\network\protocol\v310\AvailableEntityIdentifiersPacket;
use pocketmine\network\protocol\v310\NetworkChunkPublisherUpdatePacket;
use pocketmine\network\protocol\v310\SpawnParticleEffectPacket;

class Network {	
	
	public static $BATCH_THRESHOLD = 512;
	
	/** @var \SplFixedArray */
	private $packetPool120;
	/** @var \SplFixedArray */
	private $packetPool310;
	/** @var \SplFixedArray */
	private $packetPool331;

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
		$this->registerPackets120();
		$this->registerPackets310();
		$this->registerPackets331();
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
	public function registerPacket120($id, $class){
		$this->packetPool120[$id] = new $class;
	}
	
	/**
	 * @param int        $id 0-255
	 * @param DataPacket $class
	 */
	public function registerPacket310($id, $class){
		$this->packetPool310[$id] = new $class;
	}
	
	/**
	 * @param int        $id 0-255
	 * @param DataPacket $class
	 */
	public function registerPacket331($id, $class){
		$this->packetPool331[$id] = new $class;
	}
	
	public function getServer(){
		return $this->server;
	}

	/**
	 * @param $id
	 *
	 * @return DataPacket
	 */
	public function getPacket($id, $playerProtocol){
		/** @var DataPacket $class */
		switch ($playerProtocol) {
			case Info::PROTOCOL_331:
			case Info::PROTOCOL_332:
			case Info::PROTOCOL_340:
			case Info::PROTOCOL_342:
			case Info::PROTOCOL_350:
			case Info::PROTOCOL_351:
			case Info::PROTOCOL_354:
			case Info::PROTOCOL_360:
			case Info::PROTOCOL_361:
			case Info::PROTOCOL_370:
			case Info::PROTOCOL_385:
			case Info::PROTOCOL_386:
			case Info::PROTOCOL_389:
			case Info::PROTOCOL_392:
			case Info::PROTOCOL_393:
			case Info::PROTOCOL_400:
				$class = $this->packetPool331[$id];
				break;
			case Info::PROTOCOL_310:
			case Info::PROTOCOL_311:
			case Info::PROTOCOL_330:
				$class = $this->packetPool310[$id];
				break;
			default:
				$class = $this->packetPool120[$id];
				break;
		}
		if($class !== null){
			return clone $class;
		}
		return null;
	}
	
	public static function getChunkPacketProtocol($playerProtocol){
		switch ($playerProtocol) {
			case Info::PROTOCOL_400:
			case Info::PROTOCOL_393:
			case Info::PROTOCOL_392:
			case Info::PROTOCOL_389:
			case Info::PROTOCOL_386:
			case Info::PROTOCOL_385:
			case Info::PROTOCOL_370:
			case Info::PROTOCOL_361:
			case Info::PROTOCOL_360:
				return Info::PROTOCOL_360;
			case Info::PROTOCOL_354:
			case Info::PROTOCOL_351:
			case Info::PROTOCOL_350:
			case Info::PROTOCOL_342:
			case Info::PROTOCOL_340:
			case Info::PROTOCOL_332:
			case Info::PROTOCOL_331:
			case Info::PROTOCOL_330:
			case Info::PROTOCOL_311:
			case Info::PROTOCOL_310:
			case Info::PROTOCOL_290:
			case Info::PROTOCOL_282:
			case Info::PROTOCOL_280:
				return Info::PROTOCOL_280;
			default:
				return Info::PROTOCOL_120;
		}
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
	
	private function registerPackets120() {
		$this->packetPool120 = new \SplFixedArray(256);
		$this->registerPacket120(ProtocolInfo120::LOGIN_PACKET, LoginPacket::class);
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
		$this->registerPacket120(ProtocolInfo120::MAP_INFO_REQUEST_PACKET, MapInfoRequestPacket::class);
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
		$this->registerPacket120(ProtocolInfo120::SPAWN_EXPERIENCE_ORB_PACKET, SpawnExperienceOrbPacket::class);
	}
	
	private function registerPackets310() {
		$this->packetPool310 = new \SplFixedArray(256);
		$this->registerPacket310(ProtocolInfo310::LOGIN_PACKET, LoginPacket::class);
		$this->registerPacket310(ProtocolInfo310::PLAY_STATUS_PACKET, PlayStatusPacket::class);
		$this->registerPacket310(ProtocolInfo310::DISCONNECT_PACKET, DisconnectPacket::class);
		$this->registerPacket310(ProtocolInfo310::TEXT_PACKET, TextPacket::class);
		$this->registerPacket310(ProtocolInfo310::SET_TIME_PACKET, SetTimePacket::class);
		$this->registerPacket310(ProtocolInfo310::START_GAME_PACKET, StartGamePacket::class);
		$this->registerPacket310(ProtocolInfo310::ADD_PLAYER_PACKET, AddPlayerPacket::class);
		$this->registerPacket310(ProtocolInfo310::ADD_ENTITY_PACKET, AddEntityPacket::class);
		$this->registerPacket310(ProtocolInfo310::REMOVE_ENTITY_PACKET, RemoveEntityPacket::class);
		$this->registerPacket310(ProtocolInfo310::ADD_ITEM_ENTITY_PACKET, AddItemEntityPacket::class);
		$this->registerPacket310(ProtocolInfo310::TAKE_ITEM_ENTITY_PACKET, TakeItemEntityPacket::class);
		$this->registerPacket310(ProtocolInfo310::MOVE_ENTITY_PACKET, MoveEntityPacket::class);
		$this->registerPacket310(ProtocolInfo310::MOVE_PLAYER_PACKET, MovePlayerPacket::class);
		$this->registerPacket310(ProtocolInfo310::UPDATE_BLOCK_PACKET, UpdateBlockPacket::class);
		$this->registerPacket310(ProtocolInfo310::ADD_PAINTING_PACKET, AddPaintingPacket::class);
		$this->registerPacket310(ProtocolInfo310::EXPLODE_PACKET, ExplodePacket::class);
		$this->registerPacket310(ProtocolInfo310::LEVEL_EVENT_PACKET, LevelEventPacket::class);
		$this->registerPacket310(ProtocolInfo310::TILE_EVENT_PACKET, TileEventPacket::class);
		$this->registerPacket310(ProtocolInfo310::ENTITY_EVENT_PACKET, EntityEventPacket::class);
		$this->registerPacket310(ProtocolInfo310::MOB_EQUIPMENT_PACKET, MobEquipmentPacket::class);
		$this->registerPacket310(ProtocolInfo310::MOB_ARMOR_EQUIPMENT_PACKET, MobArmorEquipmentPacket::class);
		$this->registerPacket310(ProtocolInfo310::INTERACT_PACKET, InteractPacket::class);
		$this->registerPacket310(ProtocolInfo310::PLAYER_ACTION_PACKET, PlayerActionPacket::class);
		$this->registerPacket310(ProtocolInfo310::HURT_ARMOR_PACKET, HurtArmorPacket::class);
		$this->registerPacket310(ProtocolInfo310::SET_ENTITY_DATA_PACKET, SetEntityDataPacket::class);
		$this->registerPacket310(ProtocolInfo310::SET_ENTITY_MOTION_PACKET, SetEntityMotionPacket::class);
		$this->registerPacket310(ProtocolInfo310::SET_ENTITY_LINK_PACKET, SetEntityLinkPacket::class);
		$this->registerPacket310(ProtocolInfo310::SET_SPAWN_POSITION_PACKET, SetSpawnPositionPacket::class);
		$this->registerPacket310(ProtocolInfo310::ANIMATE_PACKET, AnimatePacket::class);
		$this->registerPacket310(ProtocolInfo310::RESPAWN_PACKET, RespawnPacket::class);
		$this->registerPacket310(ProtocolInfo310::CONTAINER_OPEN_PACKET, ContainerOpenPacket::class);
		$this->registerPacket310(ProtocolInfo310::CONTAINER_CLOSE_PACKET, ContainerClosePacket::class);
		$this->registerPacket310(ProtocolInfo310::CONTAINER_SET_DATA_PACKET, ContainerSetDataPacket::class);
		$this->registerPacket310(ProtocolInfo310::CRAFTING_DATA_PACKET, CraftingDataPacket::class);
		$this->registerPacket310(ProtocolInfo310::CRAFTING_EVENT_PACKET, CraftingEventPacket::class);
		$this->registerPacket310(ProtocolInfo310::ADVENTURE_SETTINGS_PACKET, AdventureSettingsPacket::class);
		$this->registerPacket310(ProtocolInfo310::TILE_ENTITY_DATA_PACKET, TileEntityDataPacket::class);
		$this->registerPacket310(ProtocolInfo310::FULL_CHUNK_DATA_PACKET, FullChunkDataPacket::class);
		$this->registerPacket310(ProtocolInfo310::SET_COMMANDS_ENABLED_PACKET, SetCommandsEnabledPacket::class);
		$this->registerPacket310(ProtocolInfo310::SET_DIFFICULTY_PACKET, SetDifficultyPacket::class);
		$this->registerPacket310(ProtocolInfo310::PLAYER_LIST_PACKET, PlayerListPacket::class);
		$this->registerPacket310(ProtocolInfo310::REQUEST_CHUNK_RADIUS_PACKET, RequestChunkRadiusPacket::class);
		$this->registerPacket310(ProtocolInfo310::CHUNK_RADIUS_UPDATE_PACKET, ChunkRadiusUpdatePacket::class);
		$this->registerPacket310(ProtocolInfo310::AVAILABLE_COMMANDS_PACKET, AvailableCommandsPacket::class);
		$this->registerPacket310(ProtocolInfo310::TRANSFER_PACKET, TransferPacket::class);
		$this->registerPacket310(ProtocolInfo310::CLIENT_TO_SERVER_HANDSHAKE_PACKET, ClientToServerHandshakePacket::class);
		$this->registerPacket310(ProtocolInfo310::RESOURCE_PACK_DATA_INFO_PACKET, ResourcePackDataInfoPacket::class);
		$this->registerPacket310(ProtocolInfo310::RESOURCE_PACKS_INFO_PACKET, ResourcePackDataInfoPacket::class);
		$this->registerPacket310(ProtocolInfo310::RESOURCE_PACKS_CLIENT_RESPONSE_PACKET, ResourcePackClientResponsePacket::class);
		$this->registerPacket310(ProtocolInfo310::RESOURCE_PACK_CHUNK_REQUEST_PACKET, ResourcePackChunkRequestPacket::class);
		$this->registerPacket310(ProtocolInfo310::PLAYER_INPUT_PACKET, PlayerInputPacket::class);
		$this->registerPacket310(ProtocolInfo310::MAP_INFO_REQUEST_PACKET, MapInfoRequestPacket::class);
		$this->registerPacket310(ProtocolInfo310::INVENTORY_TRANSACTION_PACKET, InventoryTransactionPacket::class);
		$this->registerPacket310(ProtocolInfo310::INVENTORY_CONTENT_PACKET, InventoryContentPacket::class);
		$this->registerPacket310(ProtocolInfo310::PLAYER_HOTBAR_PACKET, PlayerHotbarPacket::class);
		$this->registerPacket310(ProtocolInfo310::COMMAND_REQUEST_PACKET, CommandRequestPacket::class);
		$this->registerPacket310(ProtocolInfo310::PLAYER_SKIN_PACKET, PlayerSkinPacket::class);
		$this->registerPacket310(ProtocolInfo310::MODAL_FORM_RESPONSE_PACKET, ModalFormResponsePacket::class);
		$this->registerPacket310(ProtocolInfo310::SERVER_SETTINGS_REQUEST_PACKET, ServerSettingsRequestPacket::class);
		$this->registerPacket310(ProtocolInfo310::PURCHASE_RECEIPT_PACKET, PurchaseReceiptPacket::class);
		$this->registerPacket310(ProtocolInfo310::SUB_CLIENT_LOGIN_PACKET, SubClientLoginPacket::class);			
		$this->registerPacket310(ProtocolInfo310::AVAILABLE_ENTITY_IDENTIFIERS_PACKET, AvailableEntityIdentifiersPacket::class);	
		$this->registerPacket310(ProtocolInfo310::LEVEL_SOUND_EVENT_PACKET, LevelSoundEventPacket::class);	
		$this->registerPacket310(ProtocolInfo310::NETWORK_CHUNK_PUBLISHER_UPDATE_PACKET, NetworkChunkPublisherUpdatePacket::class);	
		$this->registerPacket310(ProtocolInfo310::SPAWN_PARTICLE_EFFECT_PACKET, SpawnParticleEffectPacket::class);
		$this->registerPacket310(ProtocolInfo310::SPAWN_EXPERIENCE_ORB_PACKET, SpawnExperienceOrbPacket::class);

	}
	
	private function registerPackets331() {
		$this->packetPool331 = new \SplFixedArray(256);
		$this->registerPacket331(ProtocolInfo331::LOGIN_PACKET, LoginPacket::class);
		$this->registerPacket331(ProtocolInfo331::PLAY_STATUS_PACKET, PlayStatusPacket::class);
		$this->registerPacket331(ProtocolInfo331::DISCONNECT_PACKET, DisconnectPacket::class);
		$this->registerPacket331(ProtocolInfo331::TEXT_PACKET, TextPacket::class);
		$this->registerPacket331(ProtocolInfo331::SET_TIME_PACKET, SetTimePacket::class);
		$this->registerPacket331(ProtocolInfo331::START_GAME_PACKET, StartGamePacket::class);
		$this->registerPacket331(ProtocolInfo331::ADD_PLAYER_PACKET, AddPlayerPacket::class);
		$this->registerPacket331(ProtocolInfo331::ADD_ENTITY_PACKET, AddEntityPacket::class);
		$this->registerPacket331(ProtocolInfo331::REMOVE_ENTITY_PACKET, RemoveEntityPacket::class);
		$this->registerPacket331(ProtocolInfo331::ADD_ITEM_ENTITY_PACKET, AddItemEntityPacket::class);
		$this->registerPacket331(ProtocolInfo331::TAKE_ITEM_ENTITY_PACKET, TakeItemEntityPacket::class);
		$this->registerPacket331(ProtocolInfo331::MOVE_ENTITY_PACKET, MoveEntityPacket::class);
		$this->registerPacket331(ProtocolInfo331::MOVE_PLAYER_PACKET, MovePlayerPacket::class);
		$this->registerPacket331(ProtocolInfo331::UPDATE_BLOCK_PACKET, UpdateBlockPacket::class);
		$this->registerPacket331(ProtocolInfo331::ADD_PAINTING_PACKET, AddPaintingPacket::class);
		$this->registerPacket331(ProtocolInfo331::EXPLODE_PACKET, ExplodePacket::class);
		$this->registerPacket331(ProtocolInfo331::LEVEL_EVENT_PACKET, LevelEventPacket::class);
		$this->registerPacket331(ProtocolInfo331::TILE_EVENT_PACKET, TileEventPacket::class);
		$this->registerPacket331(ProtocolInfo331::ENTITY_EVENT_PACKET, EntityEventPacket::class);
		$this->registerPacket331(ProtocolInfo331::MOB_EQUIPMENT_PACKET, MobEquipmentPacket::class);
		$this->registerPacket331(ProtocolInfo331::MOB_ARMOR_EQUIPMENT_PACKET, MobArmorEquipmentPacket::class);
		$this->registerPacket331(ProtocolInfo331::INTERACT_PACKET, InteractPacket::class);
		$this->registerPacket331(ProtocolInfo331::PLAYER_ACTION_PACKET, PlayerActionPacket::class);
		$this->registerPacket331(ProtocolInfo331::HURT_ARMOR_PACKET, HurtArmorPacket::class);
		$this->registerPacket331(ProtocolInfo331::SET_ENTITY_DATA_PACKET, SetEntityDataPacket::class);
		$this->registerPacket331(ProtocolInfo331::SET_ENTITY_MOTION_PACKET, SetEntityMotionPacket::class);
		$this->registerPacket331(ProtocolInfo331::SET_ENTITY_LINK_PACKET, SetEntityLinkPacket::class);
		$this->registerPacket331(ProtocolInfo331::SET_SPAWN_POSITION_PACKET, SetSpawnPositionPacket::class);
		$this->registerPacket331(ProtocolInfo331::ANIMATE_PACKET, AnimatePacket::class);
		$this->registerPacket331(ProtocolInfo331::RESPAWN_PACKET, RespawnPacket::class);
		$this->registerPacket331(ProtocolInfo331::CONTAINER_OPEN_PACKET, ContainerOpenPacket::class);
		$this->registerPacket331(ProtocolInfo331::CONTAINER_CLOSE_PACKET, ContainerClosePacket::class);
		$this->registerPacket331(ProtocolInfo331::CONTAINER_SET_DATA_PACKET, ContainerSetDataPacket::class);
		$this->registerPacket331(ProtocolInfo331::CRAFTING_DATA_PACKET, CraftingDataPacket::class);
		$this->registerPacket331(ProtocolInfo331::CRAFTING_EVENT_PACKET, CraftingEventPacket::class);
		$this->registerPacket331(ProtocolInfo331::ADVENTURE_SETTINGS_PACKET, AdventureSettingsPacket::class);
		$this->registerPacket331(ProtocolInfo331::TILE_ENTITY_DATA_PACKET, TileEntityDataPacket::class);
		$this->registerPacket331(ProtocolInfo331::FULL_CHUNK_DATA_PACKET, FullChunkDataPacket::class);
		$this->registerPacket331(ProtocolInfo331::SET_COMMANDS_ENABLED_PACKET, SetCommandsEnabledPacket::class);
		$this->registerPacket331(ProtocolInfo331::SET_DIFFICULTY_PACKET, SetDifficultyPacket::class);
		$this->registerPacket331(ProtocolInfo331::PLAYER_LIST_PACKET, PlayerListPacket::class);
		$this->registerPacket331(ProtocolInfo331::REQUEST_CHUNK_RADIUS_PACKET, RequestChunkRadiusPacket::class);
		$this->registerPacket331(ProtocolInfo331::CHUNK_RADIUS_UPDATE_PACKET, ChunkRadiusUpdatePacket::class);
		$this->registerPacket331(ProtocolInfo331::AVAILABLE_COMMANDS_PACKET, AvailableCommandsPacket::class);
		$this->registerPacket331(ProtocolInfo331::TRANSFER_PACKET, TransferPacket::class);
		$this->registerPacket331(ProtocolInfo331::CLIENT_TO_SERVER_HANDSHAKE_PACKET, ClientToServerHandshakePacket::class);
		$this->registerPacket331(ProtocolInfo331::RESOURCE_PACK_DATA_INFO_PACKET, ResourcePackDataInfoPacket::class);
		$this->registerPacket331(ProtocolInfo331::RESOURCE_PACKS_INFO_PACKET, ResourcePackDataInfoPacket::class);
		$this->registerPacket331(ProtocolInfo331::RESOURCE_PACKS_CLIENT_RESPONSE_PACKET, ResourcePackClientResponsePacket::class);
		$this->registerPacket331(ProtocolInfo331::RESOURCE_PACK_CHUNK_REQUEST_PACKET, ResourcePackChunkRequestPacket::class);
		$this->registerPacket331(ProtocolInfo331::PLAYER_INPUT_PACKET, PlayerInputPacket::class);
		$this->registerPacket331(ProtocolInfo331::MAP_INFO_REQUEST_PACKET, MapInfoRequestPacket::class);
		$this->registerPacket331(ProtocolInfo331::INVENTORY_TRANSACTION_PACKET, InventoryTransactionPacket::class);
		$this->registerPacket331(ProtocolInfo331::INVENTORY_CONTENT_PACKET, InventoryContentPacket::class);
		$this->registerPacket331(ProtocolInfo331::PLAYER_HOTBAR_PACKET, PlayerHotbarPacket::class);
		$this->registerPacket331(ProtocolInfo331::COMMAND_REQUEST_PACKET, CommandRequestPacket::class);
		$this->registerPacket331(ProtocolInfo331::PLAYER_SKIN_PACKET, PlayerSkinPacket::class);
		$this->registerPacket331(ProtocolInfo331::MODAL_FORM_RESPONSE_PACKET, ModalFormResponsePacket::class);
		$this->registerPacket331(ProtocolInfo331::SERVER_SETTINGS_REQUEST_PACKET, ServerSettingsRequestPacket::class);
		$this->registerPacket331(ProtocolInfo331::PURCHASE_RECEIPT_PACKET, PurchaseReceiptPacket::class);
		$this->registerPacket331(ProtocolInfo331::SUB_CLIENT_LOGIN_PACKET, SubClientLoginPacket::class);			
		$this->registerPacket331(ProtocolInfo331::AVAILABLE_ENTITY_IDENTIFIERS_PACKET, AvailableEntityIdentifiersPacket::class);	
		$this->registerPacket331(ProtocolInfo331::LEVEL_SOUND_EVENT_PACKET, LevelSoundEventPacket::class);	
		$this->registerPacket331(ProtocolInfo331::NETWORK_CHUNK_PUBLISHER_UPDATE_PACKET, NetworkChunkPublisherUpdatePacket::class);	
		$this->registerPacket331(ProtocolInfo331::SPAWN_PARTICLE_EFFECT_PACKET, SpawnParticleEffectPacket::class);
		$this->registerPacket331(ProtocolInfo331::SPAWN_EXPERIENCE_ORB_PACKET, SpawnExperienceOrbPacket::class);
	}
}
