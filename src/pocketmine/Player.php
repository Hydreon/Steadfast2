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

namespace pocketmine;

use pocketmine\network\protocol\v120\PlaySoundPacket;
use pocketmine\network\protocol\v120\StopSoundPacket;
use const M_SQRT3;
use function max;
use function mt_rand;
use pocketmine\block\Block;
use pocketmine\command\CommandSender;
use pocketmine\customUI\CustomUI;
use pocketmine\entity\Arrow;
use pocketmine\entity\Effect;
use pocketmine\entity\Entity;
use pocketmine\entity\Human;
use pocketmine\entity\Item as DroppedItem;
use pocketmine\entity\Living;
use pocketmine\entity\Projectile;
use pocketmine\event\block\ItemFrameDropItemEvent;
use pocketmine\event\block\SignChangeEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityRegainHealthEvent;
use pocketmine\event\entity\EntityShootBowEvent;
use pocketmine\event\entity\ProjectileLaunchEvent;
use pocketmine\event\inventory\CraftItemEvent;
use pocketmine\event\inventory\InventoryCloseEvent;
use pocketmine\event\inventory\InventoryPickupArrowEvent;
use pocketmine\event\inventory\InventoryPickupItemEvent;
use pocketmine\event\player\PlayerAnimationEvent;
use pocketmine\event\player\PlayerBedEnterEvent;
use pocketmine\event\player\PlayerBedLeaveEvent;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerCommandPreprocessEvent;
use pocketmine\event\player\PlayerCommandPostprocessEvent;
use pocketmine\event\player\PlayerCreationEvent;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\player\PlayerGameModeChangeEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerItemConsumeEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerKickEvent;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerPreLoginEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerReceiptsReceivedEvent;
use pocketmine\event\player\PlayerRespawnEvent;
use pocketmine\event\player\PlayerRespawnAfterEvent;
use pocketmine\event\player\PlayerToggleSneakEvent;
use pocketmine\event\player\PlayerToggleSprintEvent;
use pocketmine\event\server\DataPacketSendEvent;
use pocketmine\event\TextContainer;
use pocketmine\event\Timings;
use pocketmine\form\Form;
use pocketmine\form\FormValidationException;
use pocketmine\inventory\BaseTransaction;
use pocketmine\inventory\BigShapedRecipe;
use pocketmine\inventory\BigShapelessRecipe;
use pocketmine\inventory\EnchantInventory;
use pocketmine\inventory\Inventory;
use pocketmine\inventory\InventoryHolder;
use pocketmine\inventory\PlayerInventory;
use pocketmine\inventory\ShapedRecipe;
use pocketmine\inventory\ShapelessRecipe;
use pocketmine\inventory\SimpleTransactionGroup;
use pocketmine\inventory\transactions\SimpleTransactionData;
use pocketmine\item\EnchantedGoldenApple;
use pocketmine\item\GoldenApple;
use pocketmine\item\Item;
use pocketmine\item\Armor;
use pocketmine\item\Tool;
use pocketmine\item\Potion;
use pocketmine\level\format\FullChunk;
use pocketmine\level\format\LevelProvider;
use pocketmine\level\Level;
use pocketmine\level\Location;
use pocketmine\level\Position;
use pocketmine\level\sound\LaunchSound;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Vector2;
use pocketmine\math\Vector3;
use pocketmine\metadata\MetadataValue;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\nbt\tag\Compound;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\Enum;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\LongTag;
use pocketmine\nbt\tag\ShortTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\network\Network;
use pocketmine\network\protocol\AdventureSettingsPacket;
use pocketmine\network\protocol\AnimatePacket;
use pocketmine\network\protocol\BatchPacket;
use pocketmine\network\protocol\ContainerClosePacket;
use pocketmine\network\protocol\DataPacket;
use pocketmine\network\protocol\DisconnectPacket;
use pocketmine\network\protocol\EntityEventPacket;
use pocketmine\network\protocol\FullChunkDataPacket;
use pocketmine\network\protocol\Info as ProtocolInfo;
use pocketmine\network\protocol\Info;
use pocketmine\network\protocol\PEPacket;
use pocketmine\network\protocol\PlayerActionPacket;
use pocketmine\network\protocol\PlayStatusPacket;
use pocketmine\network\protocol\PlayerListPacket;
use pocketmine\network\protocol\RespawnPacket;
use pocketmine\network\protocol\SetEntityDataPacket;
use pocketmine\network\protocol\TextPacket;
use pocketmine\network\protocol\MovePlayerPacket;
use pocketmine\network\protocol\SetDifficultyPacket;
use pocketmine\network\protocol\SetEntityMotionPacket;
use pocketmine\network\protocol\SetSpawnPositionPacket;
use pocketmine\network\protocol\SetTimePacket;
use pocketmine\network\protocol\StartGamePacket;
use pocketmine\network\protocol\TakeItemEntityPacket;
use pocketmine\network\protocol\TransferPacket;
use pocketmine\network\protocol\UpdateAttributesPacket;
use pocketmine\network\protocol\SetHealthPacket;
use pocketmine\network\protocol\UpdateBlockPacket;
use pocketmine\network\protocol\ChunkRadiusUpdatePacket;
use pocketmine\network\protocol\InteractPacket;
use pocketmine\network\protocol\ResourcePackChunkDataPacket;
use pocketmine\network\SourceInterface;
use pocketmine\permission\PermissibleBase;
use pocketmine\permission\PermissionAttachment;
use pocketmine\plugin\Plugin;
use pocketmine\scheduler\CallbackTask;
use pocketmine\tile\ItemFrame;
use pocketmine\tile\Sign;
use pocketmine\tile\Spawnable;
use pocketmine\tile\Tile;
use pocketmine\utils\TextFormat;
use pocketmine\network\protocol\SetPlayerGameTypePacket;
use pocketmine\block\Liquid;
use pocketmine\network\protocol\SetCommandsEnabledPacket;
use pocketmine\network\protocol\AvailableCommandsPacket;
use pocketmine\network\protocol\ResourcePackDataInfoPacket;
use pocketmine\network\protocol\ResourcePacksInfoPacket;
use pocketmine\network\protocol\ResourcePackStackPacket;
use pocketmine\network\protocol\ServerToClientHandshakePacket;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\Elytra;
use pocketmine\network\protocol\SetTitlePacket;
use pocketmine\network\protocol\ResourcePackClientResponsePacket;
use pocketmine\network\protocol\LevelSoundEventPacket;

use pocketmine\network\protocol\v120\InventoryTransactionPacket;
use pocketmine\network\protocol\v120\Protocol120;
use pocketmine\network\multiversion\MultiversionEnums;
use pocketmine\network\protocol\LevelEventPacket;

use pocketmine\network\protocol\v120\ShowModalFormPacket;
use pocketmine\network\protocol\v120\ServerSettingsResponsetPacket;
use pocketmine\network\protocol\v120\PlayerSkinPacket;
use pocketmine\network\protocol\AddPlayerPacket;
use pocketmine\network\protocol\RemoveEntityPacket;
use pocketmine\network\protocol\v120\SubClientLoginPacket;
use pocketmine\utils\Binary;
use pocketmine\network\protocol\v310\NetworkChunkPublisherUpdatePacket;
use pocketmine\network\multiversion\Entity as MultiversionEntity;
use pocketmine\entity\Vehicle;
use pocketmine\network\protocol\GameRulesChangedPacket;
use pocketmine\player\PlayerSettingsTrait;
use pocketmine\event\entity\EntityLevelChangeEvent;
use pocketmine\event\inventory\InventoryCreationEvent;
use pocketmine\network\protocol\v120\InventoryContentPacket;
use pocketmine\network\protocol\v331\BiomeDefinitionListPacket;
use pocketmine\network\protocol\v310\AvailableEntityIdentifiersPacket;
use function rand;
use function random_int;

/**
 * Main class that handles networking, recovery, and packet sending to the server part
 */
class Player extends Human implements CommandSender, InventoryHolder, IPlayer {

	use PlayerSettingsTrait;

    const OS_ANDROID = 1;
    const OS_IOS = 2;
    const OS_OSX = 3;
    const OS_FIREOS = 4;
    const OS_GEARVR = 5;
    const OS_HOLOLENS = 6;
    const OS_WIN10 = 7;
    const OS_WIN32 = 8;
    const OS_DEDICATED = 9;
    const OS_TVOS = 10;
    const OS_ORBIS = 11;
    const OS_NX = 12;
    const OS_UNKNOWN = -1;

    const INVENTORY_CLASSIC = 0;
    const INVENTORY_POCKET = 1;

	const SURVIVAL = 0;
	const CREATIVE = 1;
	const ADVENTURE = 2;
	const SPECTATOR = 3;
	const VIEW = Player::SPECTATOR;

	const CRAFTING_DEFAULT = 0;
	const CRAFTING_WORKBENCH = 1;
	const CRAFTING_ANVIL = 2;
	const CRAFTING_ENCHANT = 3;

	const SURVIVAL_SLOTS = 36;
	const CREATIVE_SLOTS = 112;

	const DEFAULT_SPEED = 0.1;
	const MAXIMUM_SPEED = 0.5;

	const FOOD_LEVEL_MAX = 20;
	const EXHAUSTION_NEEDS_FOR_ACTION = 4;

	/** @var SourceInterface */
	protected $interface;

	public $spawned = false;
	public $loggedIn = false;
	public $dead = false;
	public $gamemode;
	public $lastBreak;

	/** @var Inventory */
	protected $currentWindow = null;
	protected $currentWindowId = -1;
	const MIN_WINDOW_ID = 2;

	protected $messageCounter = 2;

	protected $sendIndex = 0;

	private $clientSecret;

	/** @var Vector3 */
	public $speed = null;

	public $blocked = false;
	public $lastCorrect;

	public $craftingType = self::CRAFTING_DEFAULT;

	protected $isCrafting = false;

	/**
	 * @deprecated
	 * @var array
	 */
	public $loginData = [];

	public $creationTime = 0;

	protected $randomClientId;

	protected $lastMovement = 0;
	protected $connected = true;
	protected $ip;
	protected $removeFormat = true;
	protected $port;
	protected $username = '';
	protected $iusername = '';
	protected $displayName = '';
	protected $startAction = -1;
	public $protocol = ProtocolInfo::PROTOCOL_120;
	/** @var Vector3 */
	protected $sleeping = null;
	protected $clientID = null;

	public $usedChunks = [];
	protected $chunkLoadCount = 0;
	protected $loadQueue = [];
	protected $nextChunkOrderRun = 5;

	/** @var Player[] */
	protected $hiddenPlayers = [];
	protected $hiddenEntity = [];

	/** @var Vector3 */
	public $newPosition;

	protected $spawnThreshold = 9 * M_PI;
	/** @var null|Position */
	private $spawnPosition = null;

	protected $inAirTicks = 0;
	protected $startAirTicks = 5;

	protected $autoJump = true;

	private $checkMovement;
	protected $allowFlight = false;

	/**
	 * @var \pocketmine\scheduler\TaskHandler[]
	 */
	protected $tasks = [];

	/** @var PermissibleBase */
	private $perm = null;

	/** @var string*/
	protected $lastMessageReceivedFrom = "";

	protected $identifier;

	protected static $availableCommands = [];

	protected $movementSpeed = self::DEFAULT_SPEED;

	protected static $damegeTimeList = ['0.1' => 0, '0.15' => 0.4, '0.2' => 0.6, '0.25' => 0.8];

	protected $lastDamegeTime = 0;

	protected $isTeleportedForMoveEvent = false;

	private $isFirstConnect = true;

	const MAX_EXPERIENCE = 1.0; // experience is percents
	const MAX_EXPERIENCE_LEVEL = PHP_INT_MAX;
	private $exp = 0;
	private $expLevel = 0;

	private $elytraIsActivated = false;

    /** @IMPORTANT don't change the scope */
    private $inventoryType = self::INVENTORY_CLASSIC;
	private $languageCode = false;

    /** @IMPORTANT don't change the scope */
    private $deviceType = self::OS_UNKNOWN;

	private $messageQueue = [];

	private $noteSoundQueue = [];

    private $xuid = '';

	private $ping = 0;

    protected $xblName = '';

	protected $viewRadius = 3;

	protected $identityPublicKey = '';

	private $actionsNum = [];

	protected $serverAddress = '';

	protected $clientVersion = '';

	protected $originalProtocol = 0;

	protected $lastModalId = 1;

	/** @var CustomUI[] */
	protected $activeModalWindows = [];
	/** @var integer */
	protected $lastShowModalTick = 0;

	/** @var int */
   protected $formIdCounter = 0;
	/** @var Form[] */
	protected $forms = [];

	protected $isTeleporting = false;
	/** @var Player[] */
	protected $subClients = [];
	/** @var integer */
	protected $subClientId = 0;
	/** @var Player */
	protected $parent = null;

	/** @var float value for player food bar*/
	private $foodLevel = 20.0;
	/** @var float */
	private $saturation = 5.0;

    /** @var float */
    private $absorption = 0.0;

	public function setSaturation(float $saturation) {
	    $this->saturation = $saturation;
    }

    public function getSaturarion(): float {
	    return $this->saturation;
    }
	/** @var float */
	private $exhaustion = 0.0;
	/** @var integer */
	protected $foodTick = 0;
	/** @var boolean */
	protected $hungerEnabled = true;
	
	protected $currentVehicle = null;
	protected $fishingHook = null;
	protected $interactButtonText= '';
	protected $isFlying = false;

	protected $beforeSpawnViewRadius = null;
	protected $beforeSpawnTeleportPosition = null;

	protected $lastInteractTick = 0;

	private $lastInteractCoordsHash = -1;

	protected $entitiesUUIDEids = [];
	protected $lastEntityRemove = [];
	protected $entitiesPacketsQueue = [];
	protected $packetQueue = [];
	protected $inventoryPacketQueue = [];
	protected $lastMoveBuffer = '';
	protected $countMovePacketInLastTick = 0;

	protected $commandPermissions = AdventureSettingsPacket::COMMAND_PERMISSION_LEVEL_ANY;
	protected $isTransfered = false;
	protected $loginCompleted = false;
	protected $titleData = [];

	/** @var string[][] - key - tick, value - packet's buffers array */
	protected $delayedPackets = [];
	protected $editingSignData = [];
	protected $scoreboard = null;
	protected $commandsData = [];
	protected $joinCompleted = false;
	protected $platformChatId = "";
	protected $doDaylightCycle = true;
	private $lastQuickCraftTransactionGroup = [];
	protected $additionalSkinData = [];

	public function getLeaveMessage(){
		return "";
	}

	/**
	 * This might disappear in the future.
	 * Please use getUniqueId() instead (IP + clientId + name combo, in the future it'll change to real UUID for online auth)
	 *
	 * @deprecated
	 *
	 */
	public function getClientId(){
		return $this->randomClientId;
	}

	public function getClientSecret(){
		return $this->clientSecret;
	}

	public function sendForm(Form $form) : void {
		$id = $this->formIdCounter++;
		$pk = new ShowModalFormPacket();
    	$pk->formId = $id;
		$pk->data = json_encode($form);
		if($pk->data === false){

			throw new \InvalidArgumentException("Failed to encode form JSON: " . json_last_error_msg());

		}

		if($this->dataPacket($pk)){

			$this->forms[$id] = $form;
		}
	}


	public function onFormSubmit(int $formId, $responseData) : bool{
		if(!isset($this->forms[$formId])){
			$this->server->getLogger()->debug("Got unexpected response for form $formId");
			return false;
		}

		try{
			$this->forms[$formId]->handleResponse($this, $responseData);
		}catch(FormValidationException $e){
			$this->server->getLogger()->critical("Failed to validate form " . get_class($this->forms[$formId]) . ": " . $e->getMessage());
			$this->server->getLogger()->logException($e);
		}finally{
			unset($this->forms[$formId]);
		}

		return true;
	}

	public function addTitle(string $title, string $subtitle = "", int $fadeIn = -1, int $stay = -1, int $fadeOut = -1){
		$this->setTitleDuration($fadeIn, $stay, $fadeOut);
		if($subtitle !== ""){
			$this->addSubTitle($subtitle);
		}
		$this->sendTitleText($title, SetTitlePacket::TITLE_TYPE_TITLE);
	}

	/**
	 * Sets the subtitle message, without sending a title.
	 *
	 * @param string $subtitle
	 */
	public function addSubTitle(string $subtitle){
		$this->sendTitleText($subtitle, SetTitlePacket::TITLE_TYPE_SUBTITLE);
	}

	/**
	 * Adds small text to the user's screen.
	 *
	 * @param string $message
	 */
	public function addActionBarMessage(string $message){
		$this->sendTitleText($message, SetTitlePacket::TITLE_TYPE_ACTION_BAR);
	}

	/**
	 * Removes the title from the client's screen.
	 */
	public function removeTitles(){
		$pk = new SetTitlePacket();
		$pk->type = SetTitlePacket::TITLE_TYPE_CLEAR;
		$this->dataPacket($pk);
	}

	/**
	 * Resets the title duration settings to defaults and removes any existing titles.
	 */
	public function resetTitles(){
		$pk = new SetTitlePacket();
		$pk->type = SetTitlePacket::TITLE_TYPE_RESET;
		$this->dataPacket($pk);
	}

	/**
	 * Sets the title duration.
	 *
	 * @param int $fadeIn Title fade-in time in ticks.
	 * @param int $stay Title stay time in ticks.
	 * @param int $fadeOut Title fade-out time in ticks.
	 */
	public function setTitleDuration(int $fadeIn, int $stay, int $fadeOut){
		if($fadeIn >= 0 and $stay >= 0 and $fadeOut >= 0){
			$pk = new SetTitlePacket();
			$pk->type = SetTitlePacket::TITLE_TYPE_TIMES;
			$pk->fadeInTime = $fadeIn;
			$pk->stayTime = $stay;
			$pk->fadeOutTime = $fadeOut;
			$this->dataPacket($pk);
		}
	}

	/**
	 * Internal function used for sending titles.
	 *
	 * @param string $title
	 * @param int    $type
	 */
	protected function sendTitleText(string $title, int $type){
		$pk = new SetTitlePacket();
		$pk->type = $type;
		$pk->text = $title;
		$this->dataPacket($pk);
	}


	public function isBanned(){
		return $this->server->getNameBans()->isBanned(strtolower($this->getName()));
	}

	public function setBanned($value){
		if($value === true){
			$this->server->getNameBans()->addBan($this->getName(), null, null, null);
			$this->kick("You have been banned");
		}else{
			$this->server->getNameBans()->remove($this->getName());
		}
	}

	public function isWhitelisted(){
		return $this->server->isWhitelisted(strtolower($this->getName()));
	}

	public function setWhitelisted($value){
		if($value === true){
			$this->server->addWhitelist(strtolower($this->getName()));
		}else{
			$this->server->removeWhitelist(strtolower($this->getName()));
		}
	}

	public function getPlayer(){
		return $this;
	}

	public function getFirstPlayed(){
		return $this->namedtag instanceof Compound ? $this->namedtag["firstPlayed"] : null;
	}

	public function getLastPlayed(){
		return $this->namedtag instanceof Compound ? $this->namedtag["lastPlayed"] : null;
	}

	public function hasPlayedBefore(){
		return $this->namedtag instanceof Compound;
	}

	public function setAllowFlight($value){
		$this->allowFlight = (bool) $value;
		$this->sendSettings();
	}

	public function getAllowFlight(){
		return $this->allowFlight;
	}

	public function setAutoJump($value){
		$this->autoJump = $value;
		$this->sendSettings();
	}

	public function hasAutoJump(){
		return $this->autoJump;
	}

	/**
	 * @param Player $player
	 */
	public function spawnTo(Player $player){
		if($this->spawned === true and $player->spawned === true and $this->dead !== true and $player->dead !== true and $player->getLevel() === $this->level and $player->canSee($this) and !$this->isSpectator()){
			parent::spawnTo($player);
		}
	}

	/**
	 * @return Server
	 */
	public function getServer(){
		return $this->server;
	}

	/**
	 * @return bool
	 */
	public function getRemoveFormat(){
		return $this->removeFormat;
	}

	/**
	 * @param bool $remove
	 */
	public function setRemoveFormat($remove = true){
		$this->removeFormat = (bool) $remove;
	}

	/**
	 * @param Player $player
	 *
	 * @return bool
	 */
	public function canSee(Player $player){
		return !isset($this->hiddenPlayers[$player->getName()]);
	}

	/**
	 * @param Player $player
	 */
	public function hidePlayer(Player $player){
		if($player === $this){
			return;
		}
		$this->hiddenPlayers[$player->getName()] = $player;
		$player->despawnFrom($this);
	}

	/**
	 * @param Player $player
	 */
	public function showPlayer(Player $player){
		if($player === $this){
			return;
		}
		unset($this->hiddenPlayers[$player->getName()]);
		if($player->isOnline()){
			$player->spawnTo($this);
		}
	}

	public function canCollideWith(Entity $entity){
		return false;
	}

	public function resetFallDistance(){
		parent::resetFallDistance();
		if($this->inAirTicks !== 0){
			$this->startAirTicks = 5;
		}
		$this->inAirTicks = 0;
	}

	/**
	 * @return bool
	 */
	public function isOnline(){
		return $this->connected === true and $this->loggedIn === true;
	}

	/**
	 * @return bool
	 */
	public function isOp(){
		return $this->server->isOp($this->getName());
	}

	/**
	 * @param bool $value
	 */
	public function setOp($value){
		if($value === $this->isOp()){
			return;
		}

		if($value === true){
			$this->server->addOp($this->getName());
		}else{
			$this->server->removeOp($this->getName());
		}

		$this->recalculatePermissions();
	}

	/**
	 * @param permission\Permission|string $name
	 *
	 * @return bool
	 */
	public function isPermissionSet($name){
		return $this->perm->isPermissionSet($name);
	}

	/**
	 * @param permission\Permission|string $name
	 *
	 * @return bool
	 */
	public function hasPermission($name){
		return $this->perm->hasPermission($name);
	}

	/**
	 * @param Plugin $plugin
	 * @param string $name
	 * @param bool   $value
	 *
	 * @return permission\PermissionAttachment
	 */
	public function addAttachment(Plugin $plugin, $name = null, $value = null){
		return $this->perm->addAttachment($plugin, $name, $value);
	}

	/**
	 * @param PermissionAttachment $attachment
	 */
	public function removeAttachment(PermissionAttachment $attachment){
		$this->perm->removeAttachment($attachment);
	}

	public function recalculatePermissions(){
		$this->server->getPluginManager()->unsubscribeFromPermission(Server::BROADCAST_CHANNEL_USERS, $this);
		$this->server->getPluginManager()->unsubscribeFromPermission(Server::BROADCAST_CHANNEL_ADMINISTRATIVE, $this);

		if($this->perm === null){
			return;
		}

		$this->perm->recalculatePermissions();

		if($this->hasPermission(Server::BROADCAST_CHANNEL_USERS)){
			$this->server->getPluginManager()->subscribeToPermission(Server::BROADCAST_CHANNEL_USERS, $this);
		}
		if($this->hasPermission(Server::BROADCAST_CHANNEL_ADMINISTRATIVE)){
			$this->server->getPluginManager()->subscribeToPermission(Server::BROADCAST_CHANNEL_ADMINISTRATIVE, $this);
		}
	}

	/**
	 * @return permission\PermissionAttachmentInfo[]
	 */
	public function getEffectivePermissions(){
		return $this->perm->getEffectivePermissions();
	}


