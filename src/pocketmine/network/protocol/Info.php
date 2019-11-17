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
 * Minecraft: PE multiplayer protocol implementation
 */
namespace pocketmine\network\protocol;


interface Info{

	const CURRENT_PROTOCOL = 120;
	const ACCEPTED_PROTOCOLS = [134, 135, 136, 137, 140, 141, 150, 160, 200, 201, 220, 221, 222, 224, 223, 240, 250, 260, 261, 270, 271, 273, 274, 280, 281, 282, 290, 291, 310, 311, 312, 313, 330, 331, 332, 342, 340, 350, 351, 352, 353, 354, 360, 361, 370, 371, 385, 386, 387, 388, 389, 390];
	
	const PROTOCOL_134 = 134; // 1.2.0.20, 1.2.0.22
	const PROTOCOL_135 = 135; // 1.2.0.24, 1.2.0.25
	const PROTOCOL_136 = 136; // 1.2.0.31
	const PROTOCOL_137 = 137; // 1.2.0
	const PROTOCOL_140 = 140; // 1.2.5.11
	const PROTOCOL_141 = 141; // 1.2.5.x
	const PROTOCOL_150 = 150; // 1.2.6
	const PROTOCOL_160 = 160; // 1.2.7
	const PROTOCOL_200 = 200; // 1.2.10
	const PROTOCOL_201 = 201; // 1.2.10.x
	const PROTOCOL_220 = 220; // 1.2.13.5
	const PROTOCOL_221 = 221; // 1.2.13.8
	const PROTOCOL_222 = 222; // 1.2.13.10
	const PROTOCOL_223 = 223; // 1.2.13.54
	const PROTOCOL_224 = 224; // 1.2.13.11
	const PROTOCOL_240 = 240; // 1.2.14.2
	const PROTOCOL_250 = 250; // 1.2.15.1
	const PROTOCOL_260 = 260; // 1.2.20.1, 1.2.20.2
	const PROTOCOL_261 = 261; // 1.4.0
	const PROTOCOL_270 = 270; // 1.5.0.0
	const PROTOCOL_271 = 271; // 1.5.0.0, 1.5.0.4
	const PROTOCOL_273 = 273; // 1.5.0.7
	const PROTOCOL_274 = 274; // 1.5.0.10
	const PROTOCOL_280 = 280; // 1.6.0.1
	const PROTOCOL_281 = 281; // 1.6.0.5
	const PROTOCOL_282 = 282; // 1.6.0.8
	const PROTOCOL_290 = 290; // 1.7.0.2
	const PROTOCOL_291 = 291; // 1.7.0.5
	const PROTOCOL_310 = 310; // 1.8.0.4, 1.8.0.8
	const PROTOCOL_311 = 311; // 1.8.0.9, 1.8.0.10
	const PROTOCOL_312 = 312; // 1.8.0.11
	const PROTOCOL_313 = 313; // 1.8.0 rc1
	const PROTOCOL_330 = 330; // 1.9.0.0
	const PROTOCOL_331 = 331; // 1.9.0.2
	const PROTOCOL_332 = 332; // 1.9.0.3
	const PROTOCOL_340 = 340; // 1.10.0.3
	const PROTOCOL_342 = 342; // 1.10.0
	const PROTOCOL_350 = 350; // 1.11.0.1
	const PROTOCOL_351 = 351; // 1.11.0.3
	const PROTOCOL_352 = 352; // 1.11.0.4
	const PROTOCOL_353 = 353; // 1.11.0.5
	const PROTOCOL_354 = 354; // 1.11.0.7, 1.11.1, 1.11.2, 1.11.3
	const PROTOCOL_360 = 360; // 1.12.0.2
	const PROTOCOL_361 = 361; // 1.12.0.3, 1.12.0.4, 1.12.0.5, 1.12.0.6
	const PROTOCOL_370 = 370; // 1.13.0.1
	const PROTOCOL_371 = 371; // 1.13.0.4, 1.13.0.5, 1.13.0.6
	const PROTOCOL_385 = 385; // 1.13.0.7, 1.13.0.9, 1.13.0.10
	const PROTOCOL_386 = 386; // 1.13.0.12
	const PROTOCOL_387 = 387; // 1.13.0.15
	const PROTOCOL_388 = 388; // 1.13.0.25
	const PROTOCOL_389 = 389; // 1.13.0.18
	const PROTOCOL_390 = 390; // 1.14.0.1
	
