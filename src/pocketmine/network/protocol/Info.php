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

	/**
	 * Minecraft: PE protocol versions
	 */
	const CURRENT_PROTOCOL = 101;
//	const NEWEST_PROTOCOL = 82;
//	const OLDEST_PROTOCOL = 81;
	const ACCEPTED_PROTOCOLS = [101, 102, 105, 106, 107, 110, 111, 112, 113, 134, 135, 136, 137, 140, 141, 150, 160, 200, 201, 220, 221, 222, 224, 223, 240, 250, 260, 270, 271];
	
	const BASE_PROTOCOL = 101;
	/** RELEASE 1.0.x, 1.1.x */
	const PROTOCOL_105 = 105;
	const PROTOCOL_106 = 106;
	const PROTOCOL_107 = 107;
	const PROTOCOL_110 = 110;
	const PROTOCOL_111 = 111;
	const PROTOCOL_112 = 112;
	const PROTOCOL_113 = 113;
	/** 1.2 BETAS */
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
	const PROTOCOL_270 = 270; // 1.5.0.0
	const PROTOCOL_271 = 271; // 1.5.0.0
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
	const BATCH_PACKET = 0x06;
	const RESOURCE_PACKS_INFO_PACKET = 0x07;
	const RESOURCE_PACKS_STACK_PACKET = 0x08;
	const RESOURCE_PACKS_CLIENT_RESPONSE_PACKET = 0x09;
	const TEXT_PACKET = 0x0a;
	const SET_TIME_PACKET = 0x0b;
	const START_GAME_PACKET = 0x0c;
	const ADD_PLAYER_PACKET = 0x0d;
	const ADD_ENTITY_PACKET = 0x0e;
	const REMOVE_ENTITY_PACKET = 0x0f;
	const ADD_ITEM_ENTITY_PACKET = 0x10;
//	const ADD_HANGING_ENTITY_PACKET = 0x11;
	const TAKE_ITEM_ENTITY_PACKET = 0x12;
	const MOVE_ENTITY_PACKET = 0x13;
	const MOVE_PLAYER_PACKET = 0x14;
//	const RIDER_JUMP_PACKET = 0x15;
	const REMOVE_BLOCK_PACKET = 0x16;
	const UPDATE_BLOCK_PACKET = 0x17;	
	const ADD_PAINTING_PACKET = 0x18;
	const EXPLODE_PACKET = 0x19;
	const LEVEL_SOUND_EVENT_PACKET = 0x1a;
	const LEVEL_EVENT_PACKET = 0x1b;	
	const TILE_EVENT_PACKET = 0x1c;
	const ENTITY_EVENT_PACKET = 0x1d;
	const MOB_EFFECT_PACKET = 0x1e;
	const UPDATE_ATTRIBUTES_PACKET = 0x1f;	
	const MOB_EQUIPMENT_PACKET = 0x20;
	const MOB_ARMOR_EQUIPMENT_PACKET = 0x21;
	const INTERACT_PACKET = 0x22;
	const USE_ITEM_PACKET = 0x23;
	const PLAYER_ACTION_PACKET = 0x24;
//	const PLAYER_FALL = 0x25;
	const HURT_ARMOR_PACKET = 0x26;	
	const SET_ENTITY_DATA_PACKET = 0x27;
	const SET_ENTITY_MOTION_PACKET = 0x28;
	const SET_ENTITY_LINK_PACKET = 0x29;
	const SET_HEALTH_PACKET = 0x2a;
	const SET_SPAWN_POSITION_PACKET = 0x2b;
	const ANIMATE_PACKET = 0x2c;
	const RESPAWN_PACKET = 0x2d;
	const DROP_ITEM_PACKET = 0x2e;
//	const INVENTORY_ACTION_PACKET = 0x2f;
	const CONTAINER_OPEN_PACKET = 0x30;
	const CONTAINER_CLOSE_PACKET = 0x31;
	const CONTAINER_SET_SLOT_PACKET = 0x32;
	const CONTAINER_SET_DATA_PACKET = 0x33;
	const CONTAINER_SET_CONTENT_PACKET = 0x34;
	const CRAFTING_DATA_PACKET = 0x35;
	const CRAFTING_EVENT_PACKET = 0x36;
	const ADVENTURE_SETTINGS_PACKET = 0x37;
	const TILE_ENTITY_DATA_PACKET = 0x38;
	const PLAYER_INPUT_PACKET = 0x39;
	const FULL_CHUNK_DATA_PACKET = 0x3a;
	const SET_COMMANDS_ENABLED_PACKET = 0x3b;
	const SET_DIFFICULTY_PACKET = 0x3c;
//	const CHANGE_DIMENSION_PACKET = 0x3d;
	const SET_PLAYER_GAMETYPE_PACKET = 0x3e;
	const PLAYER_LIST_PACKET = 0x3f;
//	const TELEMETRY_EVENT_PACKET = 0x40; // ??? EVENT_PACKET in 0.15.90.8
//	const SPAWN_EXPERIENCE_ORB_PACKET = 0x41;
	const CLIENTBOUND_MAP_ITEM_DATA_PACKET = 0x42;
	const MAP_INFO_REQUEST_PACKET = 0x43;
	const REQUEST_CHUNK_RADIUS_PACKET = 0x44;
	const CHUNK_RADIUS_UPDATE_PACKET = 0x45;
//	const ITEM_FRAME_DROP_ITEM_PACKET = 0x46;
//	const REPLACE_SELECTED_ITEM_PACKET = 0x47;
//	const GAME_RULES_CHANGED_PACKET = 0x48;
//	const CAMERA_PACKET = 0x49;
//	const ADD_ITEM_PACKET = 0x4a;
//	const BOSS_EVENT_PACKET = 0x4b;
//	const SHOW_CREDITS_PACKET = 0x4c;
	const AVAILABLE_COMMANDS_PACKET = 0x4d;
	const COMMAND_STEP_PACKET = 0x4e;
	const RESOURCE_PACK_DATA_INFO_PACKET = 0x4f;
	const RESOURCE_PACK_CHUNK_DATA_PACKET = 0x50;
	const RESOURCE_PACK_CHUNK_REQUEST_PACKET = 0x51;
	const TRANSFER_PACKET = 0x53;
	
}