	/**
	 * @param SourceInterface $interface
	 * @param null            $clientID
	 * @param string          $ip
	 * @param integer         $port
	 */
	public function __construct(SourceInterface $interface, $clientID, $ip, $port){
		$this->interface = $interface;
		$this->perm = new PermissibleBase($this);
		$this->namedtag = new Compound();
		$this->server = Server::getInstance();
		$this->lastBreak = 0;
		$this->ip = $ip;
		$this->port = $port;
		$this->clientID = $clientID;
		$this->spawnPosition = null;
		$this->gamemode = $this->server->getGamemode();
		$this->setLevel($this->server->getDefaultLevel(), true);
		$this->newPosition = null;
		$this->checkMovement = (bool) $this->server->getAdvancedProperty("main.check-movement", true);
		$this->boundingBox = new AxisAlignedBB(0, 0, 0, 0, 0, 0);

		$this->uuid = null;
		$this->rawUUID = null;

		$this->creationTime = microtime(true);

		if (empty(self::$availableCommands)) {
			self::$availableCommands = $this->server->getJsonCommands();
			$plugins = $this->server->getPluginManager()->getPlugins();
			foreach ($plugins as $pluginName => $plugin) {
				$pluginCommands = $plugin->getJsonCommands();
				self::$availableCommands = array_merge(self::$availableCommands, $pluginCommands);
			}
			AvailableCommandsPacket::prepareCommands(self::$availableCommands);
		}
		$this->createInventory();
		$this->setDataFlag(self::DATA_FLAGS, self::DATA_FLAG_HAS_COLLISION, true, self::DATA_TYPE_LONG, false);
		$this->setDataFlag(self::DATA_FLAGS, self::DATA_FLAG_AFFECTED_BY_GRAVITY, true, self::DATA_TYPE_LONG, false);
	}

	protected function createInventory() {
		$inventoryClass = PlayerInventory::class;
		$event = new InventoryCreationEvent(PlayerInventory::class, $inventoryClass, $this);
		$this->server->getPluginManager()->callEvent($event);
		$class = $event->getInventoryClass();
		$this->inventory = new $class($this);
	}

	public function setViewRadius($radius) {
		if (!$this->spawned) {
			$this->beforeSpawnViewRadius = $radius;
		} else {
			$this->viewRadius = $radius;
		}
	}

	/**
	 * @return bool
	 */
	public function isConnected(){
		return $this->connected === true;
	}

	/**
	 * Gets the "friendly" name to display of this player to use in the chat.
	 *
	 * @return string
	 */
	public function getDisplayName(){
		return $this->displayName;
	}

	/**
	 * @param string $name
	 */
	public function setDisplayName($name){
		$this->displayName = $name;
	}

	/**
	 * @return string
	 */
	public function getNameTag(){
		return $this->nameTag;
	}

	/**
	 * Gets the player IP address
	 *
	 * @return string
	 */
	public function getAddress(){
		return $this->ip;
	}

	/**
	 * @return int
	 */
	public function getPort(){
		return $this->port;
	}

	/**
	 * @return bool
	 */
	public function isSleeping(){
		return $this->sleeping !== null;
	}

	public function unloadChunk($x, $z){
		$index = Level::chunkHash($x, $z);
		if(isset($this->usedChunks[$index])){
			foreach($this->level->getChunkEntities($x, $z) as $entity){
				if($entity !== $this){
					$entity->despawnFrom($this);
				}
			}

			unset($this->usedChunks[$index]);
		}
		$this->level->freeChunk($x, $z, $this);
		unset($this->loadQueue[$index]);
	}

	/**
	 * @return Position
	 */
	public function getSpawn(){
		if($this->spawnPosition instanceof Position and $this->spawnPosition->getLevel() instanceof Level){
			return $this->spawnPosition;
		}else{
			$level = $this->server->getDefaultLevel();

			return $level->getSafeSpawn();
		}
	}

	public function useChunk($x, $z){
		$this->usedChunks[Level::chunkHash($x, $z)] = true;
		$this->chunkLoadCount++;
		if($this->spawned){
			foreach($this->level->getChunkEntities($x, $z) as $entity){
				if($entity !== $this and !$entity->closed and !$entity->dead and $this->canSeeEntity($entity)){
					$entity->spawnTo($this);
				}
			}
		}
	}

	protected function sendNextChunk(){
		if($this->connected === false){
			return;
		}

		$count = 0;
		foreach($this->loadQueue as $index => $distance){
			if($count >= 10){
				break;
			}
			$X = null;
			$Z = null;
			Level::getXZ($index, $X, $Z);

			++$count;

			unset($this->loadQueue[$index]);
			$this->usedChunks[$index] = false;

			$this->level->useChunk($X, $Z, $this);
			$this->level->requestChunk($X, $Z, $this);
			$this->useChunk($X, $Z);
			if($this->server->getAutoGenerate()){
				if(!$this->level->populateChunk($X, $Z, true)){
					if($this->spawned){
						continue;
					}else{
						break;
					}
				}
			}
		}

		if ((!$this->isFirstConnect || $this->chunkLoadCount >= $this->spawnThreshold) && $this->spawned === false) {
			$this->sendSettings();
			$this->sendPotionEffects($this);
			$this->sendData($this);
			$this->inventory->sendContents($this);
			$this->inventory->sendArmorContents($this);

			$pk = new SetTimePacket();
			$pk->time = $this->level->getTime();
			$pk->started = $this->level->stopTime == false;
			$this->dataPacket($pk);
			$this->setDaylightCycle(!$this->level->stopTime);

			$pk = new PlayStatusPacket();
			$pk->status = PlayStatusPacket::PLAYER_SPAWN;
			$this->dataPacket($pk);

			$this->noDamageTicks = 60;
			$this->spawned = true;
			$chunkX = $chunkZ = null;
			foreach ($this->usedChunks as $index => $c) {
				Level::getXZ($index, $chunkX, $chunkZ);
				foreach ($this->level->getChunkEntities($chunkX, $chunkZ) as $entity) {
					if ($entity !== $this && !$entity->closed && !$entity->dead && $this->canSeeEntity($entity)) {
						$entity->spawnTo($this);
					}
				}
			}
			$this->setInteractButtonText('', true);
			$this->server->getPluginManager()->callEvent($ev = new PlayerJoinEvent($this, ""));
			if (!is_null($this->beforeSpawnViewRadius)) {
				$this->setViewRadius($this->beforeSpawnViewRadius);
				$this->beforeSpawnViewRadius = null;
			}
			if (!is_null($this->beforeSpawnTeleportPosition)) {
				$this->teleport($this->beforeSpawnTeleportPosition[0], $this->beforeSpawnTeleportPosition[1], $this->beforeSpawnTeleportPosition[2]);
				$this->beforeSpawnTeleportPosition = null;
			}
			$this->nextChunkOrderRun = 1;
			$this->joinCompleted = true;
		}
	}

	protected function orderChunks() {
		if ($this->connected === false) {
			return false;
		}

		$this->nextChunkOrderRun = 200;
		$radiusSquared = $this->viewRadius ** 2;
		$centerX = $this->x >> 4;
		$centerZ = $this->z >> 4;
		$newOrder = [];
		$lastChunk = $this->usedChunks;

		for ($dx = 0; $dx < $this->viewRadius; $dx++) {
			for ($dz = 0; $dz < $this->viewRadius; $dz++) {
				if ($dx ** 2 + $dz ** 2 > $radiusSquared) {
					continue;
				}

				foreach ([$dx, (-$dx - 1)] as $ddx) {
					foreach ([$dz, (-$dz - 1)] as $ddz) {
						$chunkX = $centerX + $ddx;
						$chunkZ = $centerZ + $ddz;
						$index = Level::chunkHash($chunkX, $chunkZ);
						if (isset($lastChunk[$index])) {
							unset($lastChunk[$index]);
						} else {
							$newOrder[$index] = abs($dx) + abs($dz);
						}
					}
				}

			}
		}

		foreach ($lastChunk as $index => $Yndex) {
			$X = null;
			$Z = null;
			Level::getXZ($index, $X, $Z);
			$this->unloadChunk($X, $Z);
		}
		$this->loadQueue = $newOrder;

		if($this->protocol >= ProtocolInfo::PROTOCOL_310 && $this->spawned && !empty($newOrder)){
			$pk = new NetworkChunkPublisherUpdatePacket();
			$pk->x = $this->getFloorX();
			$pk->y = $this->getFloorY();
			$pk->z = $this->getFloorZ();
			$pk->radius = $this->viewRadius << 4;
			$this->dataPacket($pk);
		}
		return true;
	}

	/**
	 * Sends an ordered DataPacket to the send buffer
	 *
	 * @param DataPacket $packet
	 *
	 * @return int|bool
	 */
	public function dataPacket(DataPacket $packet){
		if($this->connected === false){
			return false;
		}

		if ($this->subClientId > 0 && $this->parent != null) {
			$packet->senderSubClientID = $this->subClientId;
			return $this->parent->dataPacket($packet);
		}
		
		switch($packet->pname()){
			case 'INVENTORY_CONTENT_PACKET':
				$queueKey = $packet->pname() . $packet->inventoryID;
				unset($this->inventoryPacketQueue[$queueKey]);
				$this->inventoryPacketQueue[$queueKey] = $packet;
				return;
			case 'INVENTORY_SLOT_PACKET':
				$queueKey = $packet->pname() . $packet->containerId . ':' . $packet->slot;
				unset($this->inventoryPacketQueue[$queueKey]);
				$this->inventoryPacketQueue[$queueKey] = $packet;
				return;
			case 'CONTAINER_OPEN_PACKET':
			case 'CONTAINER_CLOSE_PACKET':
				$queueKey = $packet->pname() . $packet->windowid;
				unset($this->inventoryPacketQueue[$queueKey]);
				$this->inventoryPacketQueue[$queueKey] = $packet;
				return;
			case 'BATCH_PACKET':
				$packet->encode($this->protocol);
				$this->interface->putReadyPacket($this, $packet->getBuffer());
				$packet->senderSubClientID = 0;
				return;
            case 'SCRIPT_CUSTOM_EVENT_PACKET':
                $packet->encode($this->protocol);
                $this->interface->newputPacket($this, $packet);
                $packet->senderSubClientID = 0;
                return;
			case 'ADD_PLAYER_PACKET':
			case 'ADD_ENTITY_PACKET':
			case 'ADD_ITEM_ENTITY_PACKET':
			case 'SET_ENTITY_DATA_PACKET':
			case 'MOB_EQUIPMENT_PACKET':
			case 'MOB_ARMOR_EQUIPMENT_PACKET':
			case 'ENTITY_EVENT_PACKET':
			case 'MOB_EFFECT_PACKET':
			case 'BOSS_EVENT_PACKET':
				if (isset($this->lastEntityRemove[$packet->eid])) {
					$this->addEntityPacket($packet->eid, $packet);
					return;
				}
				break;
			case 'REMOVE_ENTITY_PACKET':
				if (isset($this->entitiesPacketsQueue[$packet->eid])) {
					unset($this->entitiesPacketsQueue[$packet->eid]);
					return;
				}
				$this->lastEntityRemove[$packet->eid] = $this->lastUpdate;
				unset($this->entitiesUUIDEids[$packet->eid]);
				break;
			case 'PLAYER_LIST_PACKET':
				if (count($packet->entries) == 1) {
					$entryData = $packet->entries[0];
					if ($packet->type == PlayerListPacket::TYPE_ADD) {
						if (isset($this->lastEntityRemove[$entryData[1]])) {
							$this->addEntityPacket($entryData[1], $packet);
							$this->entitiesUUIDEids[$entryData[1]] = $entryData[0];
							return;
						}
					} elseif ($packet->type == PlayerListPacket::TYPE_REMOVE) {
						foreach ($this->entitiesUUIDEids as $eid => $uuid) {
							if ($entryData[0] === $uuid) {
								if (isset($this->lastEntityRemove[$eid])) {
									unset($this->entitiesUUIDEids[$eid]);
									$this->addEntityPacket($eid, $packet);
									return;
								}
							}
						}
					}
				}
				break;
			case 'UPDATE_ATTRIBUTES_PACKET':
				if (isset($this->lastEntityRemove[$packet->entityId])) {
					$this->addEntityPacket($packet->entityId, $packet);
					return;
				}
				break;
			case 'SET_ENTITY_LINK_PACKET':
				if (isset($this->lastEntityRemove[$packet->from])) {
					$this->addEntityPacket($packet->from, $packet);
					return;
				} elseif (isset($this->lastEntityRemove[$packet->to])) {
					$this->addEntityPacket($packet->to, $packet);
					return;
				}
				break;
		}
		$packet->encode($this->protocol);
		$this->packetQueue[] = $packet->getBuffer();
		$packet->senderSubClientID = 0;
		return true;
	}

	public function addBufferToPacketQueue($buffer) {
		if($this->connected === false){
			return false;
		}
		$this->packetQueue[] = $buffer;
	}

	public function sendPacketQueue() {
		if (count($this->packetQueue) <= 0 && count($this->inventoryPacketQueue) <= 0) {
			return;
		}
		$buffer = '';
		foreach ($this->packetQueue as $pkBuf) {
			$buffer .= Binary::writeVarInt(strlen($pkBuf)) . $pkBuf;
		}
		foreach ($this->inventoryPacketQueue as $pk) {
			$pk->encode($this->protocol);
			$pkBuf = $pk->getBuffer();
			$buffer .= Binary::writeVarInt(strlen($pkBuf)) . $pkBuf;
		}
		$this->inventoryPacketQueue= [];
		$this->packetQueue = [];
		$this->interface->putPacket($this, $buffer);
	}

	protected function addEntityPacket($eid, $pk) {
		$pk->encode($this->protocol);
		$this->entitiesPacketsQueue[$eid][] = $pk->getBuffer();
		$pk->senderSubClientID = 0;
	}

	/**
	 * @param DataPacket $packet
	 *
	 * @return bool|int
	 */
	public function directDataPacket(DataPacket $packet){
		if($this->connected === false){
			return false;
		}

		if ($this->subClientId > 0 && $this->parent != null) {
			$packet->senderSubClientID = $this->subClientId;
			return $this->parent->dataPacket($packet);
		}

		$packet->encode($this->protocol);
		$packet->senderSubClientID = 0;
		$buffer = $packet->getBuffer();
		$this->interface->putPacket($this, Binary::writeVarInt(strlen($buffer)) . $buffer);
		return true;
	}

	/**
	 * @param Vector3 $pos
	 *
	 * @return boolean
	 */
	public function sleepOn(Vector3 $pos){
		foreach($this->level->getNearbyEntities($this->boundingBox->grow(2, 1, 2), $this) as $p){
			if($p instanceof Player){
				if($p->sleeping !== null and $pos->distance($p->sleeping) <= 0.1){
					return false;
				}
			}
		}

		$this->server->getPluginManager()->callEvent($ev = new PlayerBedEnterEvent($this, $this->level->getBlock($pos)));
		if($ev->isCancelled()){
			return false;
		}

		$this->sleeping = clone $pos;
		$this->teleport(new Position($pos->x + 0.5, $pos->y - 0.5, $pos->z + 0.5, $this->level));

		$this->setDataProperty(self::DATA_PLAYER_BED_POSITION, self::DATA_TYPE_POS, [$pos->x, $pos->y, $pos->z]);
		$this->setDataFlag(self::DATA_PLAYER_FLAGS, self::DATA_PLAYER_FLAG_SLEEP, true);

		$this->setSpawn($pos);
		$this->tasks[] = $this->server->getScheduler()->scheduleDelayedTask(new CallbackTask([$this, "checkSleep"]), 60);

		return true;
	}

	/**
	 * Sets the spawnpoint of the player (and the compass direction) to a Vector3, or set it on another world with a Position object
	 *
	 * @param Vector3|Position $pos
	 */
	public function setSpawn(Vector3 $pos){
		if(!($pos instanceof Position)){
			$level = $this->level;
		}else{
			$level = $pos->getLevel();
		}
		$this->spawnPosition = new Position($pos->x, $pos->y, $pos->z, $level);
		$pk = new SetSpawnPositionPacket();
		$pk->x = (int) $this->spawnPosition->x;
		$pk->y = (int) $this->spawnPosition->y;
		$pk->z = (int) $this->spawnPosition->z;
		$this->dataPacket($pk);
	}

	public function stopSleep(){
		if($this->sleeping instanceof Vector3){
			$this->server->getPluginManager()->callEvent($ev = new PlayerBedLeaveEvent($this, $this->level->getBlock($this->sleeping)));

			$this->sleeping = null;
			$this->setDataFlag(self::DATA_PLAYER_FLAGS, self::DATA_PLAYER_FLAG_SLEEP, false);
			$this->setDataProperty(self::DATA_PLAYER_BED_POSITION, self::DATA_TYPE_POS, [0, 0, 0]);

			$this->level->sleepTicks = 0;

			$pk = new AnimatePacket();
			$pk->eid = $this->id;
			$pk->action = 3; //Wake up
			$this->dataPacket($pk);
		}

	}

	/**
	 * WARNING: Do not use this, it's only for internal use.
	 * Changes to this function won't be recorded on the version.
	 */
	public function checkSleep(){
		if($this->sleeping instanceof Vector3){
			//TODO: Move to Level

			$time = $this->level->getTime() % Level::TIME_FULL;

			if($time >= Level::TIME_NIGHT and $time < Level::TIME_SUNRISE){
				foreach($this->level->getPlayers() as $p){
					if($p->sleeping === null){
						return;
					}
				}

				$this->level->setTime($this->level->getTime() + Level::TIME_FULL - $time);

				foreach($this->level->getPlayers() as $p){
					$p->stopSleep();
				}
			}
		}
	}

	/**
	 * @return int
	 */
	public function getGamemode() {
		return $this->gamemode;
	}

	/**
	 * Sets the gamemode, and if needed, kicks the Player.
	 *
	 * @param int $gm
	 *
	 * @return bool
	 */
	public function setGamemode($gm) {
		if ($gm < 0 || $gm > 3 || $this->gamemode === $gm) {
			return false;
		}

		$this->server->getPluginManager()->callEvent($ev = new PlayerGameModeChangeEvent($this, (int) $gm));
		if ($ev->isCancelled()) {
			return false;
		}

		$this->gamemode = $gm;
		$this->allowFlight = $this->isCreative();

		if ($this->isSpectator()) {
			$this->despawnFromAll();
		}

		$this->namedtag->playerGameType = new IntTag("playerGameType", $this->gamemode);
		$pk = new SetPlayerGameTypePacket();
		$pk->gamemode = $this->gamemode == 3 ? 1 : $this->gamemode;
		$this->dataPacket($pk);
		$this->sendSettings();

		$this->inventory->sendContents($this);
		$this->inventory->sendContents($this->getViewers());
		$this->inventory->sendHeldItem($this->hasSpawned);

		return true;
	}

	/**
	 * Sends all the option flags
	 */
	public function sendSettings() {
		$flags = AdventureSettingsPacket::FLAG_NO_PVM | AdventureSettingsPacket::FLAG_NO_MVP;
		if ($this->autoJump) {
			$flags |= AdventureSettingsPacket::FLAG_AUTO_JUMP;
		}
		if ($this->allowFlight) {
			$flags |= AdventureSettingsPacket::FLAG_PLAYER_MAY_FLY;
		}
		if ($this->isSpectator()) {
			$flags |= AdventureSettingsPacket::FLAG_WORLD_IMMUTABLE;
			$flags |= AdventureSettingsPacket::FLAG_PLAYER_NO_CLIP;
		}

		$pk = new AdventureSettingsPacket();
		$pk->flags = $flags;
		$pk->userId = $this->getId();
		$pk->commandPermissions = $this->commandPermissions;
		$pk->permissionLevel = AdventureSettingsPacket::PERMISSION_LEVEL_CUSTOM;
		$pk->actionPermissions = $this->getActionFlags();
		$this->dataPacket($pk);
	}

	public function isSurvival(){
		return ($this->gamemode & 0x01) === 0;
	}

	public function isCreative(){
		return ($this->gamemode & 0x01) > 0;
	}

	public function isSpectator(){
		return $this->gamemode === 3;
	}

	public function isAdventure(){
		return ($this->gamemode & 0x02) > 0;
	}

	public function getDrops(){
		if(!$this->isCreative()){
			return parent::getDrops();
		}

		return [];
	}

	/**
	 * @deprecated
	 */
	public function addEntityMotion($entityId, $x, $y, $z){

	}

	/**
	 * @deprecated
	 */
	public function addEntityMovement($entityId, $x, $y, $z, $yaw, $pitch, $headYaw = null){

	}

//	public function setDataProperty($id, $type, $value){
//		if(parent::setDataProperty($id, $type, $value)){
//			$this->sendData([$this], [$id => $this->dataProperties[$id]]);
//			return true;
//		}
//
//		return false;
//	}

	protected function checkGroundState($movX, $movY, $movZ, $dx, $dy, $dz){
		/*
		if(!$this->onGround or $movY != 0){
			$bb = clone $this->boundingBox;
			$bb->maxY = $bb->minY + 0.5;
			$bb->minY -= 1;
			if(count($this->level->getCollisionBlocks($bb, true)) > 0){
				$this->onGround = true;
			}else{
				$this->onGround = false;
			}
		}
		$this->isCollided = $this->onGround;
		*/
	}

	protected function checkNearEntities($tickDiff){
		foreach($this->level->getNearbyEntities($this->boundingBox->grow(1, 0.5, 1), $this) as $entity){
			$entity->scheduleUpdate();

			if(!$entity->isAlive()){
				continue;
			}

			if($entity instanceof Arrow && $entity->hadCollision){
				$item = Item::get(Item::ARROW, 0, 1);
				if($this->isSurvival() and !$this->inventory->canAddItem($item)){
					continue;
				}

				$this->server->getPluginManager()->callEvent($ev = new InventoryPickupArrowEvent($this->inventory, $entity));
				if($ev->isCancelled()){
					continue;
				}

				$pk = new TakeItemEntityPacket();
				$pk->eid = $this->getId();
				$pk->target = $entity->getId();
				Server::broadcastPacket($entity->getViewers(), $pk);

				$this->inventory->addItem(clone $item);
				$entity->close();
			}elseif($entity instanceof DroppedItem){
				if($entity->getPickupDelay() <= 0){
					$item = $entity->getItem();

					if($item instanceof Item){
						if($this->isSurvival() and !$this->inventory->canAddItem($item)){
							continue;
						}

						$this->server->getPluginManager()->callEvent($ev = new InventoryPickupItemEvent($this->inventory, $entity));
						if($ev->isCancelled()){
							continue;
						}

						$pk = new TakeItemEntityPacket();
						$pk->eid = $this->getId();
						$pk->target = $entity->getId();
						Server::broadcastPacket($entity->getViewers(), $pk);

						$this->inventory->addItem(clone $item);
						$entity->kill();
					}
				}
			}
		}
	}

	protected $moving = false;

	public function setMoving($moving) {
		$this->moving = $moving;
	}

	public function isMoving(){
		return $this->moving;
	}

	public function setMotion(Vector3 $mot){
		if(parent::setMotion($mot)){
			if($this->chunk !== null){
				$pk = new SetEntityMotionPacket();
				$pk->entities[] = [$this->id, $mot->x, $mot->y, $mot->z];
				$this->dataPacket($pk);
				$viewers = $this->getViewers();
				$viewers[$this->getId()] = $this;
				Server::broadcastPacket($viewers, $pk);
			}

			if($this->motionY > 0){
				$this->startAirTicks = (-(log($this->gravity / ($this->gravity + $this->drag * $this->motionY))) / $this->drag) * 2 + 5;
			}

			return true;
		}
		return false;
	}

