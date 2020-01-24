<?php

namespace pocketmine\network\multiversion;

use pocketmine\Server;

abstract class Entity {
	
	const NAME_NONE = ":";
	const ID_NONE = 1;
	
	/** Drop */
	const ID_ITEM = 64; //minecraft:item
	const ID_EXP_ORB = 69; //minecraft:xp_orb

	/** Blocks */
	const ID_TNT = 65; //minecraft:tnt
	const ID_FALLING_BLOCK = 66; //minecraft:falling_block
	const ID_MOVING_BLOCK = 67; //moving_block

	/** Immobile and projectiles */
	const ID_ARMOR_STAND = 61; //armor_stand
	const ID_EXP_BOTTLE = 68; //xp_bottle
	const ID_EYE_OF_ENDER_SIGNAL = 70; //eye_of_ender_signal
	const ID_ENDER_CRYSTAL = 71; //ender_crystal
	const ID_FIREWORK_ROCKET = 72; //fireworks_rocket
	const ID_TRIDENT = 73; //thrown_trident
	const ID_SHULKER_BULLET = 76; //shulker_bullet
	const ID_FISHING_HOOK = 77; //fishing_hook
	const ID_DRAGON_FIREBALL = 79; //dragon_fireball
	const ID_ARROW = 80; //arrow
	const ID_SNOWBALL = 81; //snowball
	const ID_EGG = 82; //egg
	const ID_PAINTING = 83; //painting
	const ID_MINECART = 84; //minecart
	const ID_GHAST_FIREBALL = 85; //fireball
	const ID_SPLASH_POTION = 86; //splash_potion
	const ID_ENDER_PERL = 87; //ender_pearl
	const ID_LEASH_KNOT = 88; //leash_knot
	const ID_WITHER_SKULL = 89; //wither_skull
	const ID_BOAT = 90; //boat
	const ID_WITHER_SKULL_DANGEROUS = 91; //wither_skull_dangerous
	const ID_LIGHTNING_BOLT = 93; //lightning_bolt
	const ID_SMALL_FIREBALL = 94; //small_fireball
	const ID_AREA_EFFECT_CLOUD = 95; //area_effect_cloud
	const ID_MINECART_HOPPER = 96; //hopper_minecart
	const ID_MINECART_TNT = 97; //tnt_minecart
	const ID_MINECART_CHEST = 98; //chest_minecart
	const ID_MINECART_COMMAND_BLOCK = 100; //command_block_minecart
	const ID_LINGERING_POTION = 101; //lingering_potion
	const ID_LLAMA_SPLIT = 102; //llama_spit
	const ID_EVOCATION_FANGS = 103; //evocation_fang

	/** Hostile mobs */
	const ID_ZOMBIE = 32; //zombie
	const ID_CREEPER = 33; //creeper
	const ID_SKELETON = 34; //skeleton
	const ID_SPIDER = 35; //spider
	const ID_ZOMBIE_PIGMAN = 36; //zombie_pigman
	const ID_SLIME = 37; //slime
	const ID_ENDERMAN = 38; //enderman
	const ID_SILVERFISH = 39; //silverfish
	const ID_CAVE_SPIDER = 40; //cave_spider
	const ID_GHAST = 41; //ghast
	const ID_MAGMA_CUBE = 42; //magma_cube
	const ID_BLAZE = 43; //blaze
	const ID_ZOMBIE_VILLAGER = 44; //zombie_villager
	const ID_WITCH = 45; //witch
	const ID_STRAY = 46; //stray
	const ID_HUSK = 47; //husk
	const ID_WITHER_SKELETON = 48; //wither_skeleton
	const ID_GUARDIAN = 49; //guardian
	const ID_ELDER_GUARDIAN = 50; //elder_guardian
	const ID_WITHER = 52; //wither
	const ID_ENDER_DRAGON = 53; //ender_dragon
	const ID_SHULKER = 54; //shulker
	const ID_ENDERMITE = 55; //endermite
	const ID_VINDICATOR = 57; //vindicator
	const ID_PHANTOM = 58; //phantom
	const ID_EVOKER = 104; //evocation_illager
	const ID_VEX = 105; //vex
	const ID_DROWNED = 110; //drowned

