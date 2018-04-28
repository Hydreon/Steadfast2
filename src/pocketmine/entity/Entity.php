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
 * All the entity classes
 */
namespace pocketmine\entity;

use pocketmine\block\Block;
use pocketmine\block\Water;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDespawnEvent;
use pocketmine\event\entity\EntityLevelChangeEvent;
use pocketmine\event\entity\EntityMotionEvent;
use pocketmine\event\entity\EntityRegainHealthEvent;
use pocketmine\event\entity\EntitySpawnEvent;
use pocketmine\event\entity\EntityTeleportEvent;
use pocketmine\event\Timings;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\Tool;
use pocketmine\level\format\Chunk;
use pocketmine\level\format\FullChunk;
use pocketmine\level\Level;
use pocketmine\level\Location;
use pocketmine\level\Position;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Math;
use pocketmine\math\Vector3;
use pocketmine\metadata\Metadatable;
use pocketmine\metadata\MetadataValue;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\nbt\tag\Compound;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\Enum;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\ShortTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\network\Network;
use pocketmine\network\protocol\AddPlayerPacket;
use pocketmine\network\protocol\MobEffectPacket;
use pocketmine\network\protocol\MoveEntityPacket;
use pocketmine\network\protocol\MovePlayerPacket;
use pocketmine\network\protocol\RemoveEntityPacket;
use pocketmine\network\protocol\SetEntityDataPacket;
use pocketmine\network\protocol\SetTimePacket;
use pocketmine\Player;
use pocketmine\plugin\Plugin;
use pocketmine\Server;
use pocketmine\utils\ChunkException;
use pocketmine\block\Liquid;

use pocketmine\block\Cobweb;
use pocketmine\block\Fire;
use pocketmine\block\Ladder;
use pocketmine\block\Vine;

abstract class Entity extends Location implements Metadatable{


	const NETWORK_ID = -1;

	const DIRECTION_SOUTH = 0;
	const DIRECTION_WEST = 1;
	const DIRECTION_NORTH = 2;
	const DIRECTION_EAST = 3;
	
	const DATA_TYPE_BYTE = 0;
	const DATA_TYPE_SHORT = 1;
	const DATA_TYPE_INT = 2;
	const DATA_TYPE_FLOAT = 3;
	const DATA_TYPE_STRING = 4;
	const DATA_TYPE_SLOT = 5;
	const DATA_TYPE_POS = 6;
	const DATA_TYPE_LONG = 7;
	const DATA_TYPE_VECTOR3 = 8;

	const DATA_FLAGS = 0; //is entity burning or not
	const DATA_ANIMAL_VARIANT = 2; // type: int
	const DATA_COLOR = 3; // type: byte
	const DATA_NAMETAG = 4; // type: string
	const DATA_AIR = 7; //air under water type: short
	const DATA_POTION_COLOR = 8; // type: int data: rgb
	const DATA_POTION_AMBIENT = 9; //is potion ambient or not
	const DATA_JUMP_DURATION = 10; // type: long
	const DATA_HORSE_FLAGS = 16; // type: int
	const DATA_HORSE_TYPE = 19; // type: byte
	const DATA_ENDERMAN_BLOCK_ID = 23; // type: short
	const DATA_ENDERMAN_BLOCK_META = 24; // type:short
	const DATA_PLAYER_FLAGS = 27;
	const DATA_PLAYER_BED_POSITION = 29;
	const DATA_LEAD_HOLDER = 38; // type: long
	const DATA_SCALE = 39; // type: float
	const DATA_BUTTON_TEXT = 40; // type: string !IMPORTANT! Send to player
	const DATA_MAX_AIR = 44; // type: short
	const DATA_WIDTH = 54;
	const DATA_HEIGHT = 55; // type: float
	const DATA_SEAT_RIDER_OFFSET = 57; // type: vector3
	
	const DATA_EXPLODE_TIMER = 56;
	
	const DATA_SILENT = 4;
	const DATA_LEAD = 24; //remove
	
	const DATA_NO_AI = 231321;
	
	//other dataProperty values:
	//14 - age (0 - baby, 130000 - parent)
	//16 - sheep color (the same as wool meta)
	//17 - shooter id (for throwable entities)
	//19 - charge value for creaper
	//20 - boat color
	//21 - in love (for those who can breed)
	
	
	const DATA_FLAG_ONFIRE = 0;
	const DATA_FLAG_SNEAKING = 1;
	const DATA_FLAG_RIDING = 2;
	const DATA_FLAG_SPRINTING = 3;
	const DATA_FLAG_ACTION = 4;
	const DATA_FLAG_INVISIBLE = 5;
	const DATA_FLAG_TEMPTED = 6;
	const DATA_FLAG_INLOVE = 7;
	const DATA_FLAG_SADDLE = 8;
	const DATA_FLAG_POWERED = 9;
	const DATA_FLAG_IGNITED = 10;
	const DATA_FLAG_IS_BABY = 11; // disable head scaling
	const DATA_FLAG_CONVERTING = 12;
	const DATA_FLAG_CRITICAL = 13;
	const DATA_FLAG_SHOW_NAMETAG = 14;
	const DATA_FLAG_ALWAYS_SHOW_NAMETAG = 15;
	const DATA_FLAG_NOT_MOVE = 16;
	const DATA_FLAG_NO_AI = 16;
	const DATA_FLAG_SILENT = 17;
	const DATA_FLAG_IS_CLIMBING = 18;
	const DATA_FLAG_RESTING_BAT = 19;
	const DATA_FLAG_ANIMAL_SIT = 20;
	const DATA_FLAG_ANGRY_WOLF = 21;
	const DATA_FLAG_INTERESTED = 22; //for mobs following players with food?
	const DATA_FLAG_ANGRY_BLAZE = 23;
	const DATA_FLAG_TAME_WOLF = 24; //works with DATA_COLOR
	const DATA_FLAG_LEASHED = 25;
	const DATA_FLAG_SHAVED_SHIP = 26;
	const DATA_FLAG_FALL_FLYING = 27;
	const DATA_FLAG_ELDER_GUARDIAN = 28;
	const DATA_FLAG_MOVING = 29; // ???
	const DATA_FLAG_NOT_IN_WATER = 30;
	const DATA_FLAG_CHESTED_MOUNT = 31;
	const DATA_FLAG_STACKABLE = 32; //???	
	