	public function onUpdate($currentTick){
		if(!$this->loggedIn){
			return false;
		}

		$tickDiff = $currentTick - $this->lastUpdate;

		if($tickDiff <= 0){
			return true;
		}

		$this->messageCounter = 2;
		$this->lastUpdate = $currentTick;
		//$this->timings->startTiming();

		// add to queue delayed packets
		foreach ($this->delayedPackets as $sendTick => $buffers) {
			if ($currentTick >= $sendTick) {
				foreach ($buffers as $buffer) {
					$this->addBufferToPacketQueue($buffer);
				}
				unset($this->delayedPackets[$sendTick]);
			}
		}

		if($this->nextChunkOrderRun-- <= 0 or $this->chunk === null){
			$this->orderChunks();
		}

		if(count($this->loadQueue) > 0 or !$this->spawned){
			$this->sendNextChunk();
		}

		if($this->dead === true and $this->spawned){
			++$this->deadTicks;
			if($this->deadTicks >= 10){
				$this->despawnFromAll();
			}
			//$this->timings->stopTiming();
			return $this->deadTicks < 10;
		}

		if($this->spawned){
			$this->processMovement($tickDiff);

			$this->entityBaseTick($tickDiff);

			if(!$this->isSpectator() and $this->speed !== null){
				if($this->onGround){
					if($this->inAirTicks !== 0){
						$this->startAirTicks = 5;
					}
					$this->inAirTicks = 0;
					if ($this->elytraIsActivated) {
						$this->setFlyingFlag(false);
						$this->setElytraActivated(false);
					}
				}else{
					if($this->needAntihackCheck() && !$this->isUseElytra() && !$this->allowFlight && !$this->isSleeping() && !$this->getDataFlag(self::DATA_FLAGS, self::DATA_FLAG_NOT_MOVE)){
						$expectedVelocity = (-$this->gravity) / $this->drag - ((-$this->gravity) / $this->drag) * exp(-$this->drag * ($this->inAirTicks - $this->startAirTicks));
						$diff = ($this->speed->y - $expectedVelocity) ** 2;

//						if(!$this->hasEffect(Effect::JUMP) and $diff > 0.6 and $expectedVelocity < $this->speed->y and !$this->server->getAllowFlight()){
//							if($this->inAirTicks < 301){
////								$this->setMotion(new Vector3(0, $expectedVelocity, 0));
//							}elseif($this->kick("Flying is not enabled on this server")){
//								//$this->timings->stopTiming();
//								return false;
//							}
//						}
						++$this->inAirTicks;
					}
				}
			}

			$this->doFood();
			$this->checkChunks();
		}

		if (count($this->messageQueue) > 0) {
			$message = array_shift($this->messageQueue);
			$pk = new TextPacket();
			$pk->type = TextPacket::TYPE_RAW;
			$pk->message = $message;
			$this->dataPacket($pk);
		}

		if (count($this->noteSoundQueue) > 0) {
			$noteId = array_shift($this->noteSoundQueue);
			$this->sendNoteSound($noteId);
		}

		foreach ($this->lastEntityRemove as $eid => $tick) {
			if ($tick + 20 < $this->lastUpdate) {
				unset($this->lastEntityRemove[$eid]);
				if (isset($this->entitiesPacketsQueue[$eid])) {
					$this->sendEntityPackets($this->entitiesPacketsQueue[$eid]);
					unset($this->entitiesPacketsQueue[$eid]);
				}
			}
		}

		if (!empty($this->titleData)) {
			$this->titleData['holdTickCount']--;
			if ($this->titleData['holdTickCount'] <= 0) {
				$this->sendTitle($this->titleData['text'], $this->titleData['subtext'], $this->titleData['time']);
				$this->titleData = [];
			}
		}

		foreach ($this->commandsData as $key => &$commandData) {
			$commandData['delay']--;
			if ($commandData['delay'] <= 0) {
				$this->processCommand($commandData['command']);
				unset($this->commandsData[$key]);
			}
		}

		//$this->timings->stopTiming();

		return true;
	}

	protected function sendEntityPackets($packets) {
		$this->packetQueue = array_merge($this->packetQueue, $packets);
	}

    public function doFood()
    {
        if ($this->getFoodEnabled()) {
            $foodLevel = $this->foodLevel;

            $this->foodTick++;
            if($this->foodTick >= 80){
                $this->foodTick = 0;
            }

            if ($this->exhaustion >= self::EXHAUSTION_NEEDS_FOR_ACTION) {
                $this->exhaustion = 0;

                if($this->saturation > 0){
                    $this->saturation--;
                }

                if ($this->saturation <= 0 && $this->foodLevel > 0){
                    $this->foodLevel--;
                }
            }

            if($this->getHealth() < $this->getMaxHealth()){
                if($this->saturation > 0 && $this->getFood() > 18){
                    if($this->foodTick % 10 === 0){
                        $ev = new EntityRegainHealthEvent($this, 1, EntityRegainHealthEvent::CAUSE_EATING);
                        $this->heal(1, $ev);
                        if (!$ev->isCancelled()) {
                            $this->saturation -= 6;
                        }
                    }
                } elseif($this->foodTick % 80 === 0){
                    if($this->getFood() > 17){
                        $ev = new EntityRegainHealthEvent($this, 1, EntityRegainHealthEvent::CAUSE_EATING);
                        $this->heal(1, $ev);
                        if (!$ev->isCancelled()) {
                            $this->saturation -= 6;
                        }
                    } elseif($this->getFood() === 0){
                        $ev = new EntityDamageEvent($this, EntityDamageEvent::CAUSE_HUNGER, 1);
                        $this->attack(1, $ev);
                    }
                }
                //todo: difficulty support?
            }

            if($this->foodLevel !== $foodLevel){ //fixes packet spam
                $this->setFood($this->foodLevel);

                if($this->foodLevel < 6){
                    $this->setSprinting(false);
                }
            }
        }
    }
	
	protected static $foodData = [
		Item::APPLE => ['food' => 4, 'saturation' => 2.4],
		Item::BAKED_POTATO => ['food' => 5, 'saturation' => 6],
		Item::BEETROOT => ['food' => 1, 'saturation' => 1.2],
		Item::BEETROOT_SOUP => ['food' => 6, 'saturation' => 7.2],
		Item::BREAD => ['food' => 5, 'saturation' => 6],
		/** @todo cake slice and whole */
		Item::CARROT => ['food' => 3, 'saturation' => 3.6],
		Item::CHORUS_FRUIT => ['food' => 4, 'saturation' => 2.4],
		Item::COOKED_CHICKEN => ['food' => 6, 'saturation' => 7.2],
		Item::COOKED_FISH => ['food' => 5, 'saturation' => 6],
		Item::COOKED_MUTTON => ['food' => 6, 'saturation' => 9.6],
		Item::COOKED_PORKCHOP => ['food' => 8, 'saturation' => 12.8],
		Item::COOKED_RABBIT => ['food' => 5, 'saturation' => 6],
		Item::COOKED_SALMON => ['food' => 6, 'saturation' => 9.6],
		Item::COOKIE => ['food' => 2, 'saturation' => 0.4],
		//Item::GOLDEN_APPLE => ['food' => 4, 'saturation' => 9.6],
		Item::GOLDEN_CARROT => ['food' => 6, 'saturation' => 14.4],
		Item::MELON => ['food' => 2, 'saturation' => 1.2],
		Item::MUSHROOM_STEW => ['food' => 6, 'saturation' => 7.2],
		Item::POISONOUS_POTATO => ['food' => 2, 'saturation' => 1.2],
		Item::POTATO => ['food' => 1, 'saturation' => 0.6],
		Item::PUMPKIN_PIE => ['food' => 8, 'saturation' => 4.8],
		Item::RABBIT_STEW => ['food' => 10, 'saturation' => 12],
		Item::RAW_BEEF => ['food' => 3, 'saturation' => 1.8],
		Item::RAW_CHICKEN => ['food' => 2, 'saturation' => 1.2],
		Item::RAW_FISH => [
			0 => ['food' => 2, 'saturation' => 0.4], // raw fish
			1 => ['food' => 2, 'saturation' => 0.4], // raw salmon
			2 => ['food' => 1, 'saturation' => 0.2], // clownfish
			3 => ['food' => 1, 'saturation' => 0.2], // pufferfish
		],
		Item::RAW_MUTTON => ['food' => 2, 'saturation' => 1.2],
		Item::RAW_PORKCHOP => ['food' => 3, 'saturation' => 1.8],
		Item::RAW_RABBIT => ['food' => 3, 'saturation' => 1.8],
		Item::ROTTEN_FLESH => ['food' => 4, 'saturation' => 0.8],
		Item::SPIDER_EYE => ['food' => 2, 'saturation' => 3.2],
		Item::STEAK => ['food' => 8, 'saturation' => 12.8],
	];

	public function eatFoodInHand() {
		if (!$this->spawned) {
			return;
		}

		$slot = $this->inventory->getItemInHand();
		if (isset(self::$foodData[$slot->getId()])) {
			$this->server->getPluginManager()->callEvent($ev = new PlayerItemConsumeEvent($this, $slot));
			if ($ev->isCancelled()) {
				$this->inventory->sendContents($this);
				$this->setFood($this->foodLevel);
				return;
			}

			$pk = new EntityEventPacket();
			$pk->eid = $this->getId();
			$pk->event = EntityEventPacket::USE_ITEM;
			$viewers = $this->getViewers();
			$viewers[] = $this;
			Server::broadcastPacket($viewers, $pk);

			--$slot->count;
			$this->inventory->setItemInHand($slot);

			// get food data
			$foodId = $slot->getId();
			$foodData = self::$foodData[$foodId];
			if (!isset($foodData['food'])) { // is food data is array by meta
				$foodMeta = $slot->getDamage();
				if (isset($foodData[$foodMeta])) {
					$foodData = $foodData[$foodMeta];
				} else {
					$this->setFood($this->foodLevel);
					return;
				}
			}
			// food logic
			$this->foodLevel = min(self::FOOD_LEVEL_MAX, $this->foodLevel + $foodData['food']);
			$this->saturation = min ($this->foodLevel, $this->saturation + $foodData['saturation']);
			$this->setFood($this->foodLevel);

            $position = [ 'x' => $this->x, 'y' => $this->y, 'z' => $this->z ];
            $this->sendSound("SOUND_BURP", $position, 63);

			switch ($foodId) {
				case Item::BEETROOT_SOUP:
				case Item::MUSHROOM_STEW:
				case Item::RABBIT_STEW:
					$this->inventory->addItem(Item::get(Item::BOWL, 0, 1));
					break;
				/*case Item::GOLDEN_APPLE:
					$this->addEffect(Effect::getEffect(Effect::REGENERATION)->setAmplifier(1)->setDuration(5 * 20));
//						$this->addEffect(Effect::getEffect(Effect::ABSORPTION)->setAmplifier(0)->setDuration(120 * 20));
					break;
				case Item::ENCHANTED_GOLDEN_APPLE:
					$this->addEffect(Effect::getEffect(Effect::REGENERATION)->setAmplifier(4)->setDuration(30 * 20));
//						$this->addEffect(Effect::getEffect(Effect::ABSORPTION)->setAmplifier(0)->setDuration(120 * 20));
					$this->addEffect(Effect::getEffect(Effect::DAMAGE_RESISTANCE)->setAmplifier(0)->setDuration(300 * 20));
					$this->addEffect(Effect::getEffect(Effect::FIRE_RESISTANCE)->setAmplifier(0)->setDuration(300 * 20));
					break;*/
			}
		} else {
			// error_log("Try to eat " . get_class($slot) . "(" . $slot->getId() . ")");
		}
	}

