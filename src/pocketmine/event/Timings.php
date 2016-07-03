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

namespace pocketmine\event;


use pocketmine\entity\Entity;
use pocketmine\plugin\PluginManager;
use pocketmine\scheduler\PluginTask;
use pocketmine\scheduler\TaskHandler;
use pocketmine\tile\Tile;

abstract class Timings{

	/** @var TimingsHandler */
	public static $serverTickTimer;
	/** @var TimingsHandler */
	public static $playerListTimer;
	/** @var TimingsHandler */
	public static $connectionTimer;
	/** @var TimingsHandler */
	public static $tickablesTimer;
	/** @var TimingsHandler */
	public static $schedulerTimer;
	/** @var TimingsHandler */
	public static $chunkIOTickTimer;
	/** @var TimingsHandler */
	public static $timeUpdateTimer;
	/** @var TimingsHandler */
	public static $serverCommandTimer;
	/** @var TimingsHandler */
	public static $worldSaveTimer;
	/** @var TimingsHandler */
	public static $generationTimer;
	/** @var TimingsHandler */
	public static $permissibleCalculationTimer;
	/** @var TimingsHandler */
	public static $permissionDefaultTimer;

	/** @var TimingsHandler */
	public static $entityMoveTimer;
	/** @var TimingsHandler */
	public static $tickEntityTimer;
	/** @var TimingsHandler */
	public static $activatedEntityTimer;
	/** @var TimingsHandler */
	public static $tickTileEntityTimer;

	/** @var TimingsHandler */
	public static $timerEntityBaseTick;
	/** @var TimingsHandler */
	public static $timerEntityAI;
	/** @var TimingsHandler */
	public static $timerEntityAICollision;
	/** @var TimingsHandler */
	public static $timerEntityAIMove;
	/** @var TimingsHandler */
	public static $timerEntityTickRest;

	/** @var TimingsHandler */
	public static $processQueueTimer;
	/** @var TimingsHandler */
	public static $schedulerSyncTimer;

	/** @var TimingsHandler */
	public static $playerCommandTimer;

	/** @var TimingsHandler[] */
	public static $entityTypeTimingMap = [];
	/** @var TimingsHandler[] */
	public static $tileEntityTypeTimingMap = [];
	/** @var TimingsHandler[] */
	public static $pluginTaskTimingMap = [];

	public static function init(){
		if(self::$serverTickTimer instanceof TimingsHandler){
			return;
		}

		self::$serverTickTimer = new TimingsHandler("** Full Server Tick");
		self::$playerListTimer = new TimingsHandler("Player List");
		self::$connectionTimer = new TimingsHandler("Connection Handler");
		self::$tickablesTimer = new TimingsHandler("Tickables");
		self::$schedulerTimer = new TimingsHandler("Scheduler");
		self::$chunkIOTickTimer = new TimingsHandler("ChunkIOTick");
		self::$timeUpdateTimer = new TimingsHandler("Time Update");
		self::$serverCommandTimer = new TimingsHandler("Server Command");
		self::$worldSaveTimer = new TimingsHandler("World Save");
		self::$generationTimer = new TimingsHandler("World Generation");
		self::$permissibleCalculationTimer = new TimingsHandler("Permissible Calculation");
		self::$permissionDefaultTimer = new TimingsHandler("Default Permission Calculation");

		self::$entityMoveTimer = new TimingsHandler("** entityMove");
		self::$tickEntityTimer = new TimingsHandler("** tickEntity");
		self::$activatedEntityTimer = new TimingsHandler("** activatedTickEntity");
		self::$tickTileEntityTimer = new TimingsHandler("** tickTileEntity");

		self::$timerEntityBaseTick = new TimingsHandler("** livingEntityBaseTick");
		self::$timerEntityAI = new TimingsHandler("** livingEntityAI");
		self::$timerEntityAICollision = new TimingsHandler("** livingEntityAICollision");
		self::$timerEntityAIMove = new TimingsHandler("** livingEntityAIMove");
		self::$timerEntityTickRest = new TimingsHandler("** livingEntityTickRest");

		self::$processQueueTimer = new TimingsHandler("processQueue");
		self::$schedulerSyncTimer = new TimingsHandler("** Scheduler - Sync Tasks", PluginManager::$pluginParentTimer);

		self::$playerCommandTimer = new TimingsHandler("** playerCommand");
		
		
		self::$timerBatchPacket = new TimingsHandler("timerBatchPacket");
		self::$timerLoginPacket = new TimingsHandler("timerLoginPacket");		
		self::$timerMovePacket = new TimingsHandler("timerMovePacket");
		self::$timerMobEqipmentPacket = new TimingsHandler("timerMobEqipmentPacket");
		self::$timerUseItemPacket = new TimingsHandler("timerUseItemPacket");
		self::$timerActionPacket = new TimingsHandler("timerActionPacket");
		self::$timerRemoveBlockPacket = new TimingsHandler("timerRemoveBlockPacket");
		self::$timerInteractPacket = new TimingsHandler("timerInteractPacket");
		self::$timerAnimatePacket = new TimingsHandler("timerAnimatePacket");
		self::$timerEntityEventPacket = new TimingsHandler("timerEntityEventPacket");
		self::$timerDropItemPacket = new TimingsHandler("timerDropItemPacket");
		self::$timerTextPacket = new TimingsHandler("timerTextPacket");
		self::$timerContainerClosePacket = new TimingsHandler("timerContainerClosePacket");
		self::$timerCraftingEventPacket = new TimingsHandler("timerCraftingEventPacket");
		self::$timerConteinerSetSlotPacket = new TimingsHandler("timerConteinerSetSlotPacket");
		self::$timerTileEntityPacket = new TimingsHandler("timerTileEntityPacket");
		self::$timerChunkRudiusPacket = new TimingsHandler("timerChunkRudiusPacket");
		
		self::$timerMovePrepare = new TimingsHandler("timerMovePrepare");
		self::$timerMoveSend = new TimingsHandler("timerMoveSend");
		self::$timerMoutionPrepare = new TimingsHandler("timerMoutionPrepare");
		self::$timerMoutionSend = new TimingsHandler("timerMoutionSend");

	}	
	