	/* 1.1.0 new flags
	const DATA_FLAG_CAN_CLIMBING = 19; 
	const DATA_FLAG_IS_SWIMMER = 20; 
	const DATA_FLAG_CAN_FLY = 21; 
	const DATA_FLAG_IS_STAING = 37; 
	 */
	const DATA_FLAG_IS_WASD_CONTROLLED = 42; 
	const DATA_FLAG_CAN_POWER_JUMP = 43;
	const DATA_FLAG_HAS_COLLISION = 45;
	const DATA_FLAG_AFFECTED_BY_GRAVITY = 46;
	
	const DATA_PLAYER_FLAG_SLEEP = 1;
	const DATA_PLAYER_FLAG_DEAD = 2;
	
	public static $entityCount = 2;
	/** @var Entity[] */
	private static $knownEntities = [];
	private static $shortNames = [];

	/**
	 * @var Player[]
	 */
	protected $hasSpawned = [];

	/** @var Effect[] */
	protected $effects = [];

	protected $id;
	
	protected $dataFlags = 0;
	protected $dataProperties = [	
		self::DATA_FLAGS => [self::DATA_TYPE_LONG, 0],
		self::DATA_AIR => [self::DATA_TYPE_SHORT, 300],
		self::DATA_NAMETAG => [self::DATA_TYPE_STRING, ""],
		self::DATA_LEAD_HOLDER => [self::DATA_TYPE_LONG, -1],
		self::DATA_MAX_AIR => [self::DATA_TYPE_SHORT, 300],
	];
	
	public $passenger = null;
	public $vehicle = null;

	/** @var int */
	public $chunkX;
	/** @var int */
	public $chunkZ;

	/** @var Chunk */
	public $chunk;

	protected $lastDamageCause = null;

	public $lastX = null;
	public $lastY = null;
	public $lastZ = null;

	public $motionX;
	public $motionY;
	public $motionZ;
	public $lastMotionX;
	public $lastMotionY;
	public $lastMotionZ;

	public $lastYaw;
	public $lastPitch;

	/** @var AxisAlignedBB */
	public $boundingBox;
	public $onGround;
	public $inBlock = false;
	public $positionChanged;
	public $motionChanged;
	public $dead;
	public $deadTicks = 0;
	protected $age = 0;

	public $height;

	public $eyeHeight = null;

	public $width;
	public $length;

	/** @var int */
	private $health = 20;
	private $maxHealth = 20;

	public $keepMovement = false;

	public $fallDistance = 0;
	public $ticksLived = 0;
	public $lastUpdate;
	public $maxFireTicks;
	public $fireTicks;
	public $airTicks;
	public $namedtag;
	public $canCollide = true;

	protected $isStatic = false;

	public $isCollided = false;

	public $noDamageTicks;
	protected $justCreated;
	protected $fireProof;
	private $invulnerable;

	protected $gravity;
	protected $drag;

	/** @var Server */
	protected $server;

	public $closed = false;

	/** @var \pocketmine\event\TimingsHandler */
	protected $timings;
	
	protected $fireDamage = 1;


	public function __construct(FullChunk $chunk, Compound $nbt){
		if($chunk === null or $chunk->getProvider() === null){
			throw new ChunkException("Invalid garbage Chunk given to Entity");
		}

		$this->timings = Timings::getEntityTimings($this);

		if($this->eyeHeight === null){
			$this->eyeHeight = $this->height / 2 + 0.1;
		}
		$this->id = Entity::$entityCount++;		
		$this->justCreated = true;	
		$this->namedtag = $nbt;
		
		$this->chunk = $chunk;
		$this->setLevel($chunk->getProvider()->getLevel());
		$this->server = $chunk->getProvider()->getLevel()->getServer();
		$this->server->addSpawnedEntity($this);

		$this->boundingBox = new AxisAlignedBB(0, 0, 0, 0, 0, 0);
		$this->setPositionAndRotation(
			new Vector3(
				$this->namedtag["Pos"][0],
				$this->namedtag["Pos"][1],
				$this->namedtag["Pos"][2]
			),
			$this->namedtag["Rotation"][0],
			$this->namedtag["Rotation"][1],
			true
		);
		$this->motionX = $this->namedtag["Motion"][0];
		$this->motionY = $this->namedtag["Motion"][1];
		$this->motionZ = $this->namedtag["Motion"][2];
//		$this->setMotion(new Vector3($this->namedtag["Motion"][0], $this->namedtag["Motion"][1], $this->namedtag["Motion"][2]));

		if(!isset($this->namedtag->FallDistance)){
			$this->namedtag->FallDistance = new FloatTag("FallDistance", 0);
		}
		$this->fallDistance = $this->namedtag["FallDistance"];

		if(!isset($this->namedtag->Fire)){
			$this->namedtag->Fire = new ShortTag("Fire", 0);
		}
		$this->fireTicks = $this->namedtag["Fire"];

		if(!isset($this->namedtag->Air)){
			$this->namedtag->Air = new ShortTag("Air", 300);
		}
		$this->dataProperties[self::DATA_AIR] = [self::DATA_TYPE_SHORT, 300];
		$this->setDataFlag(self::DATA_FLAGS, self::DATA_FLAG_NOT_IN_WATER, true, self::DATA_TYPE_LONG, false);
		$this->setDataFlag(self::DATA_FLAGS, self::DATA_FLAG_SHOW_NAMETAG, true, self::DATA_TYPE_LONG, false);
		$this->setDataFlag(self::DATA_FLAGS, self::DATA_FLAG_ALWAYS_SHOW_NAMETAG, true, self::DATA_TYPE_LONG, false);
//		$this->setDataProperty(self::DATA_AIR, self::DATA_TYPE_SHORT, $this->namedtag["Air"]);

		if(!isset($this->namedtag->OnGround)){
			$this->namedtag->OnGround = new ByteTag("OnGround", 0);
		}
		$this->onGround = $this->namedtag["OnGround"] > 0 ? true : false;

		if(!isset($this->namedtag->Invulnerable)){
			$this->namedtag->Invulnerable = new ByteTag("Invulnerable", 0);
		}
		$this->invulnerable = $this->namedtag["Invulnerable"] > 0 ? true : false;

		$this->chunk->addEntity($this);
		$this->level->addEntity($this);
		$this->initEntity();
		$this->lastUpdate = $this->server->getTick();
		$this->server->getPluginManager()->callEvent(new EntitySpawnEvent($this));
		$this->scheduleUpdate();

	}