	/**
	 * Handles a Minecraft packet
	 * TODO: Separate all of this in handlers
	 *
	 * WARNING: Do not use this, it's only for internal use.
	 * Changes to this function won't be recorded on the version.
	 *
	 * @param DataPacket $packet
	 */
	public function handleDataPacket(DataPacket $packet){
		if($this->connected === false){
			return;
		}
		$beforeLoginAvailablePackets = ['LOGIN_PACKET', 'REQUEST_CHUNK_RADIUS_PACKET', 'RESOURCE_PACKS_CLIENT_RESPONSE_PACKET', 'CLIENT_TO_SERVER_HANDSHAKE_PACKET', 'RESOURCE_PACK_CHUNK_REQUEST_PACKET'];
		if (!$this->isOnline() && !in_array($packet->pname(), $beforeLoginAvailablePackets)) {
			return;
		}

		if ($packet->targetSubClientID > 0 && isset($this->subClients[$packet->targetSubClientID])) {
			$this->subClients[$packet->targetSubClientID]->handleDataPacket($packet);
			return;
		}

		switch($packet->pname()){
            case 'ITEM_FRAME_DROP_ITEM_PACKET':
                $tile = null;

                $tile = $this->getLevel()->getTile(new Vector3($packet->x, $packet->y, $packet->z));

                if(!$tile instanceof ItemFrame){
                    $nbt = new Compound("", [
                        new StringTag("id", Tile::ITEM_FRAME),
                        new IntTag("x", $this->x),
                        new IntTag("y", $this->y),
                        new IntTag("z", $this->z),
                        new ByteTag("ItemRotation", 0),
                        new FloatTag("ItemDropChance", 1.0)
                    ]);
                    $tile = Tile::createTile(Tile::ITEM_FRAME, $this->getLevel()->getChunk($this->x >> 4, $this->z >> 4), $nbt);
                }
                /*var_dump($packet->x);
                var_dump($packet->y);
                var_dump($packet->z);
                var_dump(new Vector3($packet->x, $packet->y, $packet->z));
                var_dump($tile);*/
                if($tile instanceof ItemFrame){
                    if($tile->getItem()->getId() !== Item::AIR){
                        $this->getServer()->getPluginManager()->callEvent($ev = new ItemFrameDropItemEvent($this->getLevel()->getBlock($tile), $this, $tile->getItem(), $tile->getItemDropChance()));
                        if(!$ev->isCancelled()){
                            if((mt_rand(0, 10) / 10) <= $tile->getItemDropChance()){
                                $this->level->dropItem($tile, $tile->getItem());
                            }
                            $tile->setItem(Item::get(Item::AIR));
                            $tile->setItemRotation(0);
                        }
                    }
                } else {
                    //$this->sendMessage("Error");
                }
                break;
            case 'SET_PLAYER_GAMETYPE_PACKET':
                file_put_contents("./logs/possible_hacks.log", date('m/d/Y h:i:s a', time()) . " SET_PLAYER_GAMETYPE_PACKET " . $this->username . PHP_EOL, FILE_APPEND | LOCK_EX);
                break;
            case 'UPDATE_ATTRIBUTES_PACKET':
                file_put_contents("./logs/possible_hacks.log", date('m/d/Y h:i:s a', time()) . " UPDATE_ATTRIBUTES_PACKET " . $this->username . PHP_EOL, FILE_APPEND | LOCK_EX);
                break;
            case 'ADVENTURE_SETTINGS_PACKET':
				if ($this->allowFlight === false && (($packet->flags >> 9) & 0x01 === 1)) { // flying hack
                    file_put_contents("./logs/possible_hacks.log", date('m/d/Y h:i:s a', time()) . " ADVENTURE_SETTINGS_PACKET FLY " . $this->username . PHP_EOL, FILE_APPEND | LOCK_EX);
//                    $this->kick("Sorry, hack mods are not permitted on Steadfast... at all.");
					// it may be not safe
					$this->sendSettings();
                }
				if (!$this->isSpectator() && (($packet->flags >> 7) & 0x01 === 1)) { // spectator hack
                    file_put_contents("./logs/possible_hacks.log", date('m/d/Y h:i:s a', time()) . " ADVENTURE_SETTINGS_PACKET SPC " . $this->username . PHP_EOL, FILE_APPEND | LOCK_EX);
//                    $this->kick("Sorry, hack mods are not permitted on Steadfast... at all.");
					$this->sendSettings();
                }
				$isFlying = ($packet->flags >> 9) & 0x01 === 1;
				if ($this->isFlying != $isFlying) {
					if ($isFlying) {
						$this->onStartFly();
					} else {
						$this->onStopFly();
					}
					$this->isFlying = $isFlying;
				}
                break;
			case 'LOGIN_PACKET':
				//Timings::$timerLoginPacket->startTiming();
				if($this->loggedIn === true){
					//Timings::$timerLoginPacket->stopTiming();
					break;
				}
				$this->protocol = $packet->protocol1; // we need protocol for correct encoding DisconnectPacket
				if($packet->isValidProtocol === false) {
					$this->close("", $this->getNonValidProtocolMessage($this->protocol));
					error_log("Login from unsupported protocol " . $this->protocol);
					//Timings::$timerLoginPacket->stopTiming();
					break;
				}
				if (!$packet->isVerified) {
					$this->close("", "Invalid Identity Public Key");
					// error_log("Invalid Identity Public Key " . $packet->username);
					break;
				}
				$this->username = TextFormat::clean($packet->username);
                $this->xblName = $this->username;
				$this->displayName = $this->username;
				$this->setNameTag($this->username);
				$this->iusername = strtolower($this->username);
				$this->randomClientId = $packet->clientId;
				$this->loginData = ["clientId" => $packet->clientId, "loginData" => null];
				$this->uuid = $packet->clientUUID;
				$this->subClientId = $packet->targetSubClientID;
				if (is_null($this->uuid)) {
					$this->close("", "Sorry, your client is broken.");
					//Timings::$timerLoginPacket->stopTiming();
					break;
				}
				$this->rawUUID = $this->uuid->toBinary();
				$this->clientSecret = $packet->clientSecret;
				$this->setSkin($packet->skin, $packet->skinName, $packet->skinGeometryName, $packet->skinGeometryData, $packet->capeData, $packet->premiunSkin);
                if ($packet->osType > 0) {
                    $this->deviceType = $packet->osType;
                }
                if ($packet->inventoryType >= 0) {
                    $this->inventoryType = $packet->inventoryType;
                }
                $this->xuid = $packet->xuid;
				$this->languageCode = $packet->languageCode;
				$this->serverAddress = $packet->serverAddress;
				$this->clientVersion = $packet->clientVersion;
				$this->originalProtocol = $packet->originalProtocol;

				$this->identityPublicKey = $packet->identityPublicKey;
				$this->platformChatId = $packet->platformChatId;
				$this->additionalSkinData = $packet->additionalSkinData;				
				$this->processLogin();
				//Timings::$timerLoginPacket->stopTiming();
				break;
			case 'MOVE_PLAYER_PACKET':
				foreach ($this->editingSignData as $hash => $data) {
					$x = $y = $z = null;
					Level::getBlockXYZ($hash, $x, $y, $z);
					$sign = $this->level->getTile(new Vector3($x, $y, $z));
					if ($sign instanceof Sign) {
						$this->checkSignChange($sign, $data);
					}
					unset($this->editingSignData[$hash]);
				}
				if ($this->dead !== true && $this->spawned === true) {
					$newPos = new Vector3($packet->x, $packet->y - $this->getEyeHeight(), $packet->z);
					if ($this->isTeleporting && $newPos->distanceSquared($this) > 2) {
						$this->isTeleporting = false;
						return;
					} else {
						if (!is_null($this->newPosition)) {
							$distanceSquared = ($newPos->x - $this->newPosition->x) ** 2 + ($newPos->z - $this->newPosition->z) ** 2;
						} else {
							$distanceSquared = ($newPos->x - $this->x) ** 2 + ($newPos->z - $this->z) ** 2;
						}
						if ($distanceSquared > $this->movementSpeed * 200 * ($this->countMovePacketInLastTick > 0 ? $this->countMovePacketInLastTick : 1)) {
							$this->revertMovement($this, $this->yaw, $this->pitch);
							$this->isTeleporting = true;
							return;
						}

						$this->isTeleporting = false;

						$packet->yaw %= 360;
						$packet->pitch %= 360;

						if ($packet->yaw < 0) {
							$packet->yaw += 360;
						}

						$this->setRotation($packet->yaw, $packet->pitch);
						$this->newPosition = $newPos;
					}
				}
				break;
			case 'MOVE_ENTITY_PACKET':
				if (!is_null($this->currentVehicle) && $this->currentVehicle->getId() == $packet->eid) {
					$this->currentVehicle->updateByOwner($packet->x, $packet->y, $packet->z, $packet->yaw, $packet->pitch);
				}
				break;
			case 'MOB_EQUIPMENT_PACKET':
				//Timings::$timerMobEqipmentPacket->startTiming();
				if($this->spawned === false or $this->dead === true){
					//Timings::$timerMobEqipmentPacket->stopTiming();
					break;
				}

				if ($this->protocol < ProtocolInfo::PROTOCOL_200) {
					if($packet->slot === 0 or $packet->slot === 255){ //0 for 0.8.0 compatibility
						$packet->slot = -1; //Air
					}else{
						$packet->slot -= 9; //Get real block slot
					}
				}

				$item = $this->inventory->getItem($packet->slot);
				$slot = $packet->slot;

				if($packet->slot === -1){ //Air
					if ($packet->selectedSlot >= 0 and $packet->selectedSlot < 9) {
						$this->changeHeldItem($packet->item, $packet->selectedSlot, $packet->slot);
						break;
					} else {
						$this->inventory->sendContents($this);
						//Timings::$timerMobEqipmentPacket->stopTiming();
						break;
					}
				}elseif($item === null || $slot === -1 || ($item->getId() != Item::FILLED_MAP && !$item->deepEquals($packet->item) || !$item->deepEquals($packet->item, true, false))){ // packet error or not implemented
					// hack for map was added because type of map_uuid is different in various versions
					$this->inventory->sendContents($this);
					//Timings::$timerMobEqipmentPacket->stopTiming();
					break;
				}else{
					if ($packet->selectedSlot >= 0 and $packet->selectedSlot < 9) {
						$this->changeHeldItem($packet->item, $packet->selectedSlot, $slot);
						break;
					} else {
						$this->inventory->sendContents($this);
						//Timings::$timerMobEqipmentPacket->stopTiming();
						break;
					}
				}

				$this->inventory->sendHeldItem($this->hasSpawned);

				$this->setDataFlag(self::DATA_FLAGS, self::DATA_FLAG_ACTION, false);
				//Timings::$timerMobEqipmentPacket->stopTiming();
				break;
			case 'LEVEL_SOUND_EVENT_PACKET':
				if ($packet->eventId == LevelSoundEventPacket::SOUND_UNDEFINED) {
					break;
				}
				$viewers = $this->getViewers();
				$viewers[] = $this;
				Server::broadcastPacket($viewers, $packet);
				break;
			case 'PLAYER_ACTION_PACKET':
				//Timings::$timerActionPacket->startTiming();
//				if($this->spawned === false or $this->blocked === true or ($this->dead === true and $packet->action !== 7)){
				if($this->spawned === false || $this->blocked === true){
					//Timings::$timerActionPacket->stopTiming();
					break;
				}

//				$this->craftingType = self::CRAFTING_DEFAULT;
				$action = MultiversionEnums::getPlayerAction($this->protocol, $packet->action);
				switch ($action) {
					case 'START_JUMP':
						if ($this->foodLevel > 0 && $this->getFoodEnabled()) {
							$this->exhaustion += $this->isSprinting() ? 0.2 : 0.05;
						}
						$this->onJump();
						break;
					case 'START_DESTROY_BLOCK':
						$this->actionsNum['CRACK_BLOCK'] = 0;
						if (!$this->isCreative()) {
							$block = $this->level->getBlock(new Vector3($packet->x, $packet->y, $packet->z));
							$breakTime = ceil($block->getBreakTime($this->inventory->getItemInHand()) * 20);
							$up = $block->getSide(1);
							if ($up->getId() === Block::FIRE) {
								$pk = new UpdateBlockPacket();
								$pk->records[] = [$up->getX(), $up->getZ(), $up->getY(), Block::FIRE, 0, UpdateBlockPacket::FLAG_ALL];
								$this->dataPacket($pk);
							}
							if ($breakTime > 0) {
								$pk = new LevelEventPacket();
								$pk->evid = LevelEventPacket::EVENT_START_BLOCK_CRACKING;
								$pk->x = $packet->x;
								$pk->y = $packet->y;
								$pk->z = $packet->z;
								$pk->data = (int) (65535 / $breakTime); // ????
								$viewers = $this->getViewers();
								$viewers[] = $this;
								Server::broadcastPacket($viewers, $pk);
							}
							
							$topBlockId = $this->level->getBlockIdAt($packet->x, $packet->y + 1, $packet->z);
							if ($topBlockId == Block::FIRE) {
								$fireBlock = $this->level->getBlock(new Vector3($packet->x, $packet->y + 1, $packet->z));
								$this->level->sendBlocks([$this], [$fireBlock], UpdateBlockPacket::FLAG_ALL_PRIORITY);
							}
						}
						break;
					case 'ABORT_DESTROY_BLOCK':
					case 'STOP_DESTROY_BLOCK':
						$this->actionsNum['CRACK_BLOCK'] = 0;
						$pk = new LevelEventPacket();
						$pk->evid = LevelEventPacket::EVENT_STOP_BLOCK_CRACKING;
						$pk->x = $packet->x;
						$pk->y = $packet->y;
						$pk->z = $packet->z;
						$viewers = $this->getViewers();
						$viewers[] = $this;
						Server::broadcastPacket($viewers, $pk);
						break;
					case 'RELEASE_USE_ITEM':
						$this->releaseUseItem();
						$this->startAction = -1;
						break;
					case 'STOP_SLEEPING':
						$this->stopSleep();
						break;
					case 'RESPAWN':
						if ($this->spawned === false or $this->isAlive() or !$this->isOnline()) {
							break;
						}
						if ($this->server->isHardcore()) {
							$this->setBanned(true);
							break;
						}
						$this->respawn();
						break;
					case 'START_SPRINTING':
						$this->setSprinting(true);
						break;
					case 'STOP_STRINTING':
						$this->setSprinting(false);
						break;
					case 'START_SNEAKING':
						$ev = new PlayerToggleSneakEvent($this, true);
						$this->server->getPluginManager()->callEvent($ev);
						if($ev->isCancelled()){
							$this->sendData($this);
						}else{
							$this->setSneaking(true);
						}
						break;
					case 'STOP_SNEAKING':
						$ev = new PlayerToggleSneakEvent($this, false);
						$this->server->getPluginManager()->callEvent($ev);
						if($ev->isCancelled()){
							$this->sendData($this);
						}else{
							$this->setSneaking(false);
						}
						break;
					case 'START_GLIDING':
						if ($this->isHaveElytra()) {
							$this->setFlyingFlag(true);
							$this->setElytraActivated(true);
							$this->resetFallDistance();
						}
						break;
					case 'STOP_GLIDING':
						$this->setFlyingFlag(false);
						$this->setElytraActivated(false);
						break;
					case 'CRACK_BLOCK':
						$this->crackBlock($packet);
						break;
				}

				$this->setDataFlag(self::DATA_FLAGS, self::DATA_FLAG_ACTION, false);
				//Timings::$timerActionPacket->stopTiming();
				break;
			case 'MOB_ARMOR_EQUIPMENT_PACKET':
				break;
			case 'INTERACT_PACKET':
				if ($packet->action === InteractPacket::ACTION_DAMAGE) {
					$this->attackByTargetId($packet->target);
				} elseif ($packet->action === InteractPacket::ACTION_SEE) {
					$target = $this->getLevel()->getEntity($packet->target);
					if ($target instanceof Vehicle) {
						$target->onNearPlayer($this);
					}
				} else {		
					if ($packet->action === 3) {
						$target = $this->getLevel()->getEntity($packet->target);
						if ($target instanceof Vehicle) {
							$target->dissMount();
						}
					}
					$this->customInteract($packet);
				}
				break;
			case 'ANIMATE_PACKET':
				//Timings::$timerAnimatePacket->startTiming();
				if ($this->spawned === false or $this->dead === true) {
					//Timings::$timerAnimatePacket->stopTiming();
					break;
				}
				$this->server->getPluginManager()->callEvent($ev = new PlayerAnimationEvent($this, $packet->action));
				if ($ev->isCancelled()) {
					//Timings::$timerAnimatePacket->stopTiming();
					break;
				}
				$pk = new AnimatePacket();
				$pk->eid = $packet->eid;
				$pk->action = $ev->getAnimationType();
				$pk->data = $packet->data;
				Server::broadcastPacket($this->getViewers(), $pk);
				//Timings::$timerAnimatePacket->stopTiming();
				break;
			case 'SET_HEALTH_PACKET': //Not used
				break;
			case 'ENTITY_EVENT_PACKET':
				//Timings::$timerEntityEventPacket->startTiming();
				if($this->spawned === false or $this->blocked === true or $this->dead === true){
					//Timings::$timerEntityEventPacket->stopTiming();
					break;
				}
//				$this->craftingType = self::CRAFTING_DEFAULT;

				$this->setDataFlag(self::DATA_FLAGS, self::DATA_FLAG_ACTION, false); //TODO: check if this should be true

				switch($packet->event){
					case EntityEventPacket::USE_ITEM: //Eating
						$this->eatFoodInHand();
						break;
					case EntityEventPacket::ENCHANT:
						if ($this->currentWindow instanceof EnchantInventory) {
							$enchantLevel = abs($packet->theThing);
							if ($this->expLevel >= $enchantLevel) {
								$this->removeExperience(0, $enchantLevel);					
								$this->currentWindow->setEnchantingLevel($enchantLevel);
							} else {
								$this->currentWindow->setItem(0, Item::get(Item::AIR));
								$this->currentWindow->setEnchantingLevel(0);
								$this->currentWindow->sendContents($this);
								$this->inventory->sendContents($this);
							}
						}
						break;
					case EntityEventPacket::FEED:
                        $position = [ 'x' => $this->x, 'y' => $this->y, 'z' => $this->z ];

                        $slot = $this->inventory->getItemInHand();
                        if($slot instanceof Potion || $slot->getId() == Item::BUCKET && $slot->getDamage() == 1) {
                            $this->sendSound('SOUND_DRINK', $position, 63);
                        } else {
                            $this->sendSound(LevelSoundEventPacket::SOUND_EAT, $position, 63);
                        }

						break;
				}
				//Timings::$timerEntityEventPacket->stopTiming();
				break;
			case 'TEXT_PACKET':
				//Timings::$timerTextPacket->startTiming();
				if($this->spawned === false or $this->dead === true){
					//Timings::$timerTextPacket->stopTiming();
					break;
				}
//				$this->craftingType = self::CRAFTING_DEFAULT;
				if($packet->type === TextPacket::TYPE_CHAT){
					$packet->message = TextFormat::clean($packet->message, $this->removeFormat);
					foreach(explode("\n", $packet->message) as $message){
						$message = trim($message);
						if($message != "" and strlen($message) <= 255 and $this->messageCounter-- > 0){
							$this->server->getPluginManager()->callEvent($ev = new PlayerChatEvent($this, $message));
							if(!$ev->isCancelled()){
								$this->server->broadcastMessage($ev->getFormat(), $ev->getRecipients());
							}
						}
					}
				} else {
					echo "Recive message with type ".$packet->type.PHP_EOL;
				}
				//Timings::$timerTextPacket->stopTiming();
				break;
			case 'CONTAINER_CLOSE_PACKET':
				//Timings::$timerContainerClosePacket->startTiming();
				if ($this->spawned === false || $packet->windowid === 0){
					break;
				}
				$this->craftingType = self::CRAFTING_DEFAULT;
				$this->currentTransaction = null;
				// @todo добавить обычный инвентарь и броню
				if ($packet->windowid === $this->currentWindowId && $this->currentWindow != null) {
					$this->server->getPluginManager()->callEvent(new InventoryCloseEvent($this->currentWindow, $this));
					$this->removeWindow($this->currentWindow);
				}
				// duck tape
				if ($packet->windowid == 0xff) { // player inventory and workbench
					$this->onCloseSelfInventory();
					$this->inventory->close($this);
				}
				//Timings::$timerContainerClosePacket->stopTiming();
				break;
			case 'CRAFTING_EVENT_PACKET':
				//Timings::$timerCraftingEventPacket->startTiming();
				if ($this->spawned === false or $this->dead) {
					//Timings::$timerCraftingEventPacket->stopTiming();
					break;
				}
				if ($packet->windowId > 0 && $packet->windowId !== $this->currentWindowId) {
					$this->inventory->sendContents($this);
					$pk = new ContainerClosePacket();
					$pk->windowid = $packet->windowId;
					$this->dataPacket($pk);
					//Timings::$timerCraftingEventPacket->stopTiming();
					break;
				}

				$recipe = $this->server->getCraftingManager()->getRecipe($packet->id);
				$result = $packet->output[0];

				if (!($result instanceof Item)) {
					$this->inventory->sendContents($this);
					//Timings::$timerCraftingEventPacket->stopTiming();
					break;
				}

				if (is_null($recipe) || !$result->deepEquals($recipe->getResult(), true, false) ) { //hack for win10
					$newRecipe = $this->server->getCraftingManager()->getRecipeByHash($result->getId() . ":" . $result->getDamage());
					if (!is_null($newRecipe)) {
						$recipe = $newRecipe;
					}
				}
				try {
					$scale = floor($packet->output[0]->getCount() / $recipe->getResult()->getCount());
					if ($scale > 1) {
						$recipe = clone $recipe;
						$recipe->scale($scale);
					}
					if ($this->inventory->isQuickCraftEnabled()) {
						$craftSlots = $this->inventory->getQuckCraftContents();
						$this->tryApplyQuickCraft($craftSlots, $recipe);
						$this->inventory->setItem(PlayerInventory::CRAFT_RESULT_INDEX, $recipe->getResult());
						foreach ($craftSlots as $slot => $item) {
							$this->inventory->setItem(PlayerInventory::QUICK_CRAFT_INDEX_OFFSET - $slot, $item);
						}
						$this->inventory->setQuickCraftMode(false);
					} else {
						$craftSlots = $this->inventory->getCraftContents();
						$this->tryApplyCraft($craftSlots, $recipe);
						$this->inventory->setItem(PlayerInventory::CRAFT_RESULT_INDEX, $recipe->getResult());
						foreach ($craftSlots as $slot => $item) {
							$this->inventory->setItem(PlayerInventory::CRAFT_INDEX_0 - $slot, $item);
						}
					}
					if (!empty($this->lastQuickCraftTransactionGroup)) {
						foreach ($this->lastQuickCraftTransactionGroup as $trGroup) {
							if (!$trGroup->execute()) {
								$trGroup->sendInventories();
							}
						}
						$this->lastQuickCraftTransactionGroup = [];
					}
				} catch (\Exception $e) {
					$pk = new ContainerClosePacket();
					$pk->windowid = Protocol120::CONTAINER_ID_INVENTORY;
					$this->dataPacket($pk);
					$this->lastQuickCraftTransactionGroup = [];
				}
				break;
			case 'TILE_ENTITY_DATA_PACKET':
				//Timings::$timerTileEntityPacket->startTiming();
				if($this->spawned === false or $this->blocked === true or $this->dead === true){
					//Timings::$timerTileEntityPacket->stopTiming();
					break;
				}
//				$this->craftingType = self::CRAFTING_DEFAULT;

				$pos = new Vector3($packet->x, $packet->y, $packet->z);
				if($pos->distanceSquared($this) > 10000){
					//Timings::$timerTileEntityPacket->stopTiming();
					break;
				}

				$t = $this->level->getTile($pos);
				if ($t instanceof Sign) {
					$this->editingSignData[Level::blockHash($packet->x, $packet->y, $packet->z)] = $packet->namedtag;
				}
				//Timings::$timerTileEntityPacket->stopTiming();
				break;
			case 'REQUEST_CHUNK_RADIUS_PACKET':
				//Timings::$timerChunkRudiusPacket->startTiming();
				if ($packet->radius > 12) {
					$packet->radius = 12;
				} elseif ($packet->radius < 3) {
					$packet->radius = 3;
				}
				$this->setViewRadius($packet->radius);
				$pk = new ChunkRadiusUpdatePacket();
				$pk->radius = $packet->radius;
				$this->dataPacket($pk);
				if (!$this->loggedIn && $this->loginCompleted) {
					$this->loggedIn = true;
					$this->scheduleUpdate();
					$this->justCreated = false;
				}
				//Timings::$timerChunkRudiusPacket->stopTiming();
				break;
			case 'COMMAND_STEP_PACKET':
				$commandName = $packet->name;
				$commandOverload = $packet->overload;
				$commandParams = json_decode($packet->outputFormat, true);
				// trying to find command or her alias
				if (!isset(self::$availableCommands[$commandName])) {
					foreach(self::$availableCommands as $name => $data) {
						if (isset($data['versions'][0]['aliases'])) {
							if (in_array($commandName, $data['versions'][0]['aliases'])) {
								$commandName = $name;
								break;
							}
						}
					}
				}
				if (!isset(self::$availableCommands[$commandName])) {
					$this->sendMessage('Unknown command.');
					break;
				}

				$commandLine = $commandName;
				// facepalm : This needs for right params order
				$params = self::$availableCommands[$commandName]['versions'][0]['overloads'][$commandOverload]['input']['parameters'];
				foreach ($params as $param) {
					if (!isset($commandParams[$param['name']]) && (!isset($param['optional']) || $param['optional'] == false)) {
						$this->sendMessage('Bad arguments for ' . $commandName . ' command.');
						break(2);
					}
					if (isset($commandParams[$param['name']])) {
						$commandLine .= ' ' . $commandParams[$param['name']];
					}
				}
				$this->processCommand($commandLine);
				break;
			case 'RESOURCE_PACKS_CLIENT_RESPONSE_PACKET':
				switch ($packet->status) {
					case ResourcePackClientResponsePacket::STATUS_REFUSED:
						$pk = new ResourcePackStackPacket();
						$this->dataPacket($pk);
						break;
					case ResourcePackClientResponsePacket::STATUS_SEND_PACKS:
						$modsManager = $this->server->getModsManager();
						foreach ($packet->packIds as $packId) {
							$resourcePack = $modsManager->getResourcePackById($packId);
							if (is_null($resourcePack)) {
								continue;
							}
							$pk = new ResourcePackDataInfoPacket();
							$pk->modId = $resourcePack->id;
							$pk->fileSize = $resourcePack->size;
							$pk->modFileHash = $resourcePack->hash;
							$this->dataPacket($pk);
						}
						break;
					case ResourcePackClientResponsePacket::STATUS_HAVE_ALL_PACKS:
						$modsManager = $this->server->getModsManager();
						$pk = new ResourcePackStackPacket();
						$pk->isRequired = $modsManager->isModsRequired();
						$pk->addons = $modsManager->getAddons();
						$pk->resourcePacks = $modsManager->getResourcePacks();
						$this->dataPacket($pk);
						break;
					case ResourcePackClientResponsePacket::STATUS_COMPLETED:
						$this->completeLogin();
						break;
					default:
						return false;
				}
				break;
			case "RESOURCE_PACK_CHUNK_REQUEST_PACKET":
				$modsManager = $this->server->getModsManager();
				$resourcePack = $modsManager->getResourcePackById($packet->resourcePackId);
				if (is_null($resourcePack)) {
					$this->close("Request invalid resource pack", "Request invalid resource pack");
				} else {
					$pk = new ResourcePackChunkDataPacket();
					$pk->resourcePackId = $packet->resourcePackId;
					$pk->chunkIndex = $packet->requestChunkIndex;
					$pk->chunkData = $resourcePack->readChunk($pk->chunkIndex, ResourcePackDataInfoPacket::MAX_CHUNK_SIZE);
					$this->dataPacket($pk);
				}
				break;
			/** @minProtocol 120 */
			case 'INVENTORY_TRANSACTION_PACKET':
				switch ($packet->transactionType) {
					case InventoryTransactionPacket::TRANSACTION_TYPE_INVENTORY_MISMATCH:
						$this->sendAllInventories();
						break;
					case InventoryTransactionPacket::TRANSACTION_TYPE_NORMAL:
						$this->normalTransactionLogic($packet);
						break;
					case InventoryTransactionPacket::TRANSACTION_TYPE_ITEM_USE_ON_ENTITY:
						switch ($packet->actionType) {
							case InventoryTransactionPacket::ITEM_USE_ON_ENTITY_ACTION_ATTACK:
								$this->attackByTargetId($packet->entityId);
								break;
							case InventoryTransactionPacket::ITEM_USE_ON_ENTITY_ACTION_INTERACT:
								$target = $this->getLevel()->getEntity($packet->entityId);
								if ($target instanceof Vehicle) {
									$target->onPlayerInteract($this);
								} elseif (!is_null($target)) {
									$target->interact($this);
								}
								break;
						}
						break;
					case InventoryTransactionPacket::TRANSACTION_TYPE_ITEM_USE:
						switch ($packet->actionType) {
							case InventoryTransactionPacket::ITEM_USE_ACTION_PLACE:
								$blockHash = $packet->position['x'] . ':' . $packet->position['y'] . ':' . $packet->position['z']. ':' . $packet->face;
								if ($this->lastUpdate - $this->lastInteractTick < 3 && $this->lastInteractCoordsHash == $blockHash) {
									break;
								}
								$this->lastInteractTick = $this->lastUpdate;
								$this->lastInteractCoordsHash = $blockHash;
							case InventoryTransactionPacket::ITEM_USE_ACTION_USE:
								$this->useItem($packet->item, $packet->slot, $packet->face, $packet->position, $packet->clickPosition);
								break;
							case InventoryTransactionPacket::ITEM_USE_ACTION_DESTROY:
								$this->breakBlock($packet->position);
								break;
							default:
								error_log('[TRANSACTION_TYPE_ITEM_USE] Wrong actionType ' . $packet->actionType);
								break;
						}
						break;
					case InventoryTransactionPacket::TRANSACTION_TYPE_ITEM_RELEASE:
						switch ($packet->actionType) {
							case InventoryTransactionPacket::ITEM_RELEASE_ACTION_RELEASE:
								$this->releaseUseItem();
								$this->startAction = -1;
								break;
							case InventoryTransactionPacket::ITEM_RELEASE_ACTION_USE:
								$this->useItem120();
								$this->startAction = -1;
								break;
							default:
								error_log('[TRANSACTION_TYPE_ITEM_RELEASE] Wrong actionType ' . $packet->actionType);
								break;
						}
						break;
					default:
						error_log('Wrong transactionType ' . $packet->transactionType);
						break;
				}
				break;
			/** @minProtocol 120 */
			case 'COMMAND_REQUEST_PACKET':
				if ($packet->command[0] != '/') {
					$this->sendMessage('Invalid command data.');
					break;
				}
				$commandLine = substr($packet->command, 1);
				if ($this->getPlayerProtocol() >= Info::PROTOCOL_330) { //hack for 1.9+
					$this->commandsData[] = ['command' => $commandLine, 'delay' => 2];
				} else {
					$this->processCommand($commandLine);
				}
				break;
			/** @minProtocol 120 */
			case 'PLAYER_SKIN_PACKET':
				if ($this->setSkin($packet->newSkinByteData, $packet->newSkinId, $packet->newSkinGeometryName, $packet->newSkinGeometryData, $packet->newCapeByteData, $packet->isPremiumSkin)) {
					// Send new skin to viewers and to self
					$this->additionalSkinData = $packet->additionalSkinData;
					$this->updatePlayerSkin($packet->oldSkinName, $packet->newSkinName);
				}
				break;

			/** @minProtocol 120 */
			case 'MODAL_FORM_RESPONSE_PACKET':
				$this->checkModal($packet->formId, json_decode($packet->data, true));
				break;
			/** @minProtocol 120 */
			case 'PURCHASE_RECEIPT_PACKET':
				$event = new PlayerReceiptsReceivedEvent($this, $packet->receipts);
				$this->server->getPluginManager()->callEvent($event);
				break;
			case 'SERVER_SETTINGS_REQUEST_PACKET':
				$this->sendServerSettings();
				break;
			case 'CLIENT_TO_SERVER_HANDSHAKE_PACKET':
				$this->continueLoginProcess();
				break;
			case 'SUB_CLIENT_LOGIN_PACKET':
				$subPlayer = new static($this->interface, null, $this->ip, $this->port);
				if ($subPlayer->subAuth($packet, $this)) {
					$this->subClients[$packet->targetSubClientID] = $subPlayer;
				}
				//$this->kick("COOP play is not allowed");
				break;
			case 'DISCONNECT_PACKET':
				if ($this->subClientId > 0) {
					$this->close('', 'client disconnect');
				}
				break;
			case 'PLAYER_INPUT_PACKET':
				if (!is_null($this->currentVehicle)) {
					$this->currentVehicle->playerMoveVehicle($packet->forward, $packet->sideway);
				} else {
					$this->onPlayerInput($packet->forward, $packet->sideway, $packet->jump, $packet->sneak);
				}			
				break;
			case 'MAP_INFO_REQUEST_PACKET':
				$this->onPlayerRequestMap($packet->mapId);
				break;
			case "RESPAWN_PACKET":
				$pk = new RespawnPacket();
				$pos = $this->getSpawn();
				$pk->x = $pos->x;
				$pk->y = $pos->y +  $this->getEyeHeight();
				$pk->z = $pos->z;
				$this->dataPacket($pk);
				break;
			default:
				break;
		}
	}


	protected function respawn() {
		$this->craftingType = self::CRAFTING_DEFAULT;

		$this->server->getPluginManager()->callEvent($ev = new PlayerRespawnEvent($this, $this->getSpawn()));

		$this->teleport($ev->getRespawnPosition(), $ev->getPitch(), $ev->getYaw());

		$this->setSprinting(false, true);
		$this->setSneaking(false);

		$this->extinguish();
		$this->blocksAround = null;
		$this->dataProperties[self::DATA_AIR] = [self::DATA_TYPE_SHORT, 300];
		$this->setDataFlag(self::DATA_FLAGS, self::DATA_FLAG_NOT_IN_WATER, true, self::DATA_TYPE_LONG, false);
		$this->deadTicks = 0;
		$this->despawnFromAll();
		$this->dead = false;
		$this->isTeleporting = true;
		$this->noDamageTicks = 60;

		$this->setHealth($this->getMaxHealth());
		$this->setFood(20);

		$this->foodTick = 0;
		$this->exhaustion = 0;
		$this->saturation = 5;
		$this->lastSentVitals = 10;

		$this->removeAllEffects();
		$this->sendSelfData();

		$this->sendSettings();
		$this->inventory->sendContents($this);
		$this->inventory->sendArmorContents($this);

		$this->blocked = false;

		$this->scheduleUpdate();

		$this->server->getPluginManager()->callEvent(new PlayerRespawnAfterEvent($this));
	}

	/**
	 * Kicks a player from the server
	 *
	 * @param string $reason
	 *
	 * @return bool
	 */
	public function kick($reason = "Disconnected from server."){
		$this->server->getPluginManager()->callEvent($ev = new PlayerKickEvent($this, $reason, TextFormat::YELLOW . $this->username . " has left the game"));
		if(!$ev->isCancelled()){
			$this->close($ev->getQuitMessage(), $reason);
			return true;
		}

		return false;
	}

	/**
	 * Sends a direct chat message to a player
	 *
	 * @param string|TextContainer $message
	 */
	public function sendMessage($message){
		$mes = explode("\n", $message);
		foreach($mes as $m){
			if($m !== ""){
				$this->messageQueue[] = $m;
			}
		}
	}

	public function sendChatMessage($senderName, $message) {
		$pk = new TextPacket();
		$pk->type = TextPacket::TYPE_CHAT;
		$pk->message = $message;
		$pk->source = $senderName;
		$sender = $this->server->getPlayer($senderName);
		if ($sender !== null && $sender->getOriginalProtocol() >= ProtocolInfo::PROTOCOL_140) {
			$pk->xuid = $sender->getXUID();
		}
		$this->dataPacket($pk);
	}

	public function sendTranslation($message, array $parameters = []){
		$pk = new TextPacket();
		$pk->type = TextPacket::TYPE_RAW;
		$pk->message = $message;
		$this->dataPacket($pk);
	}

	public function sendPopup($message){
		$pk = new TextPacket();
		$pk->type = TextPacket::TYPE_POPUP;
		$pk->message = $message;
		$this->dataPacket($pk);
	}

	public function sendTip($message){
		$pk = new TextPacket();
		$pk->type = TextPacket::TYPE_TIP;
		$pk->message = $message;
		$this->dataPacket($pk);
	}

