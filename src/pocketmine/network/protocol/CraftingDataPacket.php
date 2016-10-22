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

namespace pocketmine\network\protocol;

#include <rules/DataPacket.h>


use pocketmine\inventory\FurnaceRecipe;
use pocketmine\inventory\ShapedRecipe;
use pocketmine\inventory\ShapelessRecipe;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\enchantment\EnchantmentList;
use pocketmine\utils\BinaryStream;

class CraftingDataPacket extends DataPacket{
	const NETWORK_ID = Info::CRAFTING_DATA_PACKET;

	const ENTRY_SHAPELESS = 0;
	const ENTRY_SHAPED = 1;
	const ENTRY_FURNACE = 2;
	const ENTRY_FURNACE_DATA = 3;
	const ENTRY_ENCHANT_LIST = 4;

	/** @var object[] */
	public $entries = [];
	public $cleanRecipes = false;

	private static function writeEntry($entry, BinaryStream $stream){
		if($entry instanceof ShapelessRecipe){
			return self::writeShapelessRecipe($entry, $stream);
		}elseif($entry instanceof ShapedRecipe){
			return self::writeShapedRecipe($entry, $stream);
		}elseif($entry instanceof FurnaceRecipe){
			return self::writeFurnaceRecipe($entry, $stream);
		}elseif($entry instanceof EnchantmentList){
			return self::writeEnchantList($entry, $stream);
		}

		return -1;
	}

	private static function writeShapelessRecipe(ShapelessRecipe $recipe, BinaryStream $stream){
		$stream->putVarInt($recipe->getIngredientCount());
		foreach($recipe->getIngredientList() as $item){
			$stream->putSlot($item);
		}

		$stream->putVarInt(1);
		$stream->putSlot($recipe->getResult());

		$stream->putUUID($recipe->getId());

		return CraftingDataPacket::ENTRY_SHAPELESS;
	}

	private static function writeShapedRecipe(ShapedRecipe $recipe, BinaryStream $stream){
		$stream->putSignedVarInt($recipe->getWidth());
		$stream->putSignedVarInt($recipe->getHeight());
		for($z = 0; $z < $recipe->getWidth(); ++$z){
			for($x = 0; $x < $recipe->getHeight(); ++$x){
				$stream->putSlot($recipe->getIngredient($x, $z));
			}
		}

		$stream->putVarInt(1);
		$stream->putSlot($recipe->getResult());

		$stream->putUUID($recipe->getId());

		return CraftingDataPacket::ENTRY_SHAPED;
	}

	private static function writeFurnaceRecipe(FurnaceRecipe $recipe, BinaryStream $stream){		
		if($recipe->getInput()->getDamage() !== 0){ //Data recipe
			$stream->putSignedVarInt($recipe->getInput()->getDamage());
			$stream->putSignedVarInt($recipe->getInput()->getId());			
			$stream->putSlot($recipe->getResult());
			return CraftingDataPacket::ENTRY_FURNACE_DATA;
		}else{
			$stream->putSignedVarInt($recipe->getInput()->getId());
			$stream->putSlot($recipe->getResult());
			return CraftingDataPacket::ENTRY_FURNACE;
		}
	}

	private static function writeEnchantList(EnchantmentList $list, BinaryStream $stream){
		return -1; //TODO
//		$stream->putByte($list->getSize());
//		for($i = 0; $i < $list->getSize(); ++$i){
//			$entry = $list->getSlot($i);
//			$stream->putSignedVarInt($entry->getCost());
//			$stream->putByte(count($entry->getEnchantments()));
//			foreach($entry->getEnchantments() as $enchantment){
//				$stream->putSignedVarInt($enchantment->getId());
//				$stream->putSignedVarInt($enchantment->getLevel());
//			}
//			$stream->putString($entry->getRandomName());
//		}
//
//		return CraftingDataPacket::ENTRY_ENCHANT_LIST;
	}

	public function addShapelessRecipe(ShapelessRecipe $recipe){
		$this->entries[] = $recipe;
	}

	public function addShapedRecipe(ShapedRecipe $recipe){
		$this->entries[] = $recipe;
	}

	public function addFurnaceRecipe(FurnaceRecipe $recipe){
		$this->entries[] = $recipe;
	}

	public function addEnchantList(EnchantmentList $list){
		$this->entries[] = $list;
	}

	public function clean(){
		$this->entries = [];
		return parent::clean();
	}

	public function decode(){

	}

	public function encode(){
		$this->reset();
		$this->putVarInt(count($this->entries));

		$writer = new BinaryStream();
		foreach($this->entries as $d){
			$entryType = self::writeEntry($d, $writer);
			if($entryType >= 0){
				$this->putSignedVarInt($entryType);
				$this->put($writer->getBuffer());
			}else{
				$this->putSignedVarInt(-1);
			}

			$writer->reset();
		}

		$this->putByte($this->cleanRecipes ? 1 : 0);
	}

}