	/**
	 * @return string
	 */
	public function getNameTag(){
		return $this->getDataProperty(self::DATA_NAMETAG);
	}

	/**
	 * @return bool
	 */
	public function isNameTagVisible(){
//		return $this->getDataProperty(self::DATA_SHOW_NAMETAG) > 0;
		return $this->getDataFlag(Entity::DATA_FLAGS, Entity::DATA_FLAG_SHOW_NAMETAG);
	}

	/**
	 * @param string $name
	 */
	public function setNameTag($name){
		$this->setDataProperty(self::DATA_NAMETAG, self::DATA_TYPE_STRING, $name);
	}

	/**
	 * @param bool $value
	 */
	public function setNameTagVisible($value = true){
		$this->setDataFlag(Entity::DATA_FLAGS, Entity::DATA_FLAG_SHOW_NAMETAG, $value ? true : false);
//		$this->setDataProperty(self::DATA_SHOW_NAMETAG, self::DATA_TYPE_BYTE, $value ? 1 : 0);
	}

	public function isSneaking(){
		return $this->getDataFlag(self::DATA_FLAGS, self::DATA_FLAG_SNEAKING);
	}

	public function setSneaking($value = true){
		$this->setDataFlag(self::DATA_FLAGS, self::DATA_FLAG_SNEAKING, (bool) $value);
	}

	public function isSprinting(){
		return $this->getDataFlag(self::DATA_FLAGS, self::DATA_FLAG_SPRINTING);
	}

	public function setSprinting($value = true){
		$this->setDataFlag(self::DATA_FLAGS, self::DATA_FLAG_SPRINTING, (bool) $value);
	}
	
	public function setFlyingFlag($value = true){
		$this->setDataFlag(self::DATA_FLAGS, self::DATA_FLAG_FALL_FLYING, (bool) $value);
	}

	/**
	 * @return Effect[]
	 */
	public function getEffects(){
		return $this->effects;
	}

	public function removeAllEffects() {
		foreach ($this->effects as $effectId => $effect) {
			unset($this->effects[$effectId]);
			$effect->remove($this);
		}
		$this->recalculateEffectColor();
	}

	public function removeEffect($effectId){
		if (isset($this->effects[$effectId])) {
			$effect = $this->effects[$effectId];
			unset($this->effects[$effectId]);
			$effect->remove($this);

			$this->recalculateEffectColor();
		}
	}

	public function getEffect($effectId){
		return isset($this->effects[$effectId]) ? $this->effects[$effectId] : null;
	}

	public function hasEffect($effectId){
		return isset($this->effects[$effectId]);
	}

	public function addEffect(Effect $effect){
		$effectId = $effect->getId();
		if (isset($this->effects[$effectId])) {
			if (abs($effect->getAmplifier()) < abs($this->effects[$effectId]->getAmplifier()) || (
					abs($effect->getAmplifier()) === abs($this->effects[$effectId]->getAmplifier()) &&
					$effect->getDuration() <= $this->effects[$effectId]->getDuration())){
				
				return;
			}
			$effect->add($this, true);
		} else {
			$effect->add($this, false);
		}

		$this->effects[$effectId] = $effect;

		$this->recalculateEffectColor();

		if ($effectId === Effect::HEALTH_BOOST) {
			$this->setHealth($this->getHealth() + 4 * ($effect->getAmplifier() + 1));
		}
	}

	protected function recalculateEffectColor(){
		$color = [0, 0, 0]; //RGB
		$count = 0;
		$ambient = true;
		foreach($this->effects as $effect){
			if($effect->isVisible()){
				$c = $effect->getColor();
				$amplifier = $effect->getAmplifier() + 1;
				$color[0] += $c[0] * $amplifier;
				$color[1] += $c[1] * $amplifier;
				$color[2] += $c[2] * $amplifier;
				$count += $amplifier;
				if ($ambient === true && !$effect->isAmbient()) {
					$ambient = false;
				}
			}
		}

		if ($count > 0) {
			$r = ($color[0] / $count) & 0xff;
			$g = ($color[1] / $count) & 0xff;
			$b = ($color[2] / $count) & 0xff;

			$this->setDataProperty(Entity::DATA_POTION_COLOR, Entity::DATA_TYPE_INT, ($r << 16) + ($g << 8) + $b);
			$this->setDataProperty(Entity::DATA_POTION_AMBIENT, Entity::DATA_TYPE_BYTE, $ambient ? 1 : 0);
		}else{
			$this->setDataProperty(Entity::DATA_POTION_COLOR, Entity::DATA_TYPE_INT, 0);
			$this->setDataProperty(Entity::DATA_POTION_AMBIENT, Entity::DATA_TYPE_BYTE, 0);
		}
	}

	/**
	 * @param int|string $type
	 * @param FullChunk  $chunk
	 * @param Compound   $nbt
	 * @param            $args
	 *
	 * @return Entity
	 */
	public static function createEntity($type, FullChunk $chunk, Compound $nbt, ...$args){
		if(isset(self::$knownEntities[$type])){
			$class = self::$knownEntities[$type];
			return new $class($chunk, $nbt, ...$args);
		}

		return null;
	}