	/**
	 * @param string $message Message to be broadcasted
	 * @param string $reason  Reason showed in console
	 */
	public function close($message = "", $reason = "generic reason"){
		if ($this->isTransfered) {
			$reason = 'transfered';
		}
		if ($this->parent !== null) {
			$this->parent->removeSubClient($this->subClientId);
		} else {
			foreach ($this->subClients as $subClient) {
				$subClient->close($message, $reason);
			}
		}
        foreach($this->tasks as $task){
			$task->cancel();
		}
		$this->tasks = [];
		if($this->connected and !$this->closed){
			$pk = new DisconnectPacket;
			$pk->message = $reason;
			$this->directDataPacket($pk);
			$this->connected = false;
			if($this->username != ""){
				$this->server->getPluginManager()->callEvent($ev = new PlayerQuitEvent($this, $message, $reason));
				if($this->server->getSavePlayerData() and $this->loggedIn === true){
					$this->save();
				}
			}

			foreach($this->server->getOnlinePlayers() as $player){
				if(!$player->canSee($this)){
					$player->showPlayer($this);
				}
				$player->despawnFrom($this);
			}
			$this->hiddenPlayers = [];
			$this->hiddenEntity = [];

			if (!is_null($this->currentWindow)) {
				$this->removeWindow($this->currentWindow);
			}

			$this->interface->close($this, $reason);

			$chunkX = $chunkZ = null;
			foreach($this->usedChunks as $index => $d){
				Level::getXZ($index, $chunkX, $chunkZ);
				$this->level->freeChunk($chunkX, $chunkZ, $this);
				unset($this->usedChunks[$index]);
				foreach($this->level->getChunkEntities($chunkX, $chunkZ) as $entity){
					$entity->removeClosedViewer($this);
				}
			}

			parent::close();

			$this->server->removeOnlinePlayer($this);

			$this->loggedIn = false;

//			if(isset($ev) and $this->username != "" and $this->spawned !== false and $ev->getQuitMessage() != ""){
//				$this->server->broadcastMessage($ev->getQuitMessage());
//			}

			$this->server->getPluginManager()->unsubscribeFromPermission(Server::BROADCAST_CHANNEL_USERS, $this);
			$this->spawned = false;
			$this->server->getLogger()->info(TextFormat::AQUA . $this->username . TextFormat::WHITE . "/" . $this->ip . " logged out due to " . str_replace(["\n", "\r"], [" ", ""], $reason));
			$this->usedChunks = [];
			$this->loadQueue = [];
			$this->hasSpawned = [];
			$this->spawnPosition = null;
			$this->packetQueue = [];
			$this->entitiesPacketsQueue = [];
			$this->inventoryPacketQueue = [];
			$this->lastEntityRemove = [];
			$this->entitiesUUIDEids = [];
			$this->lastMoveBuffer = '';
			unset($this->buffer);
			if (!is_null($this->scoreboard)) {
				$this->scoreboard->removePlayer($this);
			}
		}
		$this->perm->clearPermissions();
		$this->server->removePlayer($this);
	}

	public function __debugInfo(){
		return [];
	}

	/**
	 * Handles player data saving
	 */
	public function save(){
		if($this->closed){
			throw new \InvalidStateException("Tried to save closed player");
		}

		parent::saveNBT();
		if($this->level instanceof Level){
			$this->namedtag->Level = new StringTag("Level", $this->level->getName());
			if($this->spawnPosition instanceof Position and $this->spawnPosition->getLevel() instanceof Level){
				$this->namedtag["SpawnLevel"] = $this->spawnPosition->getLevel()->getName();
				$this->namedtag["SpawnX"] = (int) $this->spawnPosition->x;
				$this->namedtag["SpawnY"] = (int) $this->spawnPosition->y;
				$this->namedtag["SpawnZ"] = (int) $this->spawnPosition->z;
			}

			$this->namedtag["playerGameType"] = $this->gamemode;
			$this->namedtag["lastPlayed"] = floor(microtime(true) * 1000);

			if($this->username != "" and $this->namedtag instanceof Compound){
				$this->server->saveOfflinePlayerData($this->username, $this->namedtag, true);
			}
		}
	}

	/**
	 * Gets the username
	 *
	 * @return string
	 */
	public function getName(){
		return $this->username;
	}

    public function getXBLName() {
        return $this->xblName;
    }

	public function freeChunks(){
		$x = $z = null;
		foreach ($this->usedChunks as $index => $chunk) {
			Level::getXZ($index, $x, $z);
			$this->level->freeChunk($x, $z, $this);
			unset($this->usedChunks[$index]);
			unset($this->loadQueue[$index]);
			foreach($this->level->getChunkEntities($x, $z) as $entity){
				if($entity !== $this){
					$entity->despawnFrom($this);
				}
			}
		}
	}

	public function kill(){
		if($this->dead === true or $this->spawned === false){
			return;
		}

		$message = $this->getName() . " died";

		$cause = $this->getLastDamageCause();
		$ev = null;
		if($cause instanceof EntityDamageEvent){
			$ev = $cause;
			$cause = $ev->getCause();
		}

		switch($cause){
			case EntityDamageEvent::CAUSE_ENTITY_ATTACK:
				if($ev instanceof EntityDamageByEntityEvent){
					$e = $ev->getDamager();
					if($e instanceof Player){
						$message = $this->getName() . " was killed by " . $e->getName();
						break;
					}elseif($e instanceof Living){
						$message = $this->getName() . " was slain by " . $e->getName();
						break;
					}
				}
				$message = $this->getName() . " was killed";
				break;
			case EntityDamageEvent::CAUSE_PROJECTILE:
				if($ev instanceof EntityDamageByEntityEvent){
					$e = $ev->getDamager();
					if($e instanceof Living){
						$message = $this->getName() . " was shot by " . $e->getName();
						break;
					}
				}
				$message = $this->getName() . " was shot by arrow";
				break;
			case EntityDamageEvent::CAUSE_SUICIDE:
				$message = $this->getName() . " died";
				break;
			case EntityDamageEvent::CAUSE_VOID:
				$message = $this->getName() . " fell out of the world";
				break;
			case EntityDamageEvent::CAUSE_FALL:
				if($ev instanceof EntityDamageEvent){
					if($ev->getFinalDamage() > 2){
						$message = $this->getName() . " fell from a high place";
						break;
					}
				}
				$message = $this->getName() . " hit the ground too hard";
				break;

			case EntityDamageEvent::CAUSE_SUFFOCATION:
				$message = $this->getName() . " suffocated in a wall";
				break;

			case EntityDamageEvent::CAUSE_LAVA:
				$message = $this->getName() . " tried to swim in lava";
				break;

			case EntityDamageEvent::CAUSE_FIRE:
				$message = $this->getName() . " went up in flames";
				break;

			case EntityDamageEvent::CAUSE_FIRE_TICK:
				$message = $this->getName() . " burned to death";
				break;

			case EntityDamageEvent::CAUSE_DROWNING:
				$message = $this->getName() . " drowned";
				break;

			case EntityDamageEvent::CAUSE_CONTACT:
				$message = $this->getName() . " was pricked to death";
				break;

			case EntityDamageEvent::CAUSE_BLOCK_EXPLOSION:
			case EntityDamageEvent::CAUSE_ENTITY_EXPLOSION:
				$message = $this->getName() . " blew up";
				break;

			case EntityDamageEvent::CAUSE_MAGIC:
				$message = $this->getName() . " was slain by magic";
				break;

			case EntityDamageEvent::CAUSE_CUSTOM:
				break;

			default:

		}

		if($this->dead){
			return;
		}

		Entity::kill();

		$this->server->getPluginManager()->callEvent($ev = new PlayerDeathEvent($this, $this->getDrops(), $message));

		$this->freeChunks();
		if (!is_null($this->currentVehicle)) {
			$this->currentVehicle->dissMount();
		}

		if(!$ev->getKeepInventory()){
			foreach($ev->getDrops() as $item){
				$this->level->dropItem($this, $item);
			}

			if($this->inventory !== null){
				$this->inventory->clearAll();
			}
		}

		if($ev->getDeathMessage() != ""){
			$this->server->broadcast($ev->getDeathMessage(), Server::BROADCAST_CHANNEL_USERS);
		}

		if($this->server->isHardcore()){
			$this->setBanned(true);
		}else{
			$pk = new RespawnPacket();
			$pos = $this->getSpawn();
			$pk->x = $pos->x;
			$pk->y = $pos->y +  $this->getEyeHeight();
			$pk->z = $pos->z;
			$this->dataPacket($pk);
		}
	}

	public function setHealth($amount){
		parent::setHealth($amount);
		if($this->spawned === true){
			$pk = new UpdateAttributesPacket();
			$pk->entityId = $this->id;
			$pk->minValue = 0;
			$pk->maxValue = $this->getMaxHealth();
			$pk->value = $this->getHealth();
			$pk->defaultValue = $pk->maxValue;
			$pk->name = UpdateAttributesPacket::HEALTH;
			$this->dataPacket($pk);
		}
	}

	public function setFoodEnabled($enabled) {
		$this->hungerEnabled = $enabled;
	}

	public function getFoodEnabled() {
		return $this->hungerEnabled;
	}

	public function setFood($amount){
		if($this->spawned){
			$pk = new UpdateAttributesPacket();
			$pk->entityId = $this->id;
			$pk->minValue = 0;
			$pk->maxValue = 20;
			$pk->value = $amount;
			$pk->defaultValue = $pk->maxValue;
			$pk->name = UpdateAttributesPacket::HUNGER;
			$this->dataPacket($pk);
		}

		$this->foodLevel = $amount;
	}

	public function getFood() {
		return $this->foodLevel;
	}

	public function subtractFood($amount){
		if (!$this->getFoodEnabled()) {
			return false;
		}

//		if($this->getFood()-$amount <= 6 && !($this->getFood() <= 6)) {
////			$this->setDataProperty(self::DATA_FLAG_SPRINTING, self::DATA_TYPE_BYTE, false);
//			$this->removeEffect(Effect::SLOWNESS);
//		} elseif($this->getFood()-$amount < 6 && !($this->getFood() > 6)) {
////			$this->setDataProperty(self::DATA_FLAG_SPRINTING, self::DATA_TYPE_BYTE, true);
//			$effect = Effect::getEffect(Effect::SLOWNESS);
//			$effect->setDuration(0x7fffffff);
//			$effect->setAmplifier(2);
//			$effect->setVisible(false);
//			$this->addEffect($effect);
//		}
		if($this->foodLevel - $amount < 0) return;
		$this->setFood($this->getFood() - $amount);
	}

    public function setAbsorption(float $absorption) {
        $pk = new UpdateAttributesPacket();
        $pk->entityId = $this->id;
        $pk->value = $absorption;
        $pk->name = UpdateAttributesPacket::ABSORPTION;
        $pk->maxValue = 20;
        $this->absorption = $absorption;
        $this->dataPacket($pk);
    }

    public function getAbsorption() {
        return $this->absorption;
    }

	public function attack($damage, EntityDamageEvent $source){
		if($this->dead === true){
			return;
		}

		if($this->isCreative()
			and $source->getCause() !== EntityDamageEvent::CAUSE_MAGIC
			and $source->getCause() !== EntityDamageEvent::CAUSE_SUICIDE
			and $source->getCause() !== EntityDamageEvent::CAUSE_VOID
		){
			$source->setCancelled();
		}

		parent::attack($damage, $source);

		if($source->isCancelled()){
			return;
		}elseif($this->getLastDamageCause() === $source and $this->spawned){
			$pk = new EntityEventPacket();
			$pk->eid = $this->id;
			$pk->event = EntityEventPacket::HURT_ANIMATION;
			$this->dataPacket($pk);
		}
	}

	public function sendPosition(Vector3 $pos, $yaw = null, $pitch = null, $mode = MovePlayerPacket::MODE_RESET, array $targets = null) {
		$yaw = $yaw === null ? $this->yaw : $yaw;
		$pitch = $pitch === null ? $this->pitch : $pitch;

		$pk = new MovePlayerPacket();
		$pk->eid = $this->getId();
		$pk->x = $pos->x;
		$pk->y = $pos->y + $this->getEyeHeight();
		$pk->z = $pos->z;
		$pk->bodyYaw = $yaw;
		$pk->pitch = $pitch;
		$pk->yaw = $yaw;
		$pk->mode = $mode;

		if($targets !== null) {
			Server::broadcastPacket($targets, $pk);
		} else {
			if ($this->joinCompleted) {
				$this->directDataPacket($pk);
			} else {
				$this->dataPacket($pk);
			}
		}
	}

	protected function checkChunks() {
		$chunkX = $this->x >> 4;
		$chunkZ = $this->z >> 4;
		if ($this->chunk === null || $this->chunk->getX() !== $chunkX || $this->chunk->getZ() !== $chunkZ) {
			if ($this->chunk !== null) {
				$this->chunk->removeEntity($this);
			}
			$this->chunk = $this->level->getChunk($chunkX, $chunkZ);
			if ($this->chunk !== null) {
				$this->chunk->addEntity($this);
			}
		}

		$chunkViewers = $this->level->getUsingChunk($this->x >> 4, $this->z >> 4);
		unset($chunkViewers[$this->getId()]);

		foreach ($this->hasSpawned as $player) {
			if (!isset($chunkViewers[$player->getId()])) {
				$this->despawnFrom($player);
			} else {
				unset($chunkViewers[$player->getId()]);
			}
		}

		foreach ($chunkViewers as $player) {
			$this->spawnTo($player);
		}
	}

	public function teleport(Vector3 $pos, $yaw = null, $pitch = null) {
		$this->activeModalWindows = [];
		if (!$this->spawned || !$this->isOnline()) {
			$this->beforeSpawnTeleportPosition = [$pos, $yaw, $pitch];
			if(($pos instanceof Position) && $pos->level !== $this->level){
				$this->switchLevel($pos->getLevel());
			}
			return;
		}
		if(parent::teleport($pos, $yaw, $pitch)){
			if (!is_null($this->currentWindow)) {
				$this->removeWindow($this->currentWindow);
			}
			$this->sendPosition($this, $this->pitch, $this->yaw, MovePlayerPacket::MODE_RESET);

			$this->resetFallDistance();
			$this->nextChunkOrderRun = 0;
			$this->newPosition = null;
			$this->isTeleporting = true;
			$this->isTeleportedForMoveEvent = true;
		}
	}


	/**
	 * @param Inventory $inventory
	 *
	 * @return int
	 */
	public function getWindowId(Inventory $inventory) {
		if ($inventory === $this->currentWindow) {
			return $this->currentWindowId;
		} else if ($inventory === $this->inventory) {
			return 0;
		}
		return -1;
	}

	public function getCurrentWindowId() {
		return $this->currentWindowId;
	}

	public function getCurrentWindow() {
		return $this->currentWindow;
	}

	/**
	 * Returns the created/existing window id
	 *
	 * @param Inventory $inventory
	 * @param int       $forceId
	 *
	 * @return int
	 */
	public function addWindow(Inventory $inventory, $forceId = null) {
		if ($this->currentWindow === $inventory) {
			return $this->currentWindowId;
		}
		if (!is_null($this->currentWindow)) {
			echo '[INFO] Trying to open window when previous inventory still open'.PHP_EOL;
			$this->removeWindow($this->currentWindow);
		}
		$this->currentWindow = $inventory;
		$this->currentWindowId = !is_null($forceId) ? $forceId : rand(self::MIN_WINDOW_ID, 98);
		if (!$inventory->open($this)) {
			$this->removeWindow($inventory);
		}
		return $this->currentWindowId;
	}

	public function removeWindow(Inventory $inventory) {
		if ($this->currentWindow !== $inventory) {
			echo '[INFO] Trying to close not open window'.PHP_EOL;
		} else {
			$inventory->close($this);
			$this->currentWindow = null;
			$this->currentWindowId = -1;
		}
	}

	public function setMetadata($metadataKey, MetadataValue $metadataValue){
		$this->server->getPlayerMetadata()->setMetadata($this, $metadataKey, $metadataValue);
	}

	public function getMetadata($metadataKey){
		return $this->server->getPlayerMetadata()->getMetadata($this, $metadataKey);
	}

	public function hasMetadata($metadataKey){
		return $this->server->getPlayerMetadata()->hasMetadata($this, $metadataKey);
	}

	public function removeMetadata($metadataKey, Plugin $plugin){
		$this->server->getPlayerMetadata()->removeMetadata($this, $metadataKey, $plugin);
	}

    public function handlePlaySound(network\protocol\v120\PlaySoundPacket $packet) : bool{
        return false;
    }

    public function handleStopSound(StopSoundPacket $packet) : bool{
        return false;
    }

	public function setLastMessageFrom($name) {
		$this->lastMessageReceivedFrom = (string)$name;
	}

	public function getLastMessageFrom() {
		return $this->lastMessageReceivedFrom;
	}

	public function setIdentifier($identifier){
		$this->identifier = $identifier;
	}

	public function getIdentifier(){
		return $this->identifier;
	}

	public function getVisibleEyeHeight() {
		return $this->eyeHeight;
	}

	public function kickOnFullServer() {
		return true;
	}

	public function processLogin() {
		if ($this->server->isUseEncrypt() && $this->needEncrypt()) {
			$privateKey = $this->server->getServerPrivateKey();
			$token = $this->server->getServerToken();
			$pk = new ServerToClientHandshakePacket();
			$pk->publicKey = $this->server->getServerPublicKey();
			$pk->serverToken = $token;
			$pk->privateKey = $privateKey;
			$this->directDataPacket($pk);
			$this->interface->enableEncryptForPlayer($this, $token, $privateKey, $this->identityPublicKey);
		} else {
			$this->continueLoginProcess();
		}

	}

	public function continueLoginProcess() {
		$pk = new PlayStatusPacket();
		$pk->status = PlayStatusPacket::LOGIN_SUCCESS;
		$this->dataPacket($pk);

		$modsManager = $this->server->getModsManager();
		$pk = new ResourcePacksInfoPacket();
		$pk->isRequired = $modsManager->isModsRequired();
		$pk->addons = $modsManager->getAddons();
		$pk->resourcePacks = $modsManager->getResourcePacks();
		$this->dataPacket($pk);
	}

	public function completeLogin() {
		if ($this->loginCompleted) {
			return;
		}
		$this->loginCompleted = true;
		$valid = true;
		$len = strlen($this->username);
		if ($len > 16 or $len < 3) {
			$valid = false;
		}
		for ($i = 0; $i < $len and $valid; ++$i) {
			$c = ord($this->username{$i});
			if (($c >= ord("a") and $c <= ord("z")) or ( $c >= ord("A") and $c <= ord("Z")) or ( $c >= ord("0") and $c <= ord("9")) or $c === ord("_") or $c === ord(" ")
			) {
				continue;
			}
			$valid = false;
			break;
		}
		if (!$valid or $this->iusername === "rcon" or $this->iusername === "console") {
			$this->close("", "Please choose a valid username.");
			return;
		}

		static $allowedSkinSize = [
			8192, // argb 64x32
			16384, // argb 64x64
			32768, // argb 128x64
			65536, // argb 128x128
		];

		if (!in_array(strlen($this->skin), $allowedSkinSize)) {
			$this->close("", "Invalid skin.", false);
			return;
		}

		if (count($this->server->getOnlinePlayers()) >= $this->server->getMaxPlayers() && $this->kickOnFullServer()) {
			$this->close("", "Server is Full", false);
			return;
		}

		if (!$this->server->isWhitelisted(strtolower($this->getName()))) {
			$this->close(TextFormat::YELLOW . $this->username . " has left the game", "Server is private.");
			return;
		} elseif ($this->server->getNameBans()->isBanned(strtolower($this->getName())) or $this->server->getIPBans()->isBanned($this->getAddress())) {
			$this->close(TextFormat::YELLOW . $this->username . " has left the game", "You have been banned.");
			return;
		}

		if ($this->hasPermission(Server::BROADCAST_CHANNEL_USERS)) {
			$this->server->getPluginManager()->subscribeToPermission(Server::BROADCAST_CHANNEL_USERS, $this);
		}
		if ($this->hasPermission(Server::BROADCAST_CHANNEL_ADMINISTRATIVE)) {
			$this->server->getPluginManager()->subscribeToPermission(Server::BROADCAST_CHANNEL_ADMINISTRATIVE, $this);
		}

		foreach ($this->server->getOnlinePlayers() as $p) {
			if ($p !== $this and strtolower($p->getName()) === strtolower($this->getName())) {
				if ($this->xuid !== '') {
					$p->close(TextFormat::YELLOW . $p->getName() . " has left the game", "You connected from somewhere else.");
				} else if ($p->kick("You connected from somewhere else.") === false) {
					$this->close(TextFormat::YELLOW . $this->getName() . " has left the game", "You connected from somewhere else.");
					return;
				}
			}
		}


		$this->server->getPluginManager()->callEvent($ev = new PlayerPreLoginEvent($this, "Plugin reason"));
		if ($ev->isCancelled()) {
			$this->close("", $ev->getKickMessage());
			return;
		}

		$nbt = $this->server->getOfflinePlayerData($this->username);
		if (!isset($nbt->NameTag)) {
			$nbt->NameTag = new StringTag("NameTag", $this->username);
		} else {
			$nbt["NameTag"] = $this->username;
		}
		$this->gamemode = $nbt["playerGameType"] & 0x03;
		if ($this->server->getForceGamemode()) {
			$this->gamemode = $this->server->getGamemode();
			$nbt->playerGameType = new IntTag("playerGameType", $this->gamemode);
		}

		$this->allowFlight = $this->isCreative();


		if (($level = $this->server->getLevelByName($nbt["Level"])) === null) {
			$this->setLevel($this->server->getDefaultLevel(), true);
			$nbt["Level"] = $this->level->getName();
			$nbt["Pos"][0] = $this->level->getSpawnLocation()->x;
			$nbt["Pos"][1] = $this->level->getSpawnLocation()->y + 5;
			$nbt["Pos"][2] = $this->level->getSpawnLocation()->z;
		} else {
			$this->setLevel($level, true);
		}

		if (!($nbt instanceof Compound)) {
			$this->close(TextFormat::YELLOW . $this->username . " has left the game", "Corrupt joining data, check your connection.");
			return;
		}

		$this->achievements = [];

		/** @var Byte $achievement */
		foreach ($nbt->Achievements as $achievement) {
			$this->achievements[$achievement->getName()] = $achievement->getValue() > 0 ? true : false;
		}

		$nbt->lastPlayed = new LongTag("lastPlayed", floor(microtime(true) * 1000));
		parent::__construct($this->level->getChunk($nbt["Pos"][0] >> 4, $nbt["Pos"][2] >> 4, true), $nbt);
//		$this->loggedIn = true;
		$this->server->addOnlinePlayer($this);

		if ($this->isCreative()) {
			$this->inventory->setHeldItemSlot(0);
		} else {
			$this->inventory->setHeldItemSlot($this->inventory->getHotbarSlotIndex(0));
		}

		if ($this->spawnPosition === null and isset($this->namedtag->SpawnLevel) and ( $level = $this->server->getLevelByName($this->namedtag["SpawnLevel"])) instanceof Level) {
			$this->spawnPosition = new Position($this->namedtag["SpawnX"], $this->namedtag["SpawnY"], $this->namedtag["SpawnZ"], $level);
		}

		$this->server->getPluginManager()->callEvent($ev = new PlayerLoginEvent($this, "Plugin reason"));
		if ($ev->isCancelled()) {
			$this->close(TextFormat::YELLOW . $this->username . " has left the game", $ev->getKickMessage());
			return;
		}
		$spawnPosition = $this->getSpawn();
		$this->server->getPluginManager()->callEvent($ev = new PlayerRespawnEvent($this, $spawnPosition));
		$this->setPosition($ev->getRespawnPosition());

		$pk = new StartGamePacket();
		$pk->seed = -1;
		$pk->dimension = 0;
		$pk->x = $this->x;
		$pk->y = $this->y + $this->getEyeHeight();
		$pk->z = $this->z;
		$pk->spawnX = (int) $spawnPosition->x;
		$pk->spawnY = (int) ($spawnPosition->y + $this->getEyeHeight());
		$pk->spawnZ = (int) $spawnPosition->z;
		$pk->generator = 1; //0 old, 1 infinite, 2 flat
		$pk->gamemode = $this->gamemode == 3 ? 1 : $this->gamemode;
		$pk->eid = $this->id;
		$pk->stringClientVersion = $this->clientVersion;
		$pk->multiplayerCorrelationId = $this->uuid->toString();
		$this->directDataPacket($pk);	
		if ($this->protocol >= ProtocolInfo::PROTOCOL_331) {
			$this->directDataPacket(new AvailableEntityIdentifiersPacket());
			$this->directDataPacket(new BiomeDefinitionListPacket());
		}

		$pk = new SetTimePacket();
		$pk->time = $this->level->getTime();
		$pk->started = true;
		$this->dataPacket($pk);

		if ($this->getHealth() <= 0) {
			$this->dead = true;
		}

		if (!empty(self::$availableCommands)) {
			$pk = new AvailableCommandsPacket();
			$this->dataPacket($pk);
		}
		if($this->getHealth() <= 0){
			$this->dead = true;
		}

		$this->server->getLogger()->info(TextFormat::AQUA . $this->username . TextFormat::WHITE . "/" . TextFormat::AQUA . $this->ip . " connected");

		$slots = [];
		foreach(Item::getCreativeItems() as $item){
			$slots[] = clone $item;
		}
		$pk = new InventoryContentPacket();
		$pk->inventoryID = Protocol120::CONTAINER_ID_CREATIVE;
		$pk->items = $slots;
		$this->dataPacket($pk);

		$this->server->sendRecipeList($this);

		$this->sendSelfData();
		$this->updateSpeed($this->movementSpeed);
		$this->sendFullPlayerList();
//		$this->updateExperience(0, 100);
//		$this->getInventory()->addItem(Item::get(Item::ENCHANTMENT_TABLE), Item::get(Item::DYE, 4, 64), Item::get(Item::IRON_AXE), Item::get(Item::IRON_SWORD));
	}


