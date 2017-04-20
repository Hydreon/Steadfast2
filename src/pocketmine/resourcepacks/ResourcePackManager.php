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


namespace pocketmine\resourcepacks;


use pocketmine\Server;
use pocketmine\utils\Config;

class ResourcePackManager{

	/** @var Server */
	private $server;

	/** @var string */
	private $path;

	/** @var Config */
	private $resourcePacksConfig;

	/** @var bool */
	private $serverForceResources = false;

	/** @var ResourcePack[] */
	private $resourcePacks = [];

	/** @var ResourcePack[] */
	private $uuidList = [];

	/**
	 * @param Server $server
	 * @param string $path Path to resource-packs directory.
	 */
	public function __construct(Server $server, string $path){
		$this->server = $server;
		$this->path = $path;

		if(!file_exists($this->path)){
			$this->server->getLogger()->debug("Resource packs path $path does not exist, creating directory");
			mkdir($this->path);
		}elseif(!is_dir($this->path)){
			throw new \InvalidArgumentException("Resource packs path $path exists and is not a directory");
		}

		if(!file_exists($this->path . "resource_packs.yml")){
			file_put_contents($this->path . "resource_packs.yml", file_get_contents($this->server->getFilePath() . "src/pocketmine/resources/resource_packs.yml"));
		}

		$this->resourcePacksConfig = new Config($this->path . "resource_packs.yml", Config::YAML, []);

		$this->serverForceResources = (bool) $this->resourcePacksConfig->get("force_resources", false);

		$this->server->getLogger()->info("Loading resource packs...");

		foreach($this->resourcePacksConfig->get("resource_stack", []) as $pos => $pack){
			try{
				$packPath = $this->path . DIRECTORY_SEPARATOR . $pack;
				if(file_exists($packPath)){
					$newPack = null;
					//Detect the type of resource pack.
					if(is_dir($packPath)){
						$this->server->getLogger()->warning("Skipped resource entry $pack due to directory resource packs currently unsupported");
					}else{
						$info = new \SplFileInfo($packPath);
						switch($info->getExtension()){
							case "zip":
								$newPack = new ZippedResourcePack($packPath);
								break;
							default:
								$this->server->getLogger()->warning("Skipped resource entry $pack due to format not recognized");
								break;
						}
					}

					if($newPack instanceof ResourcePack){
						$this->resourcePacks[] = $newPack;
						$this->uuidList[$newPack->getPackId()] = $newPack;
					}
				}else{
					$this->server->getLogger()->warning("Skipped resource entry $pack due to file or directory not found");
				}
			}catch(\Throwable $e){
				$this->server->getLogger()->logException($e);
			}
		}

		$this->server->getLogger()->debug("Successfully loaded " . count($this->resourcePacks) . " resource packs");
	}

	/**
	 * Returns whether players must accept resource packs in order to join.
	 * @return bool
	 */
	public function resourcePacksRequired() : bool{
		return $this->serverForceResources;
	}

	/**
	 * Returns an array of resource packs in use, sorted in order of priority.
	 * @return ResourcePack[]
	 */
	public function getResourceStack() : array{
		return $this->resourcePacks;
	}

	/**
	 * Returns the resource pack matching the specified UUID string, or null if the ID was not recognized.
	 *
	 * @param string $id
	 * @return ResourcePack|null
	 */
	public function getPackById(string $id){
		return $this->uuidList[$id] ?? null;
	}

	/**
	 * Returns an array of pack IDs for packs currently in use.
	 * @return string[]
	 */
	public function getPackIdList() : array{
		return array_keys($this->uuidList);
	}
}