	public static function registerEntity($className, $force = false){
		$class = new \ReflectionClass($className);
		if (is_a($className, Entity::class, true) && !$class->isAbstract()) {
			if ($className::NETWORK_ID !== -1) {
				self::$knownEntities[$className::NETWORK_ID] = $className;
			} else if (!$force) {
				return false;
			}

			self::$knownEntities[$class->getShortName()] = $className;
			self::$shortNames[$className] = $class->getShortName();
			return true;
		}

		return false;
	}

	/**
	 * Returns the short save name
	 *
	 * @return string
	 */
	public function getSaveId(){
		return self::$shortNames[static::class];
	}

	public function saveNBT(){
		if (!($this instanceof Player)) {
			$this->namedtag->id = new StringTag("id", $this->getSaveId());
			if ($this->getNameTag() !== "") {
				$this->namedtag->CustomName = new StringTag("CustomName", $this->getNameTag());
				$this->namedtag->CustomNameVisible = new StringTag("CustomNameVisible", $this->isNameTagVisible());
			} else {
				unset($this->namedtag->CustomName);
				unset($this->namedtag->CustomNameVisible);
			}
		}

		$this->namedtag->Pos = new Enum("Pos", [
			new DoubleTag(0, $this->x),
			new DoubleTag(1, $this->y),
			new DoubleTag(2, $this->z)
		]);

		$this->namedtag->Motion = new Enum("Motion", [
			new DoubleTag(0, $this->motionX),
			new DoubleTag(1, $this->motionY),
			new DoubleTag(2, $this->motionZ)
		]);

		$this->namedtag->Rotation = new Enum("Rotation", [
			new FloatTag(0, $this->yaw),
			new FloatTag(1, $this->pitch)
		]);

		$this->namedtag->FallDistance = new FloatTag("FallDistance", $this->fallDistance);
		$this->namedtag->Fire = new ShortTag("Fire", $this->fireTicks);
		$this->namedtag->Air = new ShortTag("Air", $this->getDataProperty(self::DATA_AIR));
		$this->namedtag->OnGround = new ByteTag("OnGround", $this->onGround == true ? 1 : 0);
		$this->namedtag->Invulnerable = new ByteTag("Invulnerable", $this->invulnerable == true ? 1 : 0);

		if (count($this->effects) > 0) {
			$effects = [];
			foreach ($this->effects as $effectId => $effect) {
				$effects[$effectId] = new Compound($effectId, [
					"Id" => new ByteTag("Id", $effectId),
					"Amplifier" => new ByteTag("Amplifier", $effect->getAmplifier()),
					"Duration" => new IntTag("Duration", $effect->getDuration()),
					"Ambient" => new ByteTag("Ambient", 0),
					"ShowParticles" => new ByteTag("ShowParticles", $effect->isVisible() ? 1 : 0)
				]);
			}

			$this->namedtag->ActiveEffects = new Enum("ActiveEffects", $effects);
		} else {
			unset($this->namedtag->ActiveEffects);
		}
	}

	protected function initEntity() {
		if (isset($this->namedtag->ActiveEffects)) {
			foreach ($this->namedtag->ActiveEffects->getValue() as $e) {
				$effect = Effect::getEffect($e["Id"]);
				if ($effect === null) {
					continue;
				}
				$effect->setAmplifier($e["Amplifier"])->setDuration($e["Duration"])->setVisible($e["ShowParticles"] > 0);
				$this->addEffect($effect);
			}
		}
		
		if (isset($this->namedtag->CustomName)) {
			$this->setNameTag($this->namedtag["CustomName"]);
			if (isset($this->namedtag->CustomNameVisible)) {
				$this->setNameTagVisible($this->namedtag["CustomNameVisible"] > 0);
			}
		}

		$this->scheduleUpdate();
	}

	/**
	 * @return Player[]
	 */
	public function getViewers() {
		return $this->hasSpawned;
	}

	/**
	 * @param Player $player
	 */
	public function spawnTo(Player $player) {
		if (!isset($this->hasSpawned[$player->getId()]) && isset($player->usedChunks[Level::chunkHash($this->chunk->getX(), $this->chunk->getZ())])) {
			$this->hasSpawned[$player->getId()] = $player;
		}
	}
	
	
	public function isSpawned(Player $player) {
		if (isset($this->hasSpawned[$player->getId()])) {
			return true;
		}
		return false;
	}

	public function sendPotionEffects(Player $player) {
		foreach ($this->effects as $effect) {
			$pk = new MobEffectPacket();
			$pk->eid = $player->getId();
			$pk->effectId = $effect->getId();
			$pk->amplifier = $effect->getAmplifier();
			$pk->particles = $effect->isVisible();
			$pk->duration = $effect->getDuration();
			$pk->eventId = MobEffectPacket::EVENT_ADD;

			$player->dataPacket($pk);
		}
	}

	/**
	 * @deprecated
	 */
	public function sendMetadata($player) {
		$this->sendData($player);
	}

	/**
	 * @param Player[]|Player $player
	 * @param array $data Properly formatted entity data, defaults to everything
	 */
	public function sendData($player, array $data = null) {
		if (!is_array($player)) {
			$player = [$player];
		}
		$pk = new SetEntityDataPacket();
		$pk->eid = $this->id;
		$pk->metadata = $data === null ? $this->dataProperties : $data;
		Server::broadcastPacket($player, $pk);
	}

	/**
	 * @param Player $player
	 */
	public function despawnFrom(Player $player) {
		if (isset($this->hasSpawned[$player->getId()])) {
			$pk = new RemoveEntityPacket();
			$pk->eid = $this->getId();
			$player->dataPacket($pk);
			unset($this->hasSpawned[$player->getId()]);
		}
	}