	public function getInterface() {
		return $this->interface;
	}

	public function transfer($address, $port = false) {
		$pk = new TransferPacket();
		$pk->ip = $address;
		$pk->port = ($port === false ? 19132 : $port);
		$this->dataPacket($pk);
		$this->isTransfered = true;
	}

	public function sendSelfData() {
		$pk = new SetEntityDataPacket();
		$pk->eid = $this->id;
		$pk->metadata = $this->dataProperties;
		$this->dataPacket($pk);
	}
	/**
	 * Create new transaction pair for transaction or add it to suitable one
	 *
	 * @param BaseTransaction $transaction
	 * @return null
	 */
	protected function addTransaction($transaction) {
		$newItem = $transaction->getTargetItem();
		$oldItem = $transaction->getSourceItem();
		// if decreasing transaction drop down
		if ($newItem->getId() === Item::AIR || ($oldItem->deepEquals($newItem) && $oldItem->count > $newItem->count)) {

			return;
		}
		// if increasing create pair manualy

		// trying to find inventory
		$inventory = $this->currentWindow;
		if (is_null($this->currentWindow) || $this->currentWindow === $transaction->getInventory()) {
			$inventory = $this->inventory;
		}
		// get item difference
		if ($oldItem->deepEquals($newItem)) {
			$newItem->count -= $oldItem->count;
		}

		$items = $inventory->getContents();
		$targetSlot = -1;
		foreach ($items as $slot => $item) {
			if ($item->deepEquals($newItem) && $newItem->count <= $item->count) {
				$targetSlot = $slot;
				break;
			}
		}
		if ($targetSlot !== -1) {
			$trGroup = new SimpleTransactionGroup($this);
			$trGroup->addTransaction($transaction);
			// create pair for the first transaction
			if (!$oldItem->deepEquals($newItem) && $oldItem->getId() !== Item::AIR && $inventory === $transaction->getInventory()) { // for swap
				$targetItem = clone $oldItem;
			} else if ($newItem->count === $items[$targetSlot]->count) {
				$targetItem = Item::get(Item::AIR);
			} else {
				$targetItem = clone $items[$targetSlot];
				$targetItem->count -= $newItem->count;
			}
			$pairTransaction = new BaseTransaction($inventory, $targetSlot, $items[$targetSlot], $targetItem);
			$trGroup->addTransaction($pairTransaction);

			try {
				$isExecute = $trGroup->execute();
				if (!$isExecute) {
//					echo '[INFO] Transaction execute fail 1.'.PHP_EOL;
					$trGroup->sendInventories();
				}
			} catch (\Exception $ex) {
//				echo '[INFO] Transaction execute fail 2.'.PHP_EOL;
				$trGroup->sendInventories();
			}
		} else {
//			echo '[INFO] Suiteble item not found in the current inventory.'.PHP_EOL;
			$transaction->getInventory()->sendContents($this);
		}
	}

	protected function enchantTransaction(BaseTransaction $transaction) {
		if ($this->craftingType !== self::CRAFTING_ENCHANT) {
			$this->getInventory()->sendContents($this);
			return;
		}
		$oldItem = $transaction->getSourceItem();
		$newItem = $transaction->getTargetItem();
		$enchantInv = $this->currentWindow;

		if (($newItem instanceof Armor || $newItem instanceof Tool) && $transaction->getInventory() === $this->inventory) {
			// get enchanting data
			$source = $enchantInv->getItem(0);
			$enchantingLevel = $enchantInv->getEnchantingLevel();

			if ($enchantInv->isItemWasEnchant() && $newItem->deepEquals($source, true, false)) {
				// reset enchanting data
				$enchantInv->setItem(0, Item::get(Item::AIR));
				$enchantInv->setEnchantingLevel(0);

				$playerItems = $this->inventory->getContents();
				$dyeSlot = -1;
				$targetItemSlot = -1;
				foreach ($playerItems as $slot => $item) {
					if ($item->getId() === Item::DYE && $item->getDamage() === 4 && $item->getCount() >= $enchantingLevel) {
						$dyeSlot = $slot;
					} else if ($item->deepEquals($source)) {
						$targetItemSlot = $slot;
					}
				}
				if ($dyeSlot !== -1 && $targetItemSlot !== -1) {
					$this->inventory->setItem($targetItemSlot, $newItem);
					if ($playerItems[$dyeSlot]->getCount() > $enchantingLevel) {
						$playerItems[$dyeSlot]->count -= $enchantingLevel;
						$this->inventory->setItem($dyeSlot, $playerItems[$dyeSlot]);
					} else {
						$this->inventory->setItem($dyeSlot, Item::get(Item::AIR));
					}
				}
			} else if (!$enchantInv->isItemWasEnchant()) {
				$enchantInv->setItem(0, Item::get(Item::AIR));
			}
			$enchantInv->sendContents($this);
			$this->inventory->sendContents($this);
			return;
		}

		if (($oldItem instanceof Armor || $oldItem instanceof Tool) && $transaction->getInventory() === $this->inventory) {
			$enchantInv->setItem(0, $oldItem);
		}
	}

	protected function updateAttribute($name, $value, $minValue, $maxValue, $defaultValue) {
		$pk = new UpdateAttributesPacket();
		$pk->entityId = $this->id;
		$pk->name = $name;
		$pk->value = $value;
		$pk->minValue = $minValue;
		$pk->maxValue = $maxValue;
		$pk->defaultValue = $defaultValue;
		$this->dataPacket($pk);
	}

	public function updateSpeed($value) {
		$this->movementSpeed = $value;
		$this->updateAttribute(UpdateAttributesPacket::SPEED, $this->movementSpeed, 0, self::MAXIMUM_SPEED, $this->movementSpeed);
	}

	public function setSprinting($value = true, $setDefault = false) {
		if(!$setDefault) {
			if ($this->isSprinting() == $value) {
				return;
			}
			$ev = new PlayerToggleSprintEvent($this, $value);
			$this->server->getPluginManager()->callEvent($ev);
			if($ev->isCancelled()){
				$this->sendData($this);
				return;
			}
		}
		parent::setSprinting($value);
		if ($setDefault) {
			$this->movementSpeed = self::DEFAULT_SPEED;
		} else {
			$sprintSpeedChange = self::DEFAULT_SPEED * 0.3;
			if ($value === false) {
				$sprintSpeedChange *= -1;
			}
			$this->movementSpeed += $sprintSpeedChange;
		}
		$this->updateSpeed($this->movementSpeed);
	}

	public function getProtectionEnchantments() {
		$result = [
			Enchantment::TYPE_ARMOR_PROTECTION => null,
			Enchantment::TYPE_ARMOR_FIRE_PROTECTION => null,
			Enchantment::TYPE_ARMOR_EXPLOSION_PROTECTION => null,
			Enchantment::TYPE_ARMOR_FALL_PROTECTION => null,
			Enchantment::TYPE_ARMOR_PROJECTILE_PROTECTION => null
		];
		$armor = $this->getInventory()->getArmorContents();
		$armorProtection = 0;
		foreach ($armor as $item) {
			if ($item->getId() === Item::AIR) {
				continue;
			}
			$enchantments = $item->getEnchantments();
			foreach ($result as $id => $enchantment) {
				if (isset($enchantments[$id])) {
					if ($id == Enchantment::TYPE_ARMOR_PROTECTION) {
						$armorProtection += 0.05 * $enchantments[$id]->getLevel();
					} elseif ((is_null($enchantment) || $enchantments[$id]->getLevel() > $enchantment->getLevel())) {
						$result[$id] = $enchantments[$id];
					}
				}
			}
		}
		if ($armorProtection > 0) {
			$result[Enchantment::TYPE_ARMOR_PROTECTION] = $armorProtection;
		}
		return $result;
	}


	public function getExperience()
	{
		return $this->exp;
	}

	public function getExperienceLevel()
	{
		return $this->expLevel;
	}

	public function updateExperience($exp = 0, $level = 0, $checkNextLevel = true)
	{
		$this->exp = $exp;
		$this->expLevel = $level;

		if($this->hasEnoughExperience() && $checkNextLevel){
			$exp = $this->getExperience() - $this->getExperienceNeeded();
			$level = $this->getExperienceLevel() + 1;
			$this->updateExperience($exp, $level, false);
		}

		$this->updateAttribute(UpdateAttributesPacket::EXPERIENCE, $this->getExperience() / $this->getExperienceNeeded(), 0, self::MAX_EXPERIENCE, 0);
		$this->updateAttribute(UpdateAttributesPacket::EXPERIENCE_LEVEL, $level, 0, self::MAX_EXPERIENCE_LEVEL, 0);
	}

	public function addExperience($exp = 0, $level = 0, $checkNextLevel = true)
	{
		$this->updateExperience($this->getExperience() + $exp, $this->getExperienceLevel() + $level, $checkNextLevel);
	}

	public function removeExperience($exp = 0, $level = 0, $checkNextLevel = true)
	{
		$this->updateExperience($this->getExperience() - $exp, $this->getExperienceLevel() - $level, $checkNextLevel);
	}

	// http://minecraft.gamepedia.com/Experience
	public function getExperienceNeeded()
	{
		$level = $this->getExperienceLevel();
		if ($level <= 16) {
			return (2 * $level) + 7;
		} elseif ($level <= 31) {
			return (5 * $level) - 38;
		} elseif ($level <= 21863) {
			return (9 * $level) - 158;
		}
		return PHP_INT_MAX;
	}

	public function hasEnoughExperience() {
		return $this->getExperience() >= $this->getExperienceNeeded();
	}

	public function isUseElytra() {
		return ($this->isHaveElytra() && $this->elytraIsActivated);
	}

	public function isHaveElytra() {
		if ($this->getInventory()->getArmorItem(Elytra::SLOT_NUMBER) instanceof Elytra) {
			return true;
		}
		return false;
	}

	public function setElytraActivated($value) {
		$this->elytraIsActivated = $value;
	}

	public function isElytraActivated() {
		return $this->elytraIsActivated;
	}

	public function getPlayerProtocol() {
		return $this->protocol;
	}

	public function getDeviceOS() {
        return $this->deviceType;
    }

    public function getInventoryType() {
        return $this->inventoryType;
    }

	public function setPing($ping) {
		$this->ping = $ping;
	}

	public function getPing() {
		return $this->ping;
	}

	public function sendPing() {
		if ($this->ping <= 150) {
			$this->sendMessage(TextFormat::GREEN . "Connection: Good ({$this->ping}ms)");
		} elseif ($this->ping <= 250) {
			$this->sendMessage(TextFormat::YELLOW . "Connection: Okay ({$this->ping}ms)");
		} else {
			$this->sendMessage(TextFormat::RED . "Connection: Bad ({$this->ping}ms)");
		}
	}

    public function getXUID() {
        return $this->xuid;
    }

	public function setTitle($text, $subtext = '', $time = 36000) {
		if ($this->protocol >= Info::PROTOCOL_290) { //hack for 1.7.x
			$this->clearTitle();
			$this->titleData = ['text' => !empty($text) ? $text : ' ', 'subtext' => $subtext, 'time' => $time, 'holdTickCount' => 5];
		} else {
			$this->sendTitle($text, $subtext, $time);
		}

	}

	protected function sendTitle($text, $subtext = '', $time = 36000) {
		$pk = new SetTitlePacket();
		$pk->type = SetTitlePacket::TITLE_TYPE_TIMES;
		$pk->text = "";
		$pk->fadeInTime = 5;
		$pk->fadeOutTime = 5;
		$pk->stayTime = 20 * $time;
		$this->dataPacket($pk);

		if (!empty($subtext)) {
			$pk = new SetTitlePacket();
			$pk->type = SetTitlePacket::TITLE_TYPE_SUBTITLE;
			$pk->text = $subtext;
			$this->dataPacket($pk);
		}

		$pk = new SetTitlePacket();
		$pk->type = SetTitlePacket::TITLE_TYPE_TITLE;
		$pk->text = $text;
		$this->dataPacket($pk);
	}

	public function clearTitle() {
		if ($this->getPlayerProtocol() >= Info::PROTOCOL_340) {
			$this->titleData = [];
			$this->sendTitle(" ", "", 0);
		} else {
			$pk = new SetTitlePacket();
			$pk->type = SetTitlePacket::TITLE_TYPE_TIMES;
			$pk->text = "";
			$pk->fadeInTime = 0;
			$pk->fadeOutTime = 0;
			$pk->stayTime = 0;
			$this->dataPacket($pk);

			$pk = new SetTitlePacket();
			$pk->type = SetTitlePacket::TITLE_TYPE_CLEAR;
			$pk->text = "";
			$this->dataPacket($pk);
		}
	}

	public function setActionBar($text, $time = 36000){
		$pk = new SetTitlePacket();
		$pk->type = SetTitlePacket::TITLE_TYPE_ACTION_BAR;
		$pk->text = $text;
		$pk->stayTime = $time;
		$pk->fadeInTime = 1;
		$pk->fadeOutTime = 1;
		$this->dataPacket($pk);
	}

	public function sendNoteSound($noteId, $queue = false) {
		if ($queue) {
			$this->noteSoundQueue[] = $noteId;
			return;
		}
		$pk = new LevelSoundEventPacket();
		$pk->eventId = LevelSoundEventPacket::SOUND_NOTE;
		$pk->x = $this->x;
		$pk->y = $this->y;
		$pk->z = $this->z;
		if ($this->getPlayerProtocol() >= Info::PROTOCOL_311) {
			// for 1.9.x gap between instruments 256 (1-256 - piano, 257-512 - another one, etc)
			$pk->customData = $noteId;
			$pk->entityType = MultiversionEntity::ID_NONE;
		} else {
			$pk->entityType = $noteId;
		}
		$this->directDataPacket($pk);
	}

	public function canSeeEntity(Entity $entity){
		return !isset($this->hiddenEntity[$entity->getId()]);
	}

	public function hideEntity(Entity $entity){
		if($entity instanceof Player){
			return;
		}
		$this->hiddenEntity[$entity->getId()] = $entity;
		$entity->despawnFrom($this);
	}

	public function showEntity(Entity $entity){
		if($entity instanceof Player){
			return;
		}
		unset($this->hiddenEntity[$entity->getId()]);
		if($entity !== $this && !$entity->closed && !$entity->dead){
			$entity->spawnTo($this);
		}
	}

	public function setOnFire($seconds, $damage = 1){
 		if($this->isSpectator()) {
 			return;
 		}
 		parent::setOnFire($seconds, $damage);
 	}

	public function attackInCreative($player) {

	}


	public function attackByTargetId($targetId) {
		if ($this->spawned === false || $this->dead === true || $this->blocked) {
			return;
		}

		$target = $this->level->getEntity($targetId);
		if ($target instanceof Player && ($this->server->getConfigBoolean("pvp", true) === false || ($target->getGamemode() & 0x01) > 0 || !$this->canAttackPlayers())) {
			$target->attackInCreative($this);
			return;
		}

		if (!($target instanceof Entity) || $this->isSpectator() || $target->dead === true || !$this->canAttackMobs()) {
			return;
		}

		if ($target instanceof DroppedItem || $target instanceof Arrow) {
			return;
		}

		$item = $this->inventory->getItemInHand();
		$damageTable = [
			Item::WOODEN_SWORD => 4,
			Item::GOLD_SWORD => 4,
			Item::STONE_SWORD => 5,
			Item::IRON_SWORD => 6,
			Item::DIAMOND_SWORD => 7,
			Item::WOODEN_AXE => 3,
			Item::GOLD_AXE => 3,
			Item::STONE_AXE => 3,
			Item::IRON_AXE => 5,
			Item::DIAMOND_AXE => 6,
			Item::WOODEN_PICKAXE => 2,
			Item::GOLD_PICKAXE => 2,
			Item::STONE_PICKAXE => 3,
			Item::IRON_PICKAXE => 4,
			Item::DIAMOND_PICKAXE => 5,
			Item::WOODEN_SHOVEL => 1,
			Item::GOLD_SHOVEL => 1,
			Item::STONE_SHOVEL => 2,
			Item::IRON_SHOVEL => 3,
			Item::DIAMOND_SHOVEL => 4,
		];

		$damage = [
			EntityDamageEvent::MODIFIER_BASE => isset($damageTable[$item->getId()]) ? $damageTable[$item->getId()] : 1,
		];

		if ($this->add(0, $this->getEyeHeight())->distanceSquared($target) > 34.81) { //5.9 ** 2
			return;
		} elseif ($target instanceof Player) {
			$armorValues = [
				Item::LEATHER_CAP => 1,
				Item::LEATHER_TUNIC => 3,
				Item::LEATHER_PANTS => 2,
				Item::LEATHER_BOOTS => 1,
				Item::CHAIN_HELMET => 1,
				Item::CHAIN_CHESTPLATE => 5,
				Item::CHAIN_LEGGINGS => 4,
				Item::CHAIN_BOOTS => 1,
				Item::GOLD_HELMET => 1,
				Item::GOLD_CHESTPLATE => 5,
				Item::GOLD_LEGGINGS => 3,
				Item::GOLD_BOOTS => 1,
				Item::IRON_HELMET => 2,
				Item::IRON_CHESTPLATE => 6,
				Item::IRON_LEGGINGS => 5,
				Item::IRON_BOOTS => 2,
				Item::DIAMOND_HELMET => 3,
				Item::DIAMOND_CHESTPLATE => 8,
				Item::DIAMOND_LEGGINGS => 6,
				Item::DIAMOND_BOOTS => 3,
			];
			$points = 0;
			foreach ($target->getInventory()->getArmorContents() as $index => $i) {
				if (isset($armorValues[$i->getId()])) {
					$points += $armorValues[$i->getId()];
				}
			}

			$damage[EntityDamageEvent::MODIFIER_ARMOR] = -floor($damage[EntityDamageEvent::MODIFIER_BASE] * $points * 0.04);
		}

		$timeDiff = microtime(true) - $this->lastDamegeTime;
		$this->lastDamegeTime = microtime(true);
		foreach (self::$damegeTimeList as $time => $koef) {
			if ($timeDiff <= $time) {
				if ($koef == 0) {
					return;
				}
				$damage[EntityDamageEvent::MODIFIER_BASE] *= $koef;
				break;
			}
		}

        if($this->fallDistance > 0 && !$this->isOnGround() && !$this->isInsideOfWater() && !$this->hasEffect(Effect::BLINDNESS)){
            $damage[EntityDamageEvent::MODIFIER_CRITICAL] = $damage[EntityDamageEvent::MODIFIER_BASE] / 2;
        }

		$ev = new EntityDamageByEntityEvent($this, $target, EntityDamageEvent::CAUSE_ENTITY_ATTACK, $damage);
		$target->attack($ev->getFinalDamage(), $ev);

		if ($ev->isCancelled()) {
			if ($item->isTool() && $this->isSurvival()) {
				$this->inventory->sendContents($this);
			}
			return;
		}

		if($target instanceof Player){
            $damage = 0;
            foreach($target->getInventory()->getArmorContents() as $key => $item){
                if($item instanceof Armor && ($thornsLevel = $item->getEnchantment(Enchantment::getEnchantment(Enchantment::TYPE_ARMOR_THORNS))) > 0){
                    if(mt_rand(1, 100) < $thornsLevel * 15){
                        $item->setDamage($item->getDamage() + 3);
                        $damage += ($thornsLevel > 10 ? $thornsLevel - 10 : random_int(0, 4));
                    }else{
                        $item->setDamage($item->getDamage() + 1);
                    }

                    if($item->getDamage() >= $item->getMaxDurability()) {
                        $target->getInventory()->setArmorItem($key, Item::get(Item::AIR));
                    }


                    $this->getInventory()->setArmorItem($key, $item);
                }
            }

            if($damage > 0){
                $target->attack($damage, new EntityDamageByEntityEvent($target, $this, EntityDamageEvent::CAUSE_MAGIC, $damage));
            }
        }

        if ($item->isTool() && $this->isSurvival()) {
            if ($item->useOn($target) && $item->getDamage() >= $item->getMaxDurability()) {
                $this->inventory->setItemInHand(Item::get(Item::AIR));
            } elseif ($this->inventory->getItemInHand()->getId() === $item->getId()) {
                $this->inventory->setItemInHand($item);
            }
        }
	}