	public static $timerBatchPacket;
	public static $timerLoginPacket;	
	public static $timerMovePacket;
	public static $timerMobEqipmentPacket;
	public static $timerUseItemPacket;
	public static $timerActionPacket;
	public static $timerRemoveBlockPacket;
	public static $timerInteractPacket;
	public static $timerAnimatePacket;
	public static $timerEntityEventPacket;
	public static $timerDropItemPacket;
	public static $timerTextPacket;
	public static $timerContainerClosePacket;
	public static $timerCraftingEventPacket;
	public static $timerConteinerSetSlotPacket;
	public static $timerTileEntityPacket;
	public static $timerChunkRudiusPacket;
	
	public static $timerMovePrepare;
	public static $timerMoveSend;
	public static $timerMoutionPrepare;
	public static $timerMoutionSend;

	/**
	 * @param TaskHandler $task
	 * @param             $period
	 *
	 * @return TimingsHandler
	 */
	public static function getPluginTaskTimings(TaskHandler $task, $period){
		$ftask = $task->getTask();
		if($ftask instanceof PluginTask and $ftask->getOwner() !== null){
			$plugin = $ftask->getOwner()->getDescription()->getFullName();
		}elseif($task->timingName !== null){
			$plugin = "Scheduler";
		}else{
			$plugin = "Unknown";
		}

		$taskname = $task->getTaskName();

		$name = "Task: " . $plugin . " Runnable: " . $taskname;

		if($period > 0){
			$name .= "(interval:" . $period . ")";
		}else{
			$name .= "(Single)";
		}

		if(!isset(self::$pluginTaskTimingMap[$name])){
			self::$pluginTaskTimingMap[$name] = new TimingsHandler($name, self::$schedulerSyncTimer);
		}

		return self::$pluginTaskTimingMap[$name];
	}

	/**
	 * @param Entity $entity
	 *
	 * @return TimingsHandler
	 */
	public static function getEntityTimings(Entity $entity){
		$entityType = (new \ReflectionClass($entity))->getShortName();
		if(!isset(self::$entityTypeTimingMap[$entityType])){
			self::$entityTypeTimingMap[$entityType] = new TimingsHandler("** tickEntity - " . $entityType, self::$activatedEntityTimer);
		}

		return self::$entityTypeTimingMap[$entityType];
	}

	/**
	 * @param Tile $tile
	 *
	 * @return TimingsHandler
	 */
	public static function getTileEntityTimings(Tile $tile){
		$tileType = (new \ReflectionClass($tile))->getShortName();
		if(!isset(self::$tileEntityTypeTimingMap[$tileType])){
			self::$tileEntityTypeTimingMap[$tileType] = new TimingsHandler("** tickTileEntity - " . $tileType, self::$tickTileEntityTimer);
		}

		return self::$tileEntityTypeTimingMap[$tileType];
	}

}