	/**
	 * @param float             $damage
	 * @param EntityDamageEvent $source
	 *
	 */
	public function attack($damage, EntityDamageEvent $source) {
		$cause = $source->getCause();
		if ($this->hasEffect(Effect::FIRE_RESISTANCE) && (
				$cause === EntityDamageEvent::CAUSE_FIRE || 
				$cause === EntityDamageEvent::CAUSE_FIRE_TICK || 
				$cause === EntityDamageEvent::CAUSE_LAVA)) {
			
			$source->setCancelled();
		}
		
		$this->server->getPluginManager()->callEvent($source);
		if($source->isCancelled()){
			return;
		}
		
		if ($source instanceof EntityDamageByEntityEvent) {
			$damager = $source->getDamager();
			if ($damager instanceof Player) {
				$weapon = $damager->getInventory()->getItemInHand();
				if ($weapon instanceof Tool) {
					$enchantment = $weapon->getEnchantment(Enchantment::TYPE_WEAPON_FIRE_ASPECT);
					if (!is_null($enchantment)) {
						$fireDamage = max(($enchantment->getLevel() * 4) - 1, 1);
						$this->setOnFire(4, $fireDamage);
					}
				}
			}
		}

		$this->setLastDamageCause($source);

		$this->setHealth($this->getHealth() - $source->getFinalDamage());
	}

	/**
	 * @param float                   $amount
	 * @param EntityRegainHealthEvent $source
	 *
	 */
	public function heal($amount, EntityRegainHealthEvent $source) {
		$this->server->getPluginManager()->callEvent($source);
		if ($source->isCancelled()) {
			return;
		}
		$this->setHealth($this->getHealth() + $source->getAmount());
	}

	/**
	 * @return int
	 */
	public function getHealth() {
		return $this->health;
	}

	public function isAlive() {
		return $this->health > 0;
	}

	/**
	 * Sets the health of the Entity. This won't send any update to the players
	 *
	 * @param int $amount
	 */
	public function setHealth($amount) {
		$amount = (int) round($amount);
		if ($amount === $this->health) {
			return;
		}
		if ($amount <= 0) {
			$this->health = 0;
			if ($this->dead !== true) {
				$this->kill();
			}
		} else if ($amount <= $this->getMaxHealth() || $amount < $this->health) {
			$this->health = (int) $amount;
		} else {
			$this->health = $this->getMaxHealth();
		}
	}

	/**
	 * @param EntityDamageEvent $type
	 */
	public function setLastDamageCause(EntityDamageEvent $type) {
		$this->lastDamageCause = $type;
	}

	/**
	 * @return EntityDamageEvent|null
	 */
	public function getLastDamageCause() {
		return $this->lastDamageCause;
	}

	/**
	 * @return int
	 */
	public function getMaxHealth() {
		$effect = $this->getEffect(Effect::HEALTH_BOOST);
		return $this->maxHealth + ($effect !== null ? 4 * $effect->getAmplifier() + 1 : 0);
	}

	/**
	 * @param int $amount
	 */
	public function setMaxHealth($amount) {
		$this->maxHealth = (int) $amount;
	}

	public function canCollideWith(Entity $entity) {
		return !$this->justCreated && $entity !== $this;
	}

	protected function checkObstruction($x, $y, $z) {
		$i = Math::floorFloat($x);
		$j = Math::floorFloat($y);
		$k = Math::floorFloat($z);

		if (Block::$solid[$this->level->getBlockIdAt($i, $j, $k)]) {
			$direction = -1;
			$limit = 9999;
			$diffX = $x - $i;
			$diffY = $y - $j;
			$diffZ = $z - $k;

			if (!Block::$solid[$this->level->getBlockIdAt($i - 1, $j, $k)]) {
				$limit = $diffX;
				$direction = 0;
			}
			if (1 - $diffX < $limit && !Block::$solid[$this->level->getBlockIdAt($i + 1, $j, $k)]) {
				$limit = 1 - $diffX;
				$direction = 1;
			}
			if ($diffY < $limit && !Block::$solid[$this->level->getBlockIdAt($i, $j - 1, $k)]) {
				$limit = $diffY;
				$direction = 2;
			}
			if (1 - $diffY < $limit && !Block::$solid[$this->level->getBlockIdAt($i, $j + 1, $k)]) {
				$limit = 1 - $diffY;
				$direction = 3;
			}
			if ($diffZ < $limit && !Block::$solid[$this->level->getBlockIdAt($i, $j, $k - 1)]) {
				$limit = $diffZ;
				$direction = 4;
			}
			if (1 - $diffZ < $limit && !Block::$solid[$this->level->getBlockIdAt($i, $j, $k + 1)]) {
				$direction = 5;
			}

			$force = lcg_value() * 0.2 + 0.1;

			switch ($direction) {
				case 0:
					$this->motionX = -$force;
					return true;
				case 1:
					$this->motionX = $force;
					return true;
				case 2:
					$this->motionY = -$force;
					return true;
				case 3:
					$this->motionY = $force;
					return true;
				case 4:
					$this->motionZ = -$force;
					return true;
				case 5:
					$this->motionZ= $force;
					return true;
			}
		}

		return false;
	}

	public function entityBaseTick($tickDiff = 1) {
		$this->justCreated = false;
		if ($this->dead === true) {
			$this->removeAllEffects();
			$this->despawnFromAll();
			$this->close();
			return false;
		}
		
		$this->age += $tickDiff;
		$this->ticksLived += $tickDiff;
		if ($this->noDamageTicks > 0) {
			$this->noDamageTicks -= $tickDiff;
		}
		
		$hasUpdate = false;
		if ($this->y < 0 && $this->dead !== true) {
			$ev = new EntityDamageEvent($this, EntityDamageEvent::CAUSE_VOID, 20);
			$this->attack($ev->getFinalDamage(), $ev);
			$hasUpdate = true;
		}

		foreach ($this->effects as $effect) {
			if ($effect->canTick()) {
				$effect->applyEffect($this);
			}
			$newDuration = $effect->getDuration() - $tickDiff;
			if ($newDuration <= 0) {
				$this->removeEffect($effect->getId());
			} else {
				$effect->setDuration($newDuration);
			}
		}
		
		$this->checkBlockCollision();

		if ($this->fireTicks > 0) {
			if ($this->fireProof) {
				$this->fireTicks -= 4 * $tickDiff;
			} else {
				if (!$this->hasEffect(Effect::FIRE_RESISTANCE) && ($this->fireTicks % 20) === 0 || $tickDiff > 20) {
					$ev = new EntityDamageEvent($this, EntityDamageEvent::CAUSE_FIRE_TICK, $this->fireDamage);
					$this->attack($ev->getFinalDamage(), $ev);
				}
				$this->fireTicks -= $tickDiff;
			}

			if ($this->fireTicks <= 0) {
				$this->extinguish();
			} else {
				$this->setDataFlag(self::DATA_FLAGS, self::DATA_FLAG_ONFIRE, true);
				$hasUpdate = true;
			}
		}
		return $hasUpdate;
	}