	protected function useItem($item, $slot, $face, $blockPosition, $clickPosition) {
		switch ($face) {
			//Use Block, place
			case 0:
			case 1:
			case 2:
			case 3:
			case 4:
			case 5:
				$blockVector = new Vector3($blockPosition['x'], $blockPosition['y'], $blockPosition['z']);
				$this->setDataFlag(self::DATA_FLAGS, self::DATA_FLAG_ACTION, false);

				$itemInHand = $this->inventory->getItemInHand();
				if ($blockVector->distance($this) > 10 || $this->isSpectator()) {

				} else if ($this->isCreative() && !$this->isSpectator()) {
					if ($this->level->useItemOn($blockVector, $itemInHand, $face, $clickPosition['x'], $clickPosition['y'], $clickPosition['z'], $this) === true) {
						return;
					}
				} else if (!$itemInHand->deepEquals($item)) {
	//						$this->inventory->sendHeldItem($this);
				} else {
					$oldItem = clone $itemInHand;
					//TODO: Implement adventure mode checks
					if ($this->level->useItemOn($blockVector, $itemInHand, $face, $clickPosition['x'], $clickPosition['y'], $clickPosition['z'], $this)) {
						if (!$itemInHand->deepEquals($oldItem) || $itemInHand->getCount() !== $oldItem->getCount()) {
							$this->inventory->setItemInHand($itemInHand, $this);
							$this->inventory->sendHeldItem($this->hasSpawned);
						}
						return;
					}
				}

				$this->inventory->sendHeldItem($this);

				if($blockVector->distanceSquared($this) > 10000){
					return;
				}
				$target = $this->level->getBlock($blockVector);
				$block = $target->getSide($face);

				$this->level->sendBlocks([$this], [$target, $block], UpdateBlockPacket::FLAG_ALL_PRIORITY);
				return;

			case 0xff:
			case -1:  // -1 for 0.16
				$face = -1;
				if ($this->isSpectator()) {
					$this->inventory->sendHeldItem($this);
					if ($this->inventory->getHeldItemSlot() !== -1) {
						$this->inventory->sendContents($this);
					}
					return;
				}

				$itemInHand = $this->inventory->getItemInHand();
				if (!$itemInHand->deepEquals($item)) {
					$this->inventory->sendHeldItem($this);
					return;
				}

				if ($blockPosition['x'] != 0 || $blockPosition['y'] != 0 || $blockPosition['z'] != 0) {
					$vectorLength = sqrt($blockPosition['x'] ** 2 + $blockPosition['y'] ** 2 + $blockPosition['z'] ** 2);
					$aimPos = new Vector3($blockPosition['x'] / $vectorLength, $blockPosition['y'] / $vectorLength, $blockPosition['z'] / $vectorLength);
				} else {
					$aimPos = new Vector3(0, 0, 0);
				}

				$ev = new PlayerInteractEvent($this, $itemInHand, $aimPos, $face, PlayerInteractEvent::RIGHT_CLICK_AIR);
				$this->server->getPluginManager()->callEvent($ev);
				if ($ev->isCancelled()) {
					$this->inventory->sendHeldItem($this);
					if ($this->inventory->getHeldItemSlot() !== -1) {
						$this->inventory->sendContents($this);
					}
					return;
				}
				if (isset(self::$foodData[$itemInHand->getId()])) {
					if ($this->getFood() >= self::FOOD_LEVEL_MAX) {
						$this->startAction = -1;
						return;
					} elseif ($this->startAction > -1) {
						$diff = ($this->server->getTick() - $this->startAction);
						if ($diff > 20 && $diff < 100) {
							$this->eatFoodInHand();
						}
						$this->setDataFlag(self::DATA_FLAGS, self::DATA_FLAG_ACTION, false);
						$this->startAction = -1;
						return;
					}
				} elseif ($itemInHand->getId() === Item::SNOWBALL || $itemInHand->getId() === Item::SPLASH_POTION || $itemInHand->getId() === Item::EGG || $itemInHand->getId() === Item::BOTTLE_ENCHANTING) {
					$yawRad = $this->yaw / 180 * M_PI;
					$pitchRad = $this->pitch / 180 * M_PI;
					$nbt = new Compound("", [
						"Pos" => new Enum("Pos", [
							new DoubleTag("", $this->x),
							new DoubleTag("", $this->y + $this->getEyeHeight()),
							new DoubleTag("", $this->z)
						]),
						"Motion" => new Enum("Motion", [
							new DoubleTag("", -sin($yawRad) * cos($pitchRad)),
							new DoubleTag("", -sin($pitchRad)),
							new DoubleTag("", cos($yawRad) * cos($pitchRad))
						]),
						"Rotation" => new Enum("Rotation", [
							new FloatTag("", $this->yaw),
							new FloatTag("", $this->pitch)
						]),
					]);

					$f = 1.5;
					switch ($itemInHand->getId()) {
						case Item::SNOWBALL:
							$projectile = Entity::createEntity("Snowball", $this->chunk, $nbt, $this);
							break;
						case Item::EGG:
							$projectile = Entity::createEntity("Egg", $this->chunk, $nbt, $this);
							break;
						case Item::BOTTLE_ENCHANTING:
							$f = .3;
							$projectile = Entity::createEntity("BottleOEnchanting", $this->chunk, $nbt, $this);
							break;
                        case Item::SPLASH_POTION:
                            $projectile = Entity::createEntity("SplashPotion", $this->chunk, $nbt, $this, $itemInHand->getDamage());
                            break;
					}
					$projectile->setMotion($projectile->getMotion()->multiply($f));
					if ($this->isSurvival()) {
						$itemInHand->setCount($itemInHand->getCount() - 1);
						$this->inventory->setItemInHand($itemInHand->getCount() > 0 ? $itemInHand : Item::get(Item::AIR));
					}
					if ($projectile instanceof Projectile) {
						$this->server->getPluginManager()->callEvent($projectileEv = new ProjectileLaunchEvent($projectile));
						if ($projectileEv->isCancelled()) {
							$projectile->kill();
						} else {
							$projectile->spawnToAll();
							$this->level->addSound(new LaunchSound($this), $this->getViewers());
						}
					} else {
						$projectile->spawnToAll();
					}
				}
				if ($itemInHand->getId() === Item::FISHING_ROD) {
					$this->tryFishingHook();
				}

				$this->setDataFlag(self::DATA_FLAGS, self::DATA_FLAG_ACTION, true);
				$this->startAction = $this->server->getTick();
				return;
		}
	}

	/**
	 *
	 * @param integer[] $blockPosition
	 */
	protected function breakBlock($blockPosition) {
		if($this->spawned === false or $this->blocked === true or $this->dead === true){
			return;
		}

		$vector = new Vector3($blockPosition['x'], $blockPosition['y'], $blockPosition['z']);
		$item = $this->inventory->getItemInHand();

		$oldItem = clone $item;

		if($this->level->useBreakOn($vector, $item, $this) === true){
			if($this->isSurvival()){
				if(!$item->equals($oldItem, true) or $item->getCount() !== $oldItem->getCount()){
					$this->inventory->setItemInHand($item, $this);
					$this->inventory->sendHeldItem($this->hasSpawned);
				}
			}
			return;
		}

		$this->inventory->sendContents($this);
		$target = $this->level->getBlock($vector);
		$tile = $this->level->getTile($vector);

		$this->level->sendBlocks([$this], [$target], UpdateBlockPacket::FLAG_ALL_PRIORITY);

		$this->inventory->sendHeldItem($this);

		if($tile instanceof Spawnable){
			$tile->spawnTo($this);
		}
	}

	/**
	 * @minProtocolSupport 120
	 * @param InventoryTransactionPacket $packet
	 */
	private function normalTransactionLogic($packet) {
		$trGroup = new SimpleTransactionGroup($this);
		$isCraftResultTransaction = false;
		foreach ($packet->transactions as $trData) {
//			echo $trData . PHP_EOL;
			if ($trData->isDropItemTransaction()) {
				$this->tryDropItem($packet->transactions);
				return;
			}
			if ($trData->isCompleteEnchantTransaction()) {
				$this->tryEnchant($packet->transactions);
				return;
			}
			$transaction = $trData->convertToTransaction($this);
			if ($transaction == null) {
				// roolback
				$trGroup->sendInventories();
				return;
			}
			if ($trData->isCraftResultTransaction()) {
				$isCraftResultTransaction = true;
			}
//			echo " ---------- " . $transaction . PHP_EOL;
			$trGroup->addTransaction($transaction);
		}
		try {
			if (!$trGroup->execute()) {
				if ($isCraftResultTransaction) {
					$this->lastQuickCraftTransactionGroup[] = $trGroup;
//					echo '[INFO] Transaction execute holded.'.PHP_EOL;
				} else {
//					echo '[INFO] Transaction execute fail.'.PHP_EOL;
					$trGroup->sendInventories();
				}
			} else {
//				echo '[INFO] Transaction successfully executed.'.PHP_EOL;
			}
		} catch (\Exception $ex) {
//			echo '[INFO] Transaction execute exception. ' . $ex->getMessage() .PHP_EOL;
		}
	}

	/**
	 * @minprotocol 120
	 * @param SimpleTransactionData[] $transactionsData
	 */
	private function tryDropItem($transactionsData) {
		$dropItem = null;
		$transaction = null;
		foreach ($transactionsData as $trData) {
			if ($trData->isDropItemTransaction()) {
				$dropItem = $trData->newItem;
			} else {
				$transaction = $trData->convertToTransaction($this);
			}
		}
		if ($dropItem == null || $transaction == null) {
			$this->inventory->sendContents($this);
			if ($this->currentWindow != null) {
				$this->currentWindow->sendContents($this);
			}
			return;
		}
		//  check transaction and real data
		$inventory = $transaction->getInventory();
		if (!($inventory instanceof PlayerInventory)) {
			$inventory->sendContents($this);
			return;
		}
		$item = $inventory->getItem($transaction->getSlot());
		if ($item == null || !$item->deepEquals($dropItem) || $item->count < $dropItem->count) {
			$inventory->sendContents($this);
			return;
		}
		// generate event
		$ev = new PlayerDropItemEvent($this, $dropItem);
		$this->server->getPluginManager()->callEvent($ev);
		if($ev->isCancelled()) {
			$inventory->sendContents($this);
			return;
		}
		// finalizing drop item process
		if ($item->count == $dropItem->count) {
			$item = Item::get(Item::AIR, 0, 0);
		} else {
			$item->count -= $dropItem->count;
		}
		$inventory->setItem($transaction->getSlot(), $item);
		$motion = $this->getDirectionVector()->multiply(0.4);
		$this->level->dropItem($this->add(0, 1.3, 0), $dropItem, $motion, 40);
		$this->setDataFlag(self::DATA_FLAGS, self::DATA_FLAG_ACTION, false);
	}

	/**
	 * @minprotocol 120
	 * @param Item[] $craftSlots
	 * @param Recipe $recipe
	 * @throws \Exception
	 */
	private function tryApplyCraft(&$craftSlots, $recipe) {
		if ($recipe instanceof ShapedRecipe) {
			$ingredients = [];
			$itemGrid = $recipe->getIngredientMap();
			// convert map into list
			foreach ($itemGrid as $line) {
				foreach ($line as $item) {
//					echo $item . PHP_EOL;
					$ingredients[] = $item;
				}
			}
		} else if ($recipe instanceof ShapelessRecipe) {
			$ingredients = $recipe->getIngredientList();
		}
		foreach ($ingredients as $ingKey => $ingredient) {
			if ($ingredient == null || $ingredient->getId() == Item::AIR) {
				unset($ingredients[$ingKey]);
			}
		}
		$isAllCraftSlotsEmpty = true;
		$usedItemData = [];
		foreach ($craftSlots as $itemKey => &$item) {
			if ($item == null || $item->getId() == Item::AIR) {
				continue;
			}
			foreach ($ingredients as $ingKey => $ingredient) {
				$isItemsNotEquals = $item->getId() != $ingredient->getId() ||
						($item->getDamage() != $ingredient->getDamage() && $ingredient->getDamage() != 32767) ||
						$item->count < $ingredient->count;
				if ($isItemsNotEquals) {
					throw new \Exception('Recive bad recipe');
				}
				$isAllCraftSlotsEmpty = false;
				$usedItemData[$itemKey] = $ingredient->count;
				unset($ingredients[$ingKey]);
				break;
			}
		}
		if (!empty($ingredients)) {
			throw new \Exception('Recive bad recipe');
		}
		if ($isAllCraftSlotsEmpty) {
			throw new \Exception('All craft slots are empty');
		}
		$this->server->getPluginManager()->callEvent($ev = new CraftItemEvent($ingredients, $recipe, $this));
		if ($ev->isCancelled()) {
			throw new \Exception('Event was canceled');
		}
		foreach ($usedItemData as $itemKey => $itemCount) {
			$craftSlots[$itemKey]->count -= $itemCount;
			if ($craftSlots[$itemKey]->count == 0) {
				/** @important count = 0 is important */
				$craftSlots[$itemKey] = Item::get(Item::AIR, 0, 0);
			}
		}
	}

	/**
	 * @minprotocol 120
	 * @param Item[] $craftSlots
	 * @param Recipe $recipe
	 * @throws \Exception
	 */
	private function tryApplyQuickCraft(&$craftSlots, $recipe) {
		$ingredients = [];
		if ($recipe instanceof ShapedRecipe) {
			$itemGrid = $recipe->getIngredientMap();
			foreach ($itemGrid as $line) {
				$ingredients = array_merge($ingredients, $line);
			}
		} else if ($recipe instanceof ShapelessRecipe) {
			$ingredients = $recipe->getIngredientList();
		}
		foreach ($ingredients as $ingKey => $ingredient) {
			if ($ingredient == null || $ingredient->getId() == Item::AIR) {
				unset($ingredients[$ingKey]);
			}
		}
		$isAllCraftSlotsEmpty = true;
		foreach ($ingredients as $ingKey => $ingredient) {
			foreach ($craftSlots as $itemKey => &$item) {
				if ($item == null || $item->getId() == Item::AIR) {
					continue;
				}
				$isItemsEquals = $item->getId() == $ingredient->getId() && ($item->getDamage() == $ingredient->getDamage() || $ingredient->getDamage() == 32767);
				if ($isItemsEquals) {
					$isAllCraftSlotsEmpty = false;
					$itemCount = $item->getCount();
					$ingredientCount = $ingredient->getCount();
					if ($itemCount >= $ingredientCount) {
						if ($itemCount == $ingredientCount) {
							$item = Item::get(Item::AIR, 0, 0);
						} else {
							$item->setCount($itemCount - $ingredientCount);
						}
						unset($ingredients[$ingKey]);
						break;
					} else {
						$ingredient->setCount($ingredientCount - $itemCount);
						$item = Item::get(Item::AIR, 0, 0);
					}
				}
			}
		}
		if (!empty($ingredients)) {
			throw new \Exception('Recive bad recipe');
		}
		if ($isAllCraftSlotsEmpty) {
			throw new \Exception('All craft slots are empty');
		}
		$this->server->getPluginManager()->callEvent($ev = new CraftItemEvent($ingredients, $recipe, $this));
		if ($ev->isCancelled()) {
			throw new \Exception('Event was canceled');
		}
	}

	/**
	 *
	 * @param PlayerActionPacket $packet
	 */
	protected function crackBlock($packet) {
		if (!isset($this->actionsNum['CRACK_BLOCK'])) {
			$this->actionsNum['CRACK_BLOCK'] = 0;
		}
		$block = $this->level->getBlock(new Vector3($packet->x, $packet->y, $packet->z));
		$blockPos = [
			'x' => $packet->x,
			'y' => $packet->y,
			'z' => $packet->z,
		];

		$isNeedSendPackets = $this->actionsNum['CRACK_BLOCK'] % 4 == 0;
		$this->actionsNum['CRACK_BLOCK']++;

		$breakTime = ceil($block->getBreakTime($this->inventory->getItemInHand()) * 20);
		if ($this->actionsNum['CRACK_BLOCK'] >= $breakTime) {
			$this->breakBlock($blockPos);
		}

		if ($isNeedSendPackets) {
			$recipients = $this->getViewers();
			$recipients[] = $this;

			$pk = new LevelEventPacket();
			$pk->evid = LevelEventPacket::EVENT_PARTICLE_CRACK_BLOCK;
			$pk->x = $packet->x;
			$pk->y = $packet->y + 1;
			$pk->z = $packet->z;
			$pk->data = $block->getId() | ($block->getDamage() << 8);
			Server::broadcastPacket($recipients, $pk);
			$this->sendSound(LevelSoundEventPacket::SOUND_HIT, $blockPos, MultiversionEntity::ID_PLAYER, $block->getId(), $recipients);
		}
	}

	/**
	 * @minprotocol 120
	 * @param SimpleTransactionData[] $transactionsData
	 */
	private function tryEnchant($transactionsData) {
		foreach ($transactionsData as $trData) {
			if (!$trData->isUpdateEnchantSlotTransaction() || $trData->oldItem->getId() == Item::AIR) {
				continue;
			}
			$transaction = $trData->convertToTransaction($this);
			if (!is_null($transaction)) {
				$inventory = $transaction->getInventory();
				$inventory->setItem($transaction->getSlot(), $transaction->getTargetItem());
			}
		}
	}

	 /**
	 *
	 * @param integer $soundId
	 * @param float[] $position
	 */
	public function sendSound($soundId, $position, $entityType = MultiversionEntity::ID_NONE, $blockId = -1, $targets = []) {
		$pk = new LevelSoundEventPacket();
		$pk->eventId = $soundId;
		$pk->x = $position['x'];
		$pk->y = $position['y'];
		$pk->z = $position['z'];
		$pk->blockId = $blockId;
		$pk->entityType = $entityType;
		if (empty($targets)) {
			$this->dataPacket($pk);
		} else {
			Server::broadcastPacket($targets, $pk);
		}
	}

	public function customInteract($packet) {

	}

	public function fall($fallDistance) {
		if (!$this->allowFlight && !$this->elytraIsActivated) {
			parent::fall($fallDistance);
		}
	}

	protected function onJump() {

 	}

	protected function releaseUseItem() {
		$itemInHand = $this->inventory->getItemInHand();
		if ($this->startAction > -1 && $itemInHand->getId() === Item::BOW) {
			$bow = $this->inventory->getItemInHand();
			if ($this->isSurvival() and !$this->inventory->contains(Item::get(Item::ARROW, 0, 1))) {
				$this->inventory->sendContents($this);
				return;
			}

            $yawRad = $this->yaw / 180 * M_PI;
            $pitchRad = $this->pitch / 180 * M_PI;
			$nbt = new Compound("", [
				"Pos" => new Enum("Pos", [
					new DoubleTag("", $this->x),
					new DoubleTag("", $this->y + $this->getEyeHeight()),
					new DoubleTag("", $this->z)
				]),
				"Motion" => new Enum("Motion", [
                    new DoubleTag("", -sin($yawRad) * cos($pitchRad)),
                    new DoubleTag("", -sin($pitchRad)),
                    new DoubleTag("", cos($yawRad) * cos($pitchRad))
				]),
				"Rotation" => new Enum("Rotation", [
                    new FloatTag("", $this->yaw),
                    new FloatTag("", $this->pitch)
				]),
				"Fire" => new ShortTag("Fire", $this->isOnFire() ? 45 * 60 : 0)
			]);

			$diff = ($this->server->getTick() - $this->startAction);
			$p = $diff / 20;
			$f = min((($p ** 2) + $p * 2) / 3, 1) * 2;
			$ev = new EntityShootBowEvent($this, $bow, Entity::createEntity("Arrow", $this->chunk, $nbt, $this, $f >= 1), $f);

			if ($f < 0.1 or $diff < 5) {
				$ev->setCancelled();
			}

			$this->server->getPluginManager()->callEvent($ev);

			$projectile = $ev->getProjectile();
			if ($ev->isCancelled()) {
				$projectile->kill();
				$this->inventory->sendContents($this);
			} else {
				$projectile->setMotion($projectile->getMotion()->multiply($ev->getForce()));
				if ($this->isSurvival()) {
					if (is_null($bow->getEnchantment(Enchantment::TYPE_BOW_INFINITY))) {
						$this->inventory->removeItemWithCheckOffHand(Item::get(Item::ARROW, 0, 1));
					}

					$bow->setDamage($bow->getDamage() + 1);
					if ($bow->getDamage() >= 385) {
						$this->inventory->setItemInHand(Item::get(Item::AIR, 0, 0));
					} else {
						$this->inventory->setItemInHand($bow);
					}
				}
				if ($projectile instanceof Projectile) {
					$this->server->getPluginManager()->callEvent($projectileEv = new ProjectileLaunchEvent($projectile));
					if ($projectileEv->isCancelled()) {
						$projectile->kill();
					} else {
						$projectile->spawnToAll();
						$recipients = $this->hasSpawned;
						$recipients[$this->id] = $this;
						$pk = new LevelSoundEventPacket();
						$pk->eventId = LevelSoundEventPacket::SOUND_BOW;
						$pk->x = $this->x;
						$pk->y = $this->y;
						$pk->z = $this->z;
						$pk->blockId = -1;
						$pk->entityType = 1;
						Server::broadcastPacket($recipients, $pk);
					}
				} else {
					$projectile->spawnToAll();
				}
			}
		} else if ($itemInHand->getId() === Item::BUCKET && $itemInHand->getDamage() === 1) { //Milk!
			$this->server->getPluginManager()->callEvent($ev = new PlayerItemConsumeEvent($this, $itemInHand));
			if ($ev->isCancelled()) {
				$this->inventory->sendContents($this);
				return;
			}

			$pk = new EntityEventPacket();
			$pk->eid = $this->getId();
			$pk->event = EntityEventPacket::USE_ITEM;
			$viewers = $this->getViewers();
			$viewers[] = $this;
			Server::broadcastPacket($viewers, $pk);

			if ($this->isSurvival()) {
				--$itemInHand->count;
				$this->inventory->setItemInHand($itemInHand);
				$this->inventory->addItem(Item::get(Item::BUCKET, 0, 1));
			}

			$this->removeAllEffects();
        } else if($itemInHand instanceof GoldenApple) {
            $ev = new PlayerItemConsumeEvent($this, $itemInHand);
            $this->server->getPluginManager()->callEvent($ev);
            if (!$ev->isCancelled()) {
                $itemInHand->onConsume($this);
            } else {
                $this->setFood($this->foodLevel);
                $this->inventory->sendContents($this);
            }
        } else if($itemInHand instanceof EnchantedGoldenApple) {
            $ev = new PlayerItemConsumeEvent($this, $itemInHand);
            $this->server->getPluginManager()->callEvent($ev);
            if (!$ev->isCancelled()) {
                $itemInHand->onConsume($this);
            } else {
                $this->setFood($this->foodLevel);
                $this->inventory->sendContents($this);
            }
        } else if($itemInHand instanceof Potion && $itemInHand->canBeConsumed()) {
            $ev = new PlayerItemConsumeEvent($this, $itemInHand);
            $this->server->getPluginManager()->callEvent($ev);
            if (!$ev->isCancelled()) {
                $itemInHand->onConsume($this);
            } else {
                $this->inventory->sendContents($this);
            }
		} else {
			$this->inventory->sendContents($this);
		}
	}

	protected function useItem120() {
		$slot = $this->inventory->getItemInHand();
		if($slot instanceof Potion && $slot->canBeConsumed()) {
            $ev = new PlayerItemConsumeEvent($this, $slot);
            $this->server->getPluginManager()->callEvent($ev);
            if (!$ev->isCancelled()) {
                $slot->onConsume($this);
            } else {
                $this->inventory->sendContents($this);
            }
        }else if($slot instanceof GoldenApple){
            $ev = new PlayerItemConsumeEvent($this, $slot);
            $this->server->getPluginManager()->callEvent($ev);
            if(!$ev->isCancelled()){
                $slot->onConsume($this);
            }else{
                $this->inventory->sendContents($this);
            }
        }else if($slot instanceof EnchantedGoldenApple){
            $ev = new PlayerItemConsumeEvent($this, $slot);
            $this->server->getPluginManager()->callEvent($ev);
            if(!$ev->isCancelled()){
                $slot->onConsume($this);
            }else{
                $this->inventory->sendContents($this);
            }
		} else {
			$this->eatFoodInHand();
		}
	}

	public function getServerAddress() {
		return $this->serverAddress;
	}

	public function getClientlanguageCode() {
		return $this->languageCode;
	}

	public function getClientVersion() {
		return $this->clientVersion;
	}

	public function getOriginalProtocol() {
		return $this->originalProtocol;
	}

	/**
	 *
	 * @param CustomUI $modalWindow
	 * @return boolean
	 */
	public function showModal($modalWindow) {
		if ($this->isNeedToSendModal($modalWindow)) {
			$pk = new ShowModalFormPacket();
			$pk->formId = $this->lastModalId++;
			$pk->data = $modalWindow->toJSON();
			$this->dataPacket($pk);
			$this->activeModalWindows[$pk->formId] = $modalWindow;
			return true;
		}
		return false;
	}

	/**
	 *
	 * @param CustomUI $window
	 * @return boolean
	 */
	protected function isNeedTosendModal($window) {
		if ($this->lastUpdate - $this->lastShowModalTick > 60) {
			$this->activeModalWindows = [];
			$this->lastShowModalTick = $this->lastUpdate;
			return true;
		}
		if (!empty($this->activeModalWindows)) {
			$windowData = $window->toJSON();
			foreach ($this->activeModalWindows as $formId => $form) {
				if ($windowData === $form->toJSON()) {
					return false;
				}
				unset($this->activeModalWindows[$formId]);
			}
		}
		return true;
	}