	/** Passive and neutral mobs */
	const ID_CHICKEN = 10; //chicken
	const ID_COW = 11; //cow
	const ID_PIG = 12; //pig
	const ID_SHEEP = 13; //sheep
	const ID_WOLF = 14; //wolf
	const ID_VILLAGER = 15; //villager
	const ID_MOOSHROOM = 16; //mooshroom
	const ID_SQUID = 17; //squid
	const ID_RABBIT = 18; //rabbit
	const ID_BAT = 19; //bat
	const ID_IRON_GOLEM = 20; //iron_golem
	const ID_SNOW_GOLEM = 21; //snow_golem
	const ID_OCELOT = 22; //ocelot
	const ID_HORSE = 23; //horse
	const ID_DONKEY = 24; //donkey
	const ID_MULE = 25; //mule
	const ID_SKELETON_HORSE = 26; //skeleton_horse
	const ID_ZOMBIE_HORSE = 27; //zombie_horse
	const ID_POLAR_BEAR = 28; //polar_bear
	const ID_LLAMA = 29; //llama
	const ID_PARROT = 30; //parrot
	const ID_DOLPHIN = 31; //dolphin
	const ID_TURTLE = 74; //turtle
	const ID_CAT = 75; //cat
	const ID_PUFFERFISH = 108; //pufferfish
	const ID_SALMON = 109; //salmon
	const ID_TROPICAL_FISH = 111; //tropicalfish
	const ID_COD = 112; //cod
	const ID_PANDA = 113; //panda

	/** Other */
	const ID_PLAYER = 63; //player
	
	/** Education Edition */
	const ID_NPC = 51; //npc
	const ID_AGENT = 56; //learn_to_code_mascot
	const ID_CAMERA = 62; //tripod_camera
	const ID_CHALKBOARD = 78; //chalkboard
	const ID_ICE_BOMB = 106; //ice_bomb
	const ID_BALOON = 107; //balloon
	