	protected function updateMovement(){
		$diffPosition = ($this->x - $this->lastX) ** 2 + ($this->y - $this->lastY) ** 2 + ($this->z - $this->lastZ) ** 2;
		$diffRotation = ($this->yaw - $this->lastYaw) ** 2 + ($this->pitch - $this->lastPitch) ** 2;

		$diffMotion = ($this->motionX - $this->lastMotionX) ** 2 + ($this->motionY - $this->lastMotionY) ** 2 + ($this->motionZ - $this->lastMotionZ) ** 2;

		if($diffPosition > 0.04 or $diffRotation > 2.25 and ($diffMotion > 0.0001 and $this->getMotion()->lengthSquared() <= 0.00001)){ //0.2 ** 2, 1.5 ** 2
			$this->lastX = $this->x;
			$this->lastY = $this->y;
			$this->lastZ = $this->z;

			$this->lastYaw = $this->yaw;
			$this->lastPitch = $this->pitch;

			$this->level->addEntityMovement($this->getViewers(), $this->id, $this->x, $this->y + $this->getEyeHeight(), $this->z, $this->yaw, $this->pitch, $this->yaw, ($this instanceof Player));
		}

		if($diffMotion > 0.0025 or ($diffMotion > 0.0001 and $this->getMotion()->lengthSquared() <= 0.0001)){ //0.05 ** 2
			$this->lastMotionX = $this->motionX;
			$this->lastMotionY = $this->motionY;
			$this->lastMotionZ = $this->motionZ;

			$this->level->addEntityMotion($this->getViewers(), $this->id, $this->motionX, $this->motionY, $this->motionZ);
		}
	}

	/**
	 * @return Vector3
	 */
	public function getDirectionVector(){
		$y = -sin(deg2rad($this->pitch));
		$xz = cos(deg2rad($this->pitch));
		$x = -$xz * sin(deg2rad($this->yaw));
		$z = $xz * cos(deg2rad($this->yaw));

		return new Vector3($x, $y, $z);
	}

	public function onUpdate($currentTick){
		if($this->closed){
			return false;
		}

		$tickDiff = max(1, $currentTick - $this->lastUpdate);
		$this->lastUpdate = $currentTick;

		//$this->timings->startTiming();

		$hasUpdate = $this->entityBaseTick($tickDiff);

		$this->updateMovement();

		//$this->timings->stopTiming();

		//if($this->isStatic())
		return $hasUpdate;
		//return !($this instanceof Player);
	}

	public final function scheduleUpdate(){
		$this->level->updateEntities[$this->id] = $this;
	}

	public function isOnFire(){
		return $this->fireTicks > 0;
	}

	public function setOnFire($seconds, $damage = 1){
		$ticks = $seconds * 20;
		if($ticks > $this->fireTicks){
			$this->fireTicks = $ticks;
		}
		$this->fireDamage = $damage;
	}

	public function getDirection(){
		$rotation = ($this->yaw - 90) % 360;
		if($rotation < 0){
			$rotation += 360;
		}
		if ($rotation < 45) {
			return self::DIRECTION_NORTH;
		} else if ($rotation < 135) {
			return self::DIRECTION_EAST;
		} else if ($rotation < 225) {
			return self::DIRECTION_SOUTH;
		} else if ($rotation < 315) {
			return self::DIRECTION_WEST;
		}
		return self::DIRECTION_NORTH;
	}

	public function extinguish(){
		$this->fireTicks = 0;
		$this->fireDamage = 1;
		$this->setDataFlag(self::DATA_FLAGS, self::DATA_FLAG_ONFIRE, false);
	}

	public function canTriggerWalking(){
		return true;
	}

	public function resetFallDistance(){
		$this->fallDistance = 0;
	}

	protected function updateFallState($distanceThisTick, $onGround) {
		if ($onGround === true) {
			if($this->fallDistance > 0) {
				if (!$this->isCollideWithWater()) {
					$this->fall($this->fallDistance);
				}
				$this->resetFallDistance();
			}
		} else if ($distanceThisTick < 0) {
			$this->fallDistance -= $distanceThisTick;
		}
	}

	public function getBoundingBox(){
		return $this->boundingBox;
	}

	public function fall($fallDistance){
		$damage = floor($fallDistance - 3);
		if($damage > 0){
			$ev = new EntityDamageEvent($this, EntityDamageEvent::CAUSE_FALL, $damage);
			$this->attack($ev->getFinalDamage(), $ev);
		}
	}

	public function handleLavaMovement(){ //TODO

	}

	public function getEyeHeight(){
		return $this->eyeHeight;
	}

	public function moveFlying(){ //TODO

	}

	public function onCollideWithPlayer(Human $entityPlayer){
		
	}