	/**
	 *
	 * @param integer $formId
	 * @param string|null $data Sting in JSON format or null
	 */
	public function checkModal($formId, $data) {
		if (isset($this->activeModalWindows[$formId])) {
			$currentModel = $this->activeModalWindows[$formId];
			unset($this->activeModalWindows[$formId]);
			if ($data === null) { // The modal window was closed manually
				$currentModel->close($this);
			} else { // Player send some data
				$currentModel->handle($data, $this);
			}

		}
	}

	protected function revertMovement(Vector3 $pos, $yaw = 0, $pitch = 0) {
		$this->sendPosition($pos, $yaw, $pitch, MovePlayerPacket::MODE_RESET);
		$this->newPosition = null;
	}

	protected function processMovement($tickDiff) {
		if (empty($this->lastMoveBuffer)) {
			return;
		}
		$pk = $this->server->getNetwork()->getPacket(0x13, $this->getPlayerProtocol());
		if (is_null($pk)) {
			$this->lastMoveBuffer = '';
			return;
		}
		$pk->setBuffer($this->lastMoveBuffer);
		$this->lastMoveBuffer = '';
		$pk->decode($this->getPlayerProtocol());
		$this->handleDataPacket($pk);
		$this->countMovePacketInLastTick = 0;
		if (!$this->isAlive() || !$this->spawned || $this->newPosition === null) {
			$this->setMoving(false);
			return;
		}

		$newPos = $this->newPosition;
		if ($this->chunk === null || !$this->chunk->isGenerated()) {
			$chunk = $this->level->getChunk($newPos->x >> 4, $newPos->z >> 4);
			if ($chunk === null || !$chunk->isGenerated()) {
				$this->revertMovement($this, $this->lastYaw, $this->lastPitch);
				$this->nextChunkOrderRun = 0;
				return;
			}
		}

		$from = new Location($this->x, $this->y, $this->z, $this->lastYaw, $this->lastPitch, $this->level);
		$to = new Location($newPos->x, $newPos->y, $newPos->z, $this->yaw, $this->pitch, $this->level);

		$this->isTeleportedForMoveEvent = false;
		$deltaAngle = abs($from->yaw - $to->yaw) + abs($from->pitch - $to->pitch);
		$distanceSquared = ($this->newPosition->x - $this->x) ** 2 + ($this->newPosition->y - $this->y) ** 2 + ($this->newPosition->z - $this->z) ** 2;
		if (($distanceSquared > 0.0625 || $deltaAngle > 10)) {
			$isFirst = ($this->lastX === null || $this->lastY === null || $this->lastZ === null);
			if (!$isFirst) {
				if (!$this->isSpectator() && $this->needCheckMovementInBlock()) {
					$toX = floor($to->x);
					$toZ = floor($to->z);
					$toY = ceil($to->y);
					$block = $from->level->getBlock(new Vector3($toX, $toY, $toZ));
					$blockUp = $from->level->getBlock(new Vector3($toX, $toY + 1, $toZ));
					if (!$block->isTransparent() || !$blockUp->isTransparent()) {
						if (!$blockUp->isTransparent()) {
							$blockLow = $from->level->getBlock(new Vector3($toX, $toY - 1, $toZ));
							if ($from->y == $to->y && !$blockLow->isTransparent()) {
								$this->revertMovement($this, $this->lastYaw, $this->lastPitch);
								return;
							}
						} else {
							$blockUpUp = $from->level->getBlock(new Vector3($toX, $toY + 2, $toZ));
							if (!$blockUpUp->isTransparent()) {
								$this->revertMovement($this, $this->lastYaw, $this->lastPitch);
								return;
							}
							$blockFrom = $from->level->getBlock(new Vector3($from->x, $from->y, $from->z));
							if ($blockFrom instanceof Liquid) {
								$this->revertMovement($this, $this->lastYaw, $this->lastPitch);
								return;
							}
						}
					}
				}
				$ev = new PlayerMoveEvent($this, $from, $to);
				$this->setMoving(true);
				$this->server->getPluginManager()->callEvent($ev);
				if ($this->isTeleportedForMoveEvent) {
					return;
				}
				if ($ev->isCancelled()) {
					$this->revertMovement($this, $this->lastYaw, $this->lastPitch);
					return;
				}
				if ($to->distanceSquared($ev->getTo()) > 0.01) {
					$this->teleport($ev->getTo());
					return;
				}
			}
			$dx = $to->x - $from->x;
			$dy = $to->y - $from->y;
			$dz = $to->z - $from->z;
			$this->move($dx, $dy, $dz);
			if ($this->isTeleportedForMoveEvent) {
				return;
			}
			$this->x = $to->x;
			$this->y = $to->y;
			$this->z = $to->z;
			$this->lastX = $to->x;
			$this->lastY = $to->y;
			$this->lastZ = $to->z;
			$this->lastYaw = $to->yaw;
			$this->lastPitch = $to->pitch;
			$this->level->addEntityMovement($this->getViewers(), $this->getId(), $this->x, $this->y + $this->getVisibleEyeHeight(), $this->z, $this->yaw, $this->pitch, $this->yaw, true);
			if (!is_null($this->fishingHook)) {
				if ($this->distanceSquared($this->fishingHook) > 400 || $this->inventory->getItemInHand()->getId() !== Item::FISHING_ROD) {
					$this->clearFishingHook();
				}
			}

			if (!$this->isSpectator()) {
				$this->checkNearEntities($tickDiff);
			}
			if ($distanceSquared == 0) {
				$this->speed = new Vector3(0, 0, 0);
				$this->setMoving(false);
			} else {
				$this->speed = $from->subtract($to);
				if ($this->nextChunkOrderRun > 20) {
					$this->nextChunkOrderRun = 20;
				}
			}
			// Exhaustion logic
			if ($this->foodLevel > 0 && $this->getFoodEnabled()) {
				$distance = sqrt($dx ** 2 + $dz** 2);
				if ($distance > 0) {
					if ($this->isSprinting()) {
						$this->exhaustion += $distance * 0.1;
					} else if ($this->isCollideWithWater()) {
						$this->exhaustion += $distance * 0.01;
					}
				}
			}
		}
		$this->newPosition = null;
	}

	public function entityBaseTick($tickDiff = 1) {
		if ($this->dead === true) {
			return false;
		}

		if ($this->attackTime > 0) {
			$this->attackTime -= $tickDiff;
		}

		if ($this->noDamageTicks > 0) {
			$this->noDamageTicks -= $tickDiff;
		}

		if ($this->y < 0) {
			$ev = new EntityDamageEvent($this, EntityDamageEvent::CAUSE_VOID, 20);
			$this->attack($ev->getFinalDamage(), $ev);
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

		if ($this->isInsideOfSolid()) {
			$ev = new EntityDamageEvent($this, EntityDamageEvent::CAUSE_SUFFOCATION, 1);
			$this->attack($ev->getFinalDamage(), $ev);
		}

		// stop sprinting if not solid block under feets
		$isInsideWater = $this->isInsideOfWater();
		$blockIDUnderFeets = $this->level->getBlockIdAt(floor($this->x), floor($this->y), floor($this->z));
		if ($this->protocol <= ProtocolInfo::PROTOCOL_201 && $this->isSprinting() && ((isset(Block::$liquid[$blockIDUnderFeets]) && Block::$liquid[$blockIDUnderFeets]) || $isInsideWater)) {
			$this->setSprinting(false);
		}
		$isShouldResetAir = true;
		if ($isInsideWater && !$this->hasEffect(Effect::WATER_BREATHING)) {
			$airTicks = $this->getDataProperty(self::DATA_AIR) - $tickDiff;
			if ($airTicks <= -20) {
				$airTicks = 0;
				$ev = new EntityDamageEvent($this, EntityDamageEvent::CAUSE_DROWNING, 2);
				$this->attack($ev->getFinalDamage(), $ev);
			}
			$this->setAirTick($airTicks);
			if ($this instanceof Player) {
				$this->setDataFlag(self::DATA_FLAGS, self::DATA_FLAG_NOT_IN_WATER, false, self::DATA_TYPE_LONG, false);
				$this->sendSelfData();
			}
		} else {
			if ($this->getDataProperty(self::DATA_AIR) != 300) {
				$this->setAirTick(300);
				if (($this instanceof Player)) {
					$this->setDataFlag(self::DATA_FLAGS, self::DATA_FLAG_NOT_IN_WATER, true, self::DATA_TYPE_LONG, false);
					$this->sendSelfData();
				}
			}
		}

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
			}
		}
		return true;
	}

	protected function checkBlockCollision() {
		parent::checkBlockCollision();
		$blockAbove = $this->level->getBlock(new Vector3(floor($this->x), floor($this->y - 1), floor($this->z)));		
		if ($blockAbove !== null && !($blockAbove instanceof Liquid) && $blockAbove->hasEntityCollision()) {
			$blockAbove->onEntityCollide($this);
		}
	}
	
	protected function sendServerSettingsModal($modalWindow) {
		$pk = new ServerSettingsResponsetPacket();
		$pk->formId = $this->lastModalId++;
		$pk->data = $modalWindow->toJSON();
		$this->dataPacket($pk);
		$this->activeModalWindows[$pk->formId] = $modalWindow;
	}

	protected function sendServerSettings() {

	}

	public function needEncrypt() {
		return true;
	}

	public function updatePlayerSkin($oldSkinName, $newSkinName) {
		$pk = new PlayerSkinPacket();
		$pk->uuid = $this->getUniqueId();
		$pk->newSkinId = $this->skinName;
		$pk->newSkinName = $newSkinName;
		$pk->oldSkinName = $oldSkinName;
		$pk->newSkinByteData = $this->skin;
		$pk->newCapeByteData = $this->capeData;
		$pk->newSkinGeometryName = $this->skinGeometryName;
		$pk->newSkinGeometryData = $this->skinGeometryData;
		$pk->additionalSkinData = $this->additionalSkinData;
		$this->server->batchPackets($this->server->getOnlinePlayers(), [$pk]);
	}

	/**
	 *
	 * @return integer
	 */
	public function getSubClientId() {
		return $this->subClientId;
	}

	/**
	 *
	 * @return Player|null
	 */
	public function getParent() {
		return $this->parent;
	}

	/**
	 *
	 * @param integer $subClientId
	 */
	public function removeSubClient($subClientId) {
		if (isset($this->subClients[$subClientId])) {
			unset($this->subClients[$subClientId]);
		}
	}

	/**
	 * @minprotocol 120
	 *
	 * @param SubClientLoginPacket $packet
	 * @param Player $parent
	 * @return type
	 */
	public function subAuth($packet, $parent) {
		$this->username = TextFormat::clean($packet->username);
		$this->xblName = $this->username;
		$this->displayName = $this->username;
		$this->setNameTag($this->username);
		$this->iusername = strtolower($this->username);

		$this->randomClientId = $packet->clientId;
		$this->loginData = ["clientId" => $packet->clientId, "loginData" => null];
		$this->uuid = $packet->clientUUID;
		if (is_null($this->uuid)) {
			$this->close("", "Sorry, your client is broken.");
			return false;
		}

		$this->parent = $parent;
		$this->xuid = $packet->xuid;
		$this->rawUUID = $this->uuid->toBinary();
		$this->clientSecret = $packet->clientSecret;
		$this->protocol = $parent->getPlayerProtocol();
		$this->setSkin($packet->skin, $packet->skinName, $packet->skinGeometryName, $packet->skinGeometryData, $packet->capeData, $packet->premiumSkin);
		$this->subClientId = $packet->targetSubClientID;

		// some statistics information
		$this->deviceType = $parent->getDeviceOS();
		$this->inventoryType = $parent->getInventoryType();
		$this->languageCode = $parent->languageCode;
		$this->serverAddress = $parent->serverAddress;
		$this->clientVersion = $parent->clientVersion;
		$this->originalProtocol = $parent->originalProtocol;
		$this->platformChatId = $parent->platformChatId;

		$this->identityPublicKey = $packet->identityPublicKey;

		$pk = new PlayStatusPacket();
		$pk->status = PlayStatusPacket::LOGIN_SUCCESS;
		$this->dataPacket($pk);

		$this->loggedIn = true;
		$this->completeLogin();

		return $this->loggedIn;
	}

	private function getNonValidProtocolMessage($protocol) {
		if ($protocol > ProtocolInfo::PROTOCOL_160) {
			$pk = new PlayStatusPacket();
			$pk->status = PlayStatusPacket::LOGIN_FAILED_SERVER;
			$this->dataPacket($pk);
			return TextFormat::WHITE . "We don't support this client version yet.\n" . TextFormat::WHITE ."        The update is coming soon.";
		} else {
			$pk = new PlayStatusPacket();
			$pk->status = PlayStatusPacket::LOGIN_FAILED_CLIENT;
			$this->dataPacket($pk);
			return TextFormat::WHITE . "Please update your client version to join";
		}
	}

	public function sendFullPlayerList() {
		$players = $this->server->getOnlinePlayers();
		$isNeedSendXUID = $this->originalProtocol >= ProtocolInfo::PROTOCOL_140;
		$playersWithProto140 = [];
		$otherPlayers = [];
		$pk = new PlayerListPacket();
		$pk->type = PlayerListPacket::TYPE_ADD;
		foreach ($players as $player) {
			$entry = [$player->getUniqueId(), $player->getId(), $player->getName(), $player->getSkinName(), $player->getSkinData(), $player->getCapeData(), $player->getSkinGeometryName(), $player->getSkinGeometryData()];
			if ($isNeedSendXUID) {
				$entry[] = $player->getXUID();
			}
			$entry[9] = $player->getDeviceOS();
			$entry[10] = $player->additionalSkinData;
			$pk->entries[] = $entry;
			// collect player with different packet logic
			if ($player !== $this) {
				if ($player->getOriginalProtocol() >= ProtocolInfo::PROTOCOL_140) {
					$playersWithProto140[] = $player;
				} else {
					$otherPlayers[] = $player;
				}
			}
		}
		$this->server->batchPackets([$this], [$pk]);

		if (count($playersWithProto140) > 0) {
			$pk = new PlayerListPacket();
			$pk->type = PlayerListPacket::TYPE_ADD;
			$pk->entries[] = [$this->getUniqueId(), $this->getId(), $this->getName(), $this->getSkinName(), $this->getSkinData(), $this->getCapeData(), $this->getSkinGeometryName(), $this->getSkinGeometryData(), $this->getXUID(), $this->getDeviceOS(), $this->additionalSkinData];
			$this->server->batchPackets($playersWithProto140, [$pk]);
		}
		if (count($otherPlayers) > 0) {
			$pk = new PlayerListPacket();
			$pk->type = PlayerListPacket::TYPE_ADD;
			$pk->entries[] = [$this->getUniqueId(), $this->getId(), $this->getName(), $this->getSkinName(), $this->getSkinData(), $this->getCapeData(), $this->getSkinGeometryName(), $this->getSkinGeometryData()];
			$this->server->batchPackets($otherPlayers, [$pk]);
		}
	}

	public function setVehicle($vehicle) {
		$this->currentVehicle = $vehicle;
	}
	
	protected function getBlocksAround() {
		if ($this->blocksAround === null) {			
			$this->blocksAround = [];
			$this->blocksAround[] = $this->level->getBlock(new Vector3(floor($this->x), floor($this->y), floor($this->z)));
			if (is_null($this->currentVehicle)) {
				$minX = floor($this->x - 0.3);
				$minZ = floor($this->z - 0.3);
				$maxX = floor($this->x + 0.3);
				$maxZ = floor($this->z + 0.3);
				$y = floor($this->y + 1);
				for ($z = $minZ; $z <= $maxZ; $z++) {
					for ($x = $minX; $x <= $maxX; $x++) {
						$block = $this->level->getBlock(new Vector3($x, $y, $z));
						$this->blocksAround[] = $block;
					}
				}
			}
		}
		return $this->blocksAround;
	}
	
	public function setFishingHook($hook) {
		$this->fishingHook = $hook;
	}
	
	public function clearFishingHook() {
		if (!is_null($this->fishingHook)) {
			$this->fishingHook->close();
			$this->fishingHook = null;
		}
	}
	
	public function tryFishingHook() {
		if (is_null($this->fishingHook)) {
			$yawRad = $this->yaw / 180 * M_PI;
			$pitchRad = $this->pitch / 180 * M_PI;
			$nbt = new Compound("", [
				"Pos" => new Enum("Pos", [
					new DoubleTag("", $this->x),
					new DoubleTag("", $this->y + $this->getEyeHeight()),
					new DoubleTag("", $this->z)
						]),
				"Motion" => new Enum("Motion", [
					new DoubleTag("", -sin($yawRad) * cos($pitchRad)),
					new DoubleTag("", -sin($pitchRad)),
					new DoubleTag("", cos($yawRad) * cos($pitchRad))
						]),
				"Rotation" => new Enum("Rotation", [
					new FloatTag("", $this->yaw),
					new FloatTag("", $this->pitch)
						]),
			]);
			$hook = Entity::createEntity("FishingHook", $this->chunk, $nbt, $this);
			if (!is_null($hook)) {
				$hook->spawnToAll();
				$this->setFishingHook($hook);
			}
		} else {
			$this->clearFishingHook();
		}
	}
	
	public function setInteractButtonText($text, $force = false) {
		if ($force || $this->interactButtonText != $text) {
			$this->interactButtonText = $text;
			$pk = new SetEntityDataPacket();
			$pk->eid = $this->id;
			$pk->metadata = [self::DATA_BUTTON_TEXT => [self::DATA_TYPE_STRING, $this->interactButtonText]];
			$this->dataPacket($pk);
		}
	}

	protected function onCloseSelfInventory() {

	}

	protected function onStartFly() {

	}

	protected function onStopFly() {

	}

	protected function onPlayerInput($forward, $sideway, $isJump, $isSneak) {

	}

	protected function onPlayerRequestMap($mapId) {

	}

	public function setCompassDestination($x, $y, $z) {
		$packet = new SetSpawnPositionPacket();
		$packet->spawnType = SetSpawnPositionPacket::SPAWN_TYPE_WORLD_SPAWN;
		$packet->x = $x;
		$packet->y = $y;
		$packet->z = $z;
		$this->dataPacket($packet);
	}


	protected function changeHeldItem($item, $selectedSlot, $slot) {
		$hotbarItem = $this->inventory->getHotbatSlotItem($selectedSlot);
		$isNeedSendToHolder = !($hotbarItem->deepEquals($item));
		$this->inventory->setHeldItemIndex($selectedSlot, $isNeedSendToHolder);
		$this->inventory->setHeldItemSlot($slot);
		$this->setDataFlag(self::DATA_FLAGS, self::DATA_FLAG_ACTION, false);
		if ($hotbarItem->getId() === Item::FISHING_ROD) {
			$this->setInteractButtonText('Fish');
		} else {
			$this->setInteractButtonText('');
		}
	}

	protected function switchLevel(Level $targetLevel) {
		$this->server->getPluginManager()->callEvent($ev = new EntityLevelChangeEvent($this, $this->level, $targetLevel));
		if ($ev->isCancelled()) {
			return false;
		}
		$this->despawnFromAll();
		$this->level->removeEntity($this);
		if ($this->chunk !== null) {
			$this->chunk->removeEntity($this);
		}
		$this->chunk = null;
		$X = $Z = null;
		foreach ($this->usedChunks as $index => $d) {
			Level::getXZ($index, $X, $Z);
			$this->unloadChunk($X, $Z);
		}
		$this->usedChunks = [];
		$this->setLevel($targetLevel);
		$this->level->addEntity($this);
		if ($this->spawned) {
			$pk = new SetTimePacket();
			$pk->time = $this->level->getTime();
			$pk->started = $this->level->stopTime == false;
			$this->dataPacket($pk);
			$this->setDaylightCycle(!$this->level->stopTime);
		}
		$this->scheduleUpdate();
		return true;
	}

	public function setLastMovePacket($buffer) {
		$this->lastMoveBuffer = $buffer;
		$this->countMovePacketInLastTick++;
	}

	/**
	 * @param PEPacket[] $packets
	 */
	public function sentBatch($packets) {
		$buffer = '';
		$protocol = $this->getPlayerProtocol();
		foreach ($packets as $pk) {
			$pk->encode($protocol);
			$pkBuf = $pk->getBuffer();
			$buffer .= Binary::writeVarInt(strlen($pkBuf)) . $pkBuf;
		}
		$pk = new BatchPacket();
		$pk->payload = zlib_encode($buffer, ZLIB_ENCODING_DEFLATE, 7);
		$this->dataPacket($pk);
	}

	public function needAntihackCheck() {
		return true;
	}

	/**
	 * @param string $packetBuffer
	 * @param integer $delay
	 * @throws Extension
	 */
	public function addDelayedPacket($packetBuffer, $delay = 1) {
		if ($delay < 1) {
			throw new \Exception("Delay should be positive");
		}
		$delayedTick = $this->server->getTick() + $delay;
		if (!isset($this->delayedPackets[$delayedTick])) {
			$this->delayedPackets[$delayedTick] = [];
		}
		$this->delayedPackets[$delayedTick][] = $packetBuffer;
	}

	protected function checkSignChange($sign, $namedtag) {
		$nbt = new NBT(NBT::LITTLE_ENDIAN);
		$nbt->read($namedtag, false, true);
		$nbtData = $nbt->getData();
		$isNotCreator = !isset($sign->namedtag->Creator) || $sign->namedtag["Creator"] !== $this->username;
		// check tile id
		if ($nbtData["id"] !== Tile::SIGN || $isNotCreator) {
			$sign->spawnTo($this);
			return;
		}
		// collect sign text lines
		$signText = [];
		$signText = explode("\n", $nbtData['Text']);
		for ($i = 0; $i < 4; $i++) {
			$signText[$i] = isset($signText[$i]) ? TextFormat::clean($signText[$i], $this->removeFormat) : '';
		}
		unset($nbtData['Text']);
		// event part
		$ev = new SignChangeEvent($sign->getBlock(), $this, $signText);
		$this->server->getPluginManager()->callEvent($ev);
		if ($ev->isCancelled()) {
			$sign->spawnTo($this);
		} else {
			$sign->setText($ev->getLine(0), $ev->getLine(1), $ev->getLine(2), $ev->getLine(3));
		}
	}

	public function needCheckMovementInBlock() {
		return true;
	}

	public function getScoreboard() {
		return $this->scoreboard;
	}

	public function setScoreboard($scoreboard) {
		$this->scoreboard = $scoreboard;
	}

	protected function processCommand($commandLine) {
		try {
			$commandPreprocessEvent = new PlayerCommandPreprocessEvent($this, $commandLine);
			$this->server->getPluginManager()->callEvent($commandPreprocessEvent);
			if ($commandPreprocessEvent->isCancelled()) {
				if ($commandLine !== 'version' && $commandLine !== 'ver') {
				    return;
                }
			}
			$this->server->dispatchCommand($this, $commandLine);
			$commandPostprocessEvent = new PlayerCommandPostprocessEvent($this, $commandLine);
			$this->server->getPluginManager()->callEvent($commandPostprocessEvent);
		} catch (\Exception $ex) {
			error_log($ex->getMessage());
		}
	}

	public function setDaylightCycle($val) {
		if ($this->doDaylightCycle != $val) {
			$this->doDaylightCycle = $val;
			$pk = new GameRulesChangedPacket();
			$pk->gameRules = ["doDaylightCycle" => [1, $val]];
			$this->dataPacket($pk);
		}
	}
	
	protected function updateFallState($distanceThisTick, $onGround) {
		if ($onGround || !$this->allowFlight && !$this->elytraIsActivated) {
			parent::updateFallState($distanceThisTick, $onGround);
		}
	}
	
	protected function sendAllInventories(){
		if (!is_null($this->currentWindow)) {
			$this->currentWindow->sendContents($this);
		}
		$this->getInventory()->sendContents($this);
	}

}