	private static $idToName = [
		self::ID_NONE => self::NAME_NONE,
		self::ID_ITEM => "minecraft:item",
		self::ID_EXP_ORB => "minecraft:xp_orb",
		self::ID_TNT => "minecraft:tnt",
		self::ID_FALLING_BLOCK => "minecraft:falling_block",
		self::ID_MOVING_BLOCK => "minecraft:moving_block",
		self::ID_ARMOR_STAND => "minecraft:armor_stand",
		self::ID_EXP_BOTTLE => "minecraft:xp_bottle",
		self::ID_EYE_OF_ENDER_SIGNAL => "minecraft:eye_of_ender_signal",
		self::ID_ENDER_CRYSTAL => "minecraft:ender_crystal",
		self::ID_FIREWORK_ROCKET => "minecraft:fireworks_rocket",
		self::ID_TRIDENT => "minecraft:thrown_trident",
		self::ID_SHULKER_BULLET => "minecraft:shulker_bullet",
		self::ID_FISHING_HOOK => "minecraft:fishing_hook",
		self::ID_DRAGON_FIREBALL => "minecraft:dragon_fireball",
		self::ID_ARROW => "minecraft:arrow",
		self::ID_SNOWBALL => "minecraft:snowball",
		self::ID_EGG => "minecraft:egg",
		self::ID_PAINTING => "minecraft:painting",
		self::ID_MINECART => "minecraft:minecart",
		self::ID_GHAST_FIREBALL => "minecraft:fireball",
		self::ID_SPLASH_POTION => "minecraft:splash_potion",
		self::ID_ENDER_PERL => "minecraft:ender_pearl",
		self::ID_LEASH_KNOT => "minecraft:leash_knot",
		self::ID_WITHER_SKULL => "minecraft:wither_skull",
		self::ID_BOAT => "minecraft:boat",
		self::ID_WITHER_SKULL_DANGEROUS => "minecraft:wither_skull_dangerous",
		self::ID_LIGHTNING_BOLT => "minecraft:lightning_bolt",
		self::ID_SMALL_FIREBALL => "minecraft:small_fireball",
		self::ID_AREA_EFFECT_CLOUD => "minecraft:area_effect_cloud",
		self::ID_MINECART_HOPPER => "minecraft:hopper_minecart",
		self::ID_MINECART_TNT => "minecraft:tnt_minecart",
		self::ID_MINECART_CHEST => "minecraft:chest_minecart",
		self::ID_MINECART_COMMAND_BLOCK => "minecraft:command_block_minecart",
		self::ID_LINGERING_POTION => "minecraft:lingering_potion",
		self::ID_LLAMA_SPLIT => "minecraft:llama_spit",
		self::ID_EVOCATION_FANGS => "minecraft:evocation_fang",
		self::ID_ZOMBIE => "minecraft:zombie",
		self::ID_CREEPER => "minecraft:creeper",
		self::ID_SKELETON => "minecraft:skeleton",
		self::ID_SPIDER => "minecraft:spider",
		self::ID_ZOMBIE_PIGMAN => "minecraft:zombie_pigman",
		self::ID_SLIME => "minecraft:slime",
		self::ID_ENDERMAN => "minecraft:enderman",
		self::ID_SILVERFISH => "minecraft:silverfish",
		self::ID_CAVE_SPIDER => "minecraft:cave_spider",
		self::ID_GHAST => "minecraft:ghast",
		self::ID_MAGMA_CUBE => "minecraft:magma_cube",
		self::ID_BLAZE => "minecraft:blaze",
		self::ID_ZOMBIE_VILLAGER => "minecraft:zombie_villager",
		self::ID_WITCH => "minecraft:witch",
		self::ID_STRAY => "minecraft:stray",
		self::ID_HUSK => "minecraft:husk",
		self::ID_WITHER_SKELETON => "minecraft:wither_skeleton",
		self::ID_GUARDIAN => "minecraft:guardian",
		self::ID_ELDER_GUARDIAN => "minecraft:elder_guardian",
		self::ID_WITHER => "minecraft:wither",
		self::ID_ENDER_DRAGON => "minecraft:ender_dragon",
		self::ID_SHULKER => "minecraft:shulker",
		self::ID_ENDERMITE => "minecraft:endermite",
		self::ID_VINDICATOR => "minecraft:vindicator",
		self::ID_PHANTOM => "minecraft:phantom",
		self::ID_EVOKER => "minecraft:evocation_illager",
		self::ID_VEX => "minecraft:vex",
		self::ID_DROWNED => "minecraft:drowned",
		self::ID_CHICKEN => "minecraft:chicken",
		self::ID_COW => "minecraft:cow",
		self::ID_PIG => "minecraft:pig",
		self::ID_SHEEP => "minecraft:sheep",
		self::ID_WOLF => "minecraft:wolf",
		self::ID_VILLAGER => "minecraft:villager",
		self::ID_MOOSHROOM => "minecraft:mooshroom",
		self::ID_SQUID => "minecraft:squid",
		self::ID_RABBIT => "minecraft:rabbit",
		self::ID_BAT => "minecraft:bat",
		self::ID_IRON_GOLEM => "minecraft:iron_golem",
		self::ID_SNOW_GOLEM => "minecraft:snow_golem",
		self::ID_OCELOT => "minecraft:ocelot",
		self::ID_HORSE => "minecraft:horse",
		self::ID_DONKEY => "minecraft:donkey",
		self::ID_MULE => "minecraft:mule",
		self::ID_SKELETON_HORSE => "minecraft:skeleton_horse",
		self::ID_ZOMBIE_HORSE => "minecraft:zombie_horse",
		self::ID_POLAR_BEAR => "minecraft:polar_bear",
		self::ID_LLAMA => "minecraft:llama",
		self::ID_PARROT => "minecraft:parrot",
		self::ID_DOLPHIN => "minecraft:dolphin",
		self::ID_TURTLE => "minecraft:turtle",
		self::ID_CAT => "minecraft:cat",
		self::ID_PUFFERFISH => "minecraft:pufferfish",
		self::ID_SALMON => "minecraft:salmon",
		self::ID_TROPICAL_FISH => "minecraft:tropicalfish",
		self::ID_COD => "minecraft:cod",
		self::ID_PANDA => "minecraft:panda",
		self::ID_PLAYER => "minecraft:player",
		self::ID_NPC => "minecraft:npc",
		self::ID_AGENT => "minecraft:learn_to_code_mascot",
		self::ID_CAMERA => "minecraft:tripod_camera",
		self::ID_CHALKBOARD => "minecraft:chalkboard",
		self::ID_ICE_BOMB => "minecraft:ice_bomb",
		self::ID_BALOON => "minecraft:balloon",
	];

	public static function getNameByID($id) {
		$id &= 0xff;
		if (isset(self::$idToName[$id])) {
			return self::$idToName[$id];
		}
		Server::getInstance()->getLogger()->warning("Unknown id {$id}");
		return self::$idToName[self::ID_NONE];
	}
	
	public static function getIDByName($name) {
		$entityID = array_search($name, self::$idToName);
		if ($entityID !== false) {
			return $entityID;
		}
		return self::ID_NONE;
	}
}