	protected function switchLevel(Level $targetLevel){
		if ($this->isValid()) {
			$this->server->getPluginManager()->callEvent($ev = new EntityLevelChangeEvent($this, $this->level, $targetLevel));
			if ($ev->isCancelled()) {
				return false;
			}

			$this->level->removeEntity($this);
			if ($this->chunk !== null) {
				$this->chunk->removeEntity($this);
			}
			$this->despawnFromAll();
			if ($this instanceof Player) {
				$X = $Z = null;
				foreach ($this->usedChunks as $index => $d) {
					Level::getXZ($index, $X, $Z);
					$this->unloadChunk($X, $Z);
				}
			}
		}
		$this->setLevel($targetLevel);
		$this->level->addEntity($this);
		if ($this instanceof Player) {
			$this->usedChunks = [];
			$pk = new SetTimePacket();
			$pk->time = $this->level->getTime();
			$pk->started = $this->level->stopTime == false;
			$this->dataPacket($pk);
		}
		$this->chunk = null;

		return true;
	}

	public function getPosition(){
		return new Position($this->x, $this->y, $this->z, $this->level);
	}

	public function getLocation(){
		return new Location($this->x, $this->y, $this->z, $this->yaw, $this->pitch, $this->level);
	}

	public function isInsideOfWater() {
		$blockId = $this->level->getBlockIdAt(floor($this->x), floor($this->y + $this->eyeHeight), floor($this->z));
		if ($blockId == Block::WATER || $blockId == Block::STILL_WATER) {			
			return true;
		}
		return false;
	}

	public function isCollideWithWater() {
		$x = floor($this->x);
		$z = floor($this->z);
		$blockId = $this->level->getBlockIdAt($x, floor($this->y),$z);
		if ($blockId == Block::WATER || $blockId == Block::STILL_WATER) {			
			return true;
		}
		$topBlockId = $this->level->getBlockIdAt($x, floor($this->y + $this->eyeHeight),$z);
		if ($topBlockId == Block::WATER || $topBlockId == Block::STILL_WATER) {			
			return true;
		}
		$bottomBlockId = $this->level->getBlockIdAt($x, floor($this->y - 1),$z);
		if ($bottomBlockId == Block::WATER || $bottomBlockId == Block::STILL_WATER) {			
			return true;
		}
		return false;
	}
	
	protected function getBlocksAround(){
		$x = floor($this->x);
		$z = floor($this->z);	
		$blocksAround = [];
		$blocksAround[] = $this->level->getBlock(new Vector3($x, floor($this->y), $z));
		$blocksAround[] = $this->level->getBlock(new Vector3($x, floor($this->y + $this->eyeHeight), $z));
		return $blocksAround;
	}
	
	protected function checkBlockCollision(){
		foreach($this->getBlocksAround() as $block){
			if($block->hasEntityCollision()) {
				$block->onEntityCollide($this);
				$this->onGround = true;
			}
		}
	}

	public function isInsideOfSolid(){
		$block = $this->level->getBlock(new Vector3(floor($this->x), floor($this->y + $this->eyeHeight), floor($this->z)));
		if($block->isSolid() && !$block->isTransparent()){
			return true;
		}
		return false;
	}

	public function move($dx, $dy, $dz){	
		if($dx == 0 and $dz == 0 and $dy == 0){
			return true;
		}

		if($this->keepMovement){
			$this->boundingBox->offset($dx, $dy, $dz);
			$this->setPosition(new Vector3(($this->boundingBox->minX + $this->boundingBox->maxX) / 2, $this->boundingBox->minY, ($this->boundingBox->minZ + $this->boundingBox->maxZ) / 2));
			return true;
		}else{
			$pos = new Vector3($this->x + $dx, $this->y + $dy, $this->z + $dz);			
			if(!$this->setPosition($pos)){
				return false;
			}else{
				$bb = clone $this->boundingBox;
				$bb->maxY = $bb->minY + 0.5;
				$bb->minY -= 1;
				if (count($this->level->getCollisionBlocks($bb)) > 0) {
					$this->onGround = true;
				} else {
					$this->onGround = false;
				}
				$this->isCollided = $this->onGround;
				$this->updateFallState($dy, $this->onGround);
			}
			return true;
		}
	}



	public function setPositionAndRotation(Vector3 $pos, $yaw, $pitch){
		if($this->setPosition($pos) === true){
			$this->setRotation($yaw, $pitch);

			return true;
		}

		return false;
	}

	public function setRotation($yaw, $pitch){
		$this->yaw = $yaw;
		$this->pitch = $pitch;
		$this->scheduleUpdate();
	}

	protected function checkChunks(){
		if($this->chunk === null or ($this->chunk->getX() !== ($this->x >> 4) or $this->chunk->getZ() !== ($this->z >> 4))){
			if($this->chunk !== null){
				$this->chunk->removeEntity($this);
			}
			$this->chunk = $this->level->getChunk($this->x >> 4, $this->z >> 4, true);

			if(!$this->justCreated){
				$newChunk = $this->level->getUsingChunk($this->x >> 4, $this->z >> 4);
				foreach($this->hasSpawned as $player){
					if(!isset($newChunk[$player->getId()])){
						$this->despawnFrom($player);
					}else{
						unset($newChunk[$player->getId()]);
					}
				}
				foreach($newChunk as $player){
					if ($player->canSeeEntity($this)) {
						$this->spawnTo($player);
					}
				}
			}

			if($this->chunk === null){
				return;
			}

			$this->chunk->addEntity($this);
		}
	}

	public function setPosition(Vector3 $pos){
		if($this->closed){
			return false;
		}

		if($pos instanceof Position and $pos->level !== null and $pos->level !== $this->level){
			if($this->switchLevel($pos->getLevel()) === false){
				return false;
			}
		}

		$this->x = $pos->x;
		$this->y = $pos->y;
		$this->z = $pos->z;

		$radius = $this->width / 2;
		$this->boundingBox->setBounds($pos->x - $radius, $pos->y, $pos->z - $radius, $pos->x + $radius, $pos->y + $this->height, $pos->z + $radius);

		if (!($this instanceof Player)) {
			$this->checkChunks();
		}

		return true;
	}

	public function getMotion(){
		return new Vector3($this->motionX, $this->motionY, $this->motionZ);
	}