	/** OUTDATED (supporting will be removed with next release, may didn't work properly)*/
	const PROTOCOL_120 = 120; // 1.2.0.xx (beta)
//	const PROTOCOL_121 = 121; // 1.2.0.xx (beta)
//	const PROTOCOL_130 = 130; // 1.2.0.xx (beta)
//	const PROTOCOL_131 = 131; // 1.2.0.xx (beta)
//	const PROTOCOL_132 = 132; // 1.2.0.15 (beta)
//	const PROTOCOL_133 = 133; // 1.2.0.18 (beta)

	/**
	 * Minecraft: PE packets
	 */
	const LOGIN_PACKET = 0x01;
	const PLAY_STATUS_PACKET = 0x02;
	const SERVER_TO_CLIENT_HANDSHAKE_PACKET = 0x03;
	const CLIENT_TO_SERVER_HANDSHAKE_PACKET = 0x04;
	const DISCONNECT_PACKET = 0x05;
	const RESOURCE_PACKS_INFO_PACKET = 0x06;
	const RESOURCE_PACKS_STACK_PACKET = 0x07;
	const RESOURCE_PACKS_CLIENT_RESPONSE_PACKET = 0x08;
	const TEXT_PACKET = 0x09;
	const SET_TIME_PACKET = 0x0a;
	const START_GAME_PACKET = 0x0b;
	const ADD_PLAYER_PACKET = 0x0c;
	const ADD_ENTITY_PACKET = 0x0d;
	const REMOVE_ENTITY_PACKET = 0x0e;
	const ADD_ITEM_ENTITY_PACKET = 0x0f;
	// 0x10 - doesn't exists in client
	const TAKE_ITEM_ENTITY_PACKET = 0x11;
	const MOVE_ENTITY_PACKET = 0x12;
	const MOVE_PLAYER_PACKET = 0x13;
	const RIDER_JUMP_PACKET = 0x14;
	const UPDATE_BLOCK_PACKET = 0x15;
	const ADD_PAINTING_PACKET = 0x16;
	const EXPLODE_PACKET = 0x17;
	const LEVEL_SOUND_EVENT_PACKET = 0x18;
	const LEVEL_EVENT_PACKET = 0x19;
	const TILE_EVENT_PACKET = 0x1a;
	const ENTITY_EVENT_PACKET = 0x1b;
	const MOB_EFFECT_PACKET = 0x1c;
	const UPDATE_ATTRIBUTES_PACKET = 0x1d;
	const INVENTORY_TRANSACTION_PACKET = 0x1e;				// NEW
	const MOB_EQUIPMENT_PACKET = 0x1f;
	const MOB_ARMOR_EQUIPMENT_PACKET = 0x20;
	const INTERACT_PACKET = 0x21;
	const BLOCK_PICK_REQUEST_PACKET = 0x22;					// NEW
	const ENTITY_PICK_REQUEST_PACKET = 0x23;				// NEW
	const PLAYER_ACTION_PACKET = 0x24;
	const ENTITY_FALL_PACKET = 0x25;
	const HURT_ARMOR_PACKET = 0x26;
	const SET_ENTITY_DATA_PACKET = 0x27;
	const SET_ENTITY_MOTION_PACKET = 0x28;
	const SET_ENTITY_LINK_PACKET = 0x29;
	const SET_HEALTH_PACKET = 0x2a;
	const SET_SPAWN_POSITION_PACKET = 0x2b;
	const ANIMATE_PACKET = 0x2c;
	const RESPAWN_PACKET = 0x2d;
	const CONTAINER_OPEN_PACKET = 0x2e;
	const CONTAINER_CLOSE_PACKET = 0x2f;
	const PLAYER_HOTBAR_PACKET = 0x30;						// NEW
	const INVENTORY_CONTENT_PACKET = 0x31;					// NEW
	const INVENTORY_SLOT_PACKET = 0x32;						// NEW
	const CONTAINER_SET_DATA_PACKET = 0x33;
	const CRAFTING_DATA_PACKET = 0x34;
	const CRAFTING_EVENT_PACKET = 0x35;
	const GUI_DATA_PICK_ITEM_PACKET = 0x36;					// NEW
	const ADVENTURE_SETTINGS_PACKET = 0x37;
	const TILE_ENTITY_DATA_PACKET = 0x38;
	const PLAYER_INPUT_PACKET = 0x39;
	const FULL_CHUNK_DATA_PACKET = 0x3a;
	const SET_COMMANDS_ENABLED_PACKET = 0x3b;
	const SET_DIFFICULTY_PACKET = 0x3c;
	const CHANGE_DIMENSION_PACKET = 0x3d;
	const SET_PLAYER_GAMETYPE_PACKET = 0x3e;
	const PLAYER_LIST_PACKET = 0x3f;
	const SIMPLE_EVENT_PACKET = 0x40;
	const TELEMETRY_EVENT_PACKET = 0x41;
	const SPAWN_EXPERIENCE_ORB_PACKET = 0x42;
	const CLIENTBOUND_MAP_ITEM_DATA_PACKET = 0x43;
	const MAP_INFO_REQUEST_PACKET = 0x44;
	const REQUEST_CHUNK_RADIUS_PACKET = 0x45;
	const CHUNK_RADIUS_UPDATE_PACKET = 0x46;
	const ITEM_FRAME_DROP_ITEM_PACKET = 0x47;
	const GAME_RULES_CHANGED_PACKET = 0x48;
	const CAMERA_PACKET = 0x49;
	const BOSS_EVENT_PACKET = 0x4a;
	const SHOW_CREDITS_PACKET = 0x4b;
	const AVAILABLE_COMMANDS_PACKET = 0x4c;
	const COMMAND_REQUEST_PACKET = 0x4d;
	const COMMAND_BLOCK_UPDATE_PACKET = 0x4e;
	const COMMAND_OUTPUT_PACKET = 0x4f;						// NEW
	const UPDATE_TRADE_PACKET = 0x50;
	const UPDATE_EQUIPMENT_PACKET = 0x51;
	const RESOURCE_PACK_DATA_INFO_PACKET = 0x52;
	const RESOURCE_PACK_CHUNK_DATA_PACKET = 0x53;
	const RESOURCE_PACK_CHUNK_REQUEST_PACKET = 0x54;
	const TRANSFER_PACKET = 0x55;
	const PLAY_SOUND_PACKET = 0x56;
	const STOP_SOUND_PACKET = 0x57;
	const SET_TITLE_PACKET = 0x58;
	const ADD_BEHAVIOR_TREE_PACKET = 0x59;
	const STRUCTURE_BLOCK_UPDATE_PACKET = 0x5a;
	const SHOW_STORE_OFFER_PACKET = 0x5b;
	const PURCHASE_RECEIPT_PACKET = 0x5c;
	const PLAYER_SKIN_PACKET = 0x5d;						// NEW
	const SUB_CLIENT_LOGIN_PACKET = 0x5e;					// NEW
	const INITIATE_WEB_SOCKET_CONNECTION_PACKET = 0x5f;		// NEW
	const SET_LAST_HURT_BY_PACKET = 0x60;					// NEW
	const BOOK_EDIT_PACKET = 0x61;							// NEW
	const NPC_REQUEST_PACKET = 0x62;						// NEW
	const PHOTO_TRANSFER_PACKET = 0x63;						// NEW
	const MODAL_FORM_REQUEST_PACKET = 0x64;					// NEW
	const MODAL_FORM_RESPONSE_PACKET = 0x65;				// NEW
	const SERVER_SETTINGS_REQUEST_PACKET = 0x66;			// NEW
	const SERVER_SETTINGS_RESPONSE_PACKET = 0x67;			// NEW
	const SHOW_PROFILE_PACKET = 0x68;						// NEW
	const SET_DEFAULT_GAME_TYPE_PACKET = 0x69;				// NEW
	
}