	public function setMotion(Vector3 $motion){
		if(!$this->justCreated){
			$this->server->getPluginManager()->callEvent($ev = new EntityMotionEvent($this, $motion));
			if($ev->isCancelled()){
				return false;
			}
		}

		$this->motionX = $motion->x;
		$this->motionY = $motion->y;
		$this->motionZ = $motion->z;

		if(!$this->justCreated){
			$this->updateMovement();
		}

		return true;
	}

	public function isOnGround(){
		return $this->onGround === true;
	}

	public function kill(){
		if($this->dead){
			return;
		}
		$this->dead = true;
		$this->setHealth(0);
		$this->scheduleUpdate();
	}

	/**
	 * @param Vector3|Position|Location $pos
	 * @param float                     $yaw
	 * @param float                     $pitch
	 *
	 * @return bool
	 */
	public function teleport(Vector3 $pos, $yaw = null, $pitch = null){
		if($pos instanceof Location){
			$yaw = $yaw === null ? $pos->yaw : $yaw;
			$pitch = $pitch === null ? $pos->pitch : $pitch;
		}
		$from = Position::fromObject($this, $this->level);
		$to = Position::fromObject($pos, $pos instanceof Position ? $pos->getLevel() : $this->level);
		$this->server->getPluginManager()->callEvent($ev = new EntityTeleportEvent($this, $from, $to));
		if($ev->isCancelled()){
			return false;
		}
		$pos = $ev->getTo();

		$this->setMotion(new Vector3(0, 0, 0));
		if($this->setPositionAndRotation($pos, $yaw === null ? $this->yaw : $yaw, $pitch === null ? $this->pitch : $pitch, true) !== false){
			$this->resetFallDistance();
			$this->onGround = true;

			$this->lastX = $this->x;
			$this->lastY = $this->y;
			$this->lastZ = $this->z;

			$this->lastYaw = $this->yaw;
			$this->lastPitch = $this->pitch;

			$this->updateMovement();

			return true;
		}

		return false;
	}

	public function getId(){
		return $this->id;
	}

	public function respawnToAll(){
		foreach($this->hasSpawned as $key => $player){
			unset($this->hasSpawned[$key]);
			if ($player->canSeeEntity($this)) {
				$this->spawnTo($player);
			}
		}
	}

	public function spawnToAll(){
		if($this->chunk === null or $this->closed){
			return false;
		}
		foreach($this->level->getUsingChunk($this->chunk->getX(), $this->chunk->getZ()) as $player){
			if($player->loggedIn === true && $player->canSeeEntity($this)){
				$this->spawnTo($player);
			}
		}
	}

	public function despawnFromAll(){
		foreach($this->hasSpawned as $player){
			$this->despawnFrom($player);
		}
	}

	public function close(){
		if(!$this->closed){
			$this->server->removeSpawnedEntity($this);
			$this->server->getPluginManager()->callEvent(new EntityDespawnEvent($this));
			$this->closed = true;
			$this->despawnFromAll();
			if($this->chunk !== null){
				$this->chunk->removeEntity($this);
			}
			if($this->level !== null){
				$this->level->removeEntity($this);
			}
		}
	}

	/**
	 * @param int   $id
	 * @param int   $type
	 * @param mixed $value
	 */
	public function setDataProperty($id, $type, $value, $send = true){
		if($this->getDataProperty($id) !== $value){
			$this->dataProperties[$id] = [$type, $value];
			if (!$send) {
				return;
			}
		
			$targets = $this->hasSpawned;
			if($this instanceof Player){
				if(!$this->spawned){
					return;
				}
				$targets[] = $this;
			}

			$this->sendData($targets, [$id => $this->dataProperties[$id]]);
		}
	}
	
	public function removeDataProperty($id, $send = true) {
		unset($this->dataProperties[$id]);
		if ($send) {
			$this->sendData($this->hasSpawned);
		}
	}

	/**
	 * @param int $id
	 *
	 * @return mixed
	 */
	public function getDataProperty($id){
		return isset($this->dataProperties[$id]) ? $this->dataProperties[$id][1] : null;
	}

	/**
	 * @param int $id
	 *
	 * @return int
	 */
	public function getDataPropertyType($id){
		return isset($this->dataProperties[$id]) ? $this->dataProperties[$id][0] : null;
	}

	/**
	 * @param int  $propertyId;
	 * @param int  $id
	 * @param bool $value
	 */
	public function setDataFlag($propertyId, $id, $value = true, $type = self::DATA_TYPE_LONG, $send = true){
		if($this->getDataFlag($propertyId, $id) !== $value){
			$flags = (int) $this->getDataProperty($propertyId);
			$flags ^= 1 << $id;
			$this->setDataProperty($propertyId, $type, $flags, $send);
		}
	}

	/**
	 * @param int $propertyId
	 * @param int $id
	 *
	 * @return bool
	 */
	public function getDataFlag($propertyId, $id){
		return (((int) $this->getDataProperty($propertyId)) & (1 << $id)) > 0;
	}

	public function __destruct(){
		$this->close();
	}

	public function setMetadata($metadataKey, MetadataValue $metadataValue){
		$this->server->getEntityMetadata()->setMetadata($this, $metadataKey, $metadataValue);
	}

	public function getMetadata($metadataKey){
		return $this->server->getEntityMetadata()->getMetadata($this, $metadataKey);
	}

	public function hasMetadata($metadataKey){
		return $this->server->getEntityMetadata()->hasMetadata($this, $metadataKey);
	}

	public function removeMetadata($metadataKey, Plugin $plugin){
		$this->server->getEntityMetadata()->removeMetadata($this, $metadataKey, $plugin);
	}

	public function __toString(){
		return (new \ReflectionClass($this))->getShortName() . "(" . $this->getId() . ")";
	}
	
	public function setAirTick($val){
		$this->setDataProperty(self::DATA_AIR, self::DATA_TYPE_SHORT, $val, false);
	}
	
	public function isNeedSaveOnChunkUnload() {
		return true;
	}

}
