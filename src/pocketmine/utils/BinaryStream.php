<?php

namespace pocketmine\utils;

use pocketmine\item\Item;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\Compound;
use pocketmine\nbt\tag\IntTag;
use pocketmine\network\protocol\Info;
use pocketmine\network\protocol\PEPacket;
use pocketmine\Player;

class BinaryStream {
	
	private $offset;
	private $buffer;

	protected $deviceId = Player::OS_UNKNOWN;

	private function writeErrorLog($depth = 3) {
		$depth = max($depth, 3);
		$backtrace = debug_backtrace(2, $depth);
		$result = __CLASS__ . "::" . __METHOD__ . " -> " . PHP_EOL;
		foreach ($backtrace as $k => $v) {
			$result .= "\t[line " . (isset($backtrace[$k]['line']) ? $backtrace[$k]['line'] : 'unknown line') . "] " . (isset($backtrace[$k]['class']) ? $backtrace[$k]['class'] : 'unknown class') . " -> " . (isset($backtrace[$k]['function']) ? $backtrace[$k]['function'] : 'unknown function') . PHP_EOL;
		}
		error_log($result);
	}

	public function __get($name) {
		$this->writeErrorLog();
		switch ($name) {
			case "buffer":
				return $this->buffer;
			case "offset":
				return $this->offset;
		}
	}

	public function __set($name, $value) {
		$this->writeErrorLog();
		switch ($name) {
			case "buffer":
				$this->buffer = $value;
				return;
			case "offset":
				$this->offset = $value;
				return;
		}
	}
	
	public function __construct($buffer = "", $offset = 0) {
		$this->setBuffer($buffer, $offset);
	}

	public function reset() {
		$this->setBuffer();
	}

	public function setBuffer($buffer = "", $offset = 0) {
		$this->buffer = $buffer;
		$this->offset = (int) $offset;
	}

	public function getBuffer(){
		return $this->buffer;
	}

	public function setOffset($offset) {
		$this->offset = $offset;
	}

	public function getOffset(){
		return $this->offset;
	}

	public function get($len) {
		if ($len < 0) {
			$this->offset = strlen($this->buffer) - 1;
			return "";
		} else if ($len === true) {
			return substr($this->buffer, $this->offset);
		}
		if (strlen($this->buffer) < $this->offset + $len) {
			throw new \Exception('binary stream get error');
		}
		return $len === 1 ? $this->buffer[$this->offset++] : substr($this->buffer, ($this->offset += $len) - $len, $len);
	}

	public function put($str) {
		$this->buffer .= $str;
	}

	public function getLong() {
		return Binary::readLong($this->get(8));
	}

	public function putLong($v) {
		$this->buffer .= Binary::writeLong($v);
	}

	public function getInt() {
		return Binary::readInt($this->get(4));
	}

	public function putInt($v) {
		$this->buffer .= Binary::writeInt($v);
	}

	public function getLLong() {
		return Binary::readLLong($this->get(8));
	}

	public function putLLong($v) {
		$this->buffer .= Binary::writeLLong($v);
	}

	public function getLInt() {
		return Binary::readLInt($this->get(4));
	}

	public function putLInt($v) {
		$this->buffer .= Binary::writeLInt($v);
	}

	public function getShort($signed = true) {
		return $signed ? Binary::readSignedShort($this->get(2)) : Binary::readShort($this->get(2));
	}

	public function putShort($v) {
		$this->buffer .= Binary::writeShort($v);
	}

	public function getFloat() {
		return Binary::readFloat($this->get(4));
	}

	public function putFloat($v) {
		$this->buffer .= Binary::writeFloat($v);
	}

	public function getLShort($signed = true) {
		return $signed ? Binary::readSignedLShort($this->get(2)) : Binary::readLShort($this->get(2));
	}

	public function putLShort($v) {
		$this->buffer .= Binary::writeLShort($v);
	}

	public function getLFloat() {
		return Binary::readLFloat($this->get(4));
	}

	public function putLFloat($v) {
		$this->buffer .= Binary::writeLFloat($v);
	}

	public function getTriad() {
		return Binary::readTriad($this->get(3));
	}

	public function putTriad($v) {
		$this->buffer .= Binary::writeTriad($v);
	}

	public function getLTriad() {
		return Binary::readLTriad($this->get(3));
	}

	public function putLTriad($v) {
		$this->buffer .= Binary::writeLTriad($v);
	}

	public function getByte() {
		if (strlen($this->buffer) < $this->offset + 1) {
			throw new \Exception('binary stream getByte error');
		}
		return ord($this->buffer[$this->offset++]);
	}

	public function putByte($v) {
		$this->buffer .= chr($v);
	}

	public function getDataArray($len = 10) {
		$data = [];
		for ($i = 1; $i <= $len and !$this->feof(); ++$i) {
			$data[] = $this->get($this->getTriad());
		}
		return $data;
	}

	public function putDataArray(array $data = []) {
		foreach ($data as $v) {
			$this->putTriad(strlen($v));
			$this->put($v);
		}
	}

	public function getUUID() {
		$part1 = $this->getLInt();
		$part0 = $this->getLInt();
		$part3 = $this->getLInt();
		$part2 = $this->getLInt();
		return new UUID($part0, $part1, $part2, $part3);
	}

	public function putUUID(UUID $uuid) {
		$this->putLInt($uuid->getPart(1));
		$this->putLInt($uuid->getPart(0));
		$this->putLInt($uuid->getPart(3));
		$this->putLInt($uuid->getPart(2));
	}

	public function getSlotWithoutStackId($playerProtocol) {
		return $this->getSlot($playerProtocol, false);
	}

	public function putSlotWithoutStackId($item, $playerProtocol) {
		return $this->putSlot($item, $playerProtocol, false);
	}

	public function getSlot($playerProtocol, $withStackId = true) {
		$id = $this->getSignedVarInt();
		if ($id == 0) {
			return Item::get(Item::AIR, 0, 0);
		}
		
		$count = $this->getLShort();
		$meta = $this->getVarInt();

		if ($withStackId) {
			$includeNetId = $this->getByte();
			if ($includeNetId) {
				$this->getSignedVarInt();
			}
		}

		$blockRuntimeId = $this->getSignedVarInt();

		$buffer = new BinaryStream($this->getString());	
		$nbtLen = $buffer->getLShort(false);
		$nbt = "";
		if($nbtLen === 0xffff) {
			$nbtDataVersion = $buffer->getByte();
			$nbtTag = new NBT(NBT::LITTLE_ENDIAN);
			$offset = $buffer->getOffset();
			if ($offset > strlen($this->getBuffer())) {
				throw new \Exception('get slot nbt error');
			}
			//need cyrcle for???
			$nbtTag->read(substr($buffer->getBuffer(), $offset), false, false);
			$nbt = $nbtTag->getData();
			$buffer->setOffset($offset + $nbtTag->getOffset());

			if(isset($nbt->___Meta___) && $nbt->___Meta___ instanceof IntTag){
				//TODO HACK: This foul-smelling code ensures that we can correctly deserialize an item when the
				//client sends it back to us, because as of 1.16.220, blockitems quietly discard their metadata
				//client-side. Aside from being very annoying, this also breaks various server-side behaviours.
				$meta = $nbt->___Meta___->getValue();
				unset($nbt->___Meta___);
			}
		}elseif($nbtLen !== 0){
			throw new \Exception("Unexpected fake NBT length $nbtLen");
		}
		
		$item = Item::get($id, $meta, $count, $nbt);
		for($i = 0, $canPlaceOnCount = $buffer->getLInt(); $i < $canPlaceOnCount; ++$i){
			$item->addCanPlaceOnBlocks($buffer->get($buffer->getLShort()));
		}

		$canDestroy = [];
		for($i = 0, $canDestroyCount = $buffer->getLInt(); $i < $canDestroyCount; ++$i){
			$item->addCanDestroyBlocks($buffer->get($buffer->getLShort()));
		}
		return $item;
	}

	public function putSlot(Item $item, $playerProtocol, $withStackId = true) {
		if ($item->getId() === 0) {
			$this->putSignedVarInt(0);
			return;
		}
		$this->putSignedVarInt($item->getId());
		$this->putLShort($item->getCount());
		
		if(is_null($item->getDamage())) $item->setDamage(0);
		$this->putVarInt($item->getDamage());
		if ($withStackId) {
			if($item->getId() === 0){
				$this->putBool(false);
			}else{
				$this->putBool(true);
				$this->putSignedVarInt(1);
			}
		}
       
		$this->putSignedVarInt(PEPacket::getBlockRuntimeID($item->getId(), $item->getDamage(), $playerProtocol));
		
		$this->putString((static function() use ($item) {
			$buffer = new BinaryStream();
			$nbt = $item->getNamedTag();
			if($item->getDamage() !== 0){
				//TODO HACK: This foul-smelling code ensures that we can correctly deserialize an item when the
				//client sends it back to us, because as of 1.16.220, blockitems quietly discard their metadata
				//client-side. Aside from being very annoying, this also breaks various server-side behaviours.
				if($nbt === null){
					$nbt = new Compound();
				}
				$nbt->___Meta___ = new IntTag("___Meta___", $item->getDamage());
			}
			if ($nbt !== null) {
				$buffer->putLShort(0xffff);
				$buffer->putByte(1);
				$nbtWriter = new NBT(NBT::LITTLE_ENDIAN);
				$nbtWriter->setData($nbt);
				$buffer->put($nbtWriter->write(true));

				//steadfast doesn't support deep-cloning of CompoundTags, so this might be the item's actual cachedNBT
				unset($nbt->___Meta___);
			}else {
				$buffer->putLShort(0);
			}

			$canPlaceOnBlocks = $item->getCanPlaceOnBlocks();
			$canDestroyBlocks = $item->getCanDestroyBlocks();
			$buffer->putLInt(count($canPlaceOnBlocks));
			foreach ($canPlaceOnBlocks as $blockName) {
				$buffer->putLShort(strlen($blockName));
				$buffer->put($blockName);
			}
			$buffer->putLInt(count($canDestroyBlocks));
			foreach ($canDestroyBlocks as $blockName) {
				$buffer->putLShort(strlen($blockName));
				$buffer->put($blockName);
			}
			if($item->getId() === Item::SHIELD){
				$buffer->putLLong(0);
			}
			return $buffer->getBuffer();
		})());

        
		
		
	}

	public function feof() {
		return !isset($this->buffer[$this->offset]);
	}
	
	
	public function getSignedVarInt() {
		$result = $this->getVarInt();
		if ($result % 2 == 0) {
			$result = $result / 2;
		} else {
			$result = (-1) * ($result + 1) / 2;
		}
		return $result;
	}

	public function getVarInt() {
		$result = $shift = 0;
		do {
			$byte = $this->getByte();
			$result |= ($byte & 0x7f) << $shift;
			$shift += 7;
		} while ($byte > 0x7f);
		return $result;
	}

	public function putSignedVarInt($v) {
		$this->put(Binary::writeSignedVarInt($v));
	}

	public function putVarInt($v) {
		$this->put(Binary::writeVarInt($v));
	}
	
	public function putBool($v) {
		$this->put(Binary::writeBool($v));
	}

	public function getString(){
		return $this->get($this->getVarInt());
	}

	public function putString($v){
		$this->putVarInt(strlen($v));
		$this->put($v);
	}
	

	public function putSerializedSkin($playerProtocol, $skinId, $skinData, $skinGeometryName, $skinGeometryData, $capeData, $additionalSkinData) {
		if ($this->deviceId == Player::OS_NX || !isset($additionalSkinData['PersonaSkin']) || !$additionalSkinData['PersonaSkin']) {
			$additionalSkinData = [];
		}
		if (isset($additionalSkinData['skinData'])) {
			$skinData = $additionalSkinData['skinData'];
		}
		if (isset($additionalSkinData['skinGeometryName'])) {
			$skinGeometryName = $additionalSkinData['skinGeometryName'];
		}
		if (isset($additionalSkinData['skinGeometryData'])) {
			$skinGeometryData = $additionalSkinData['skinGeometryData'];
		}		
		if (empty($skinGeometryName)) {
			$skinGeometryName = "geometry.humanoid.custom";
		}
		$this->putString($skinId);
		if ($playerProtocol >= Info::PROTOCOL_428) {
			$this->putString($additionalSkinData['PlayFabId']??'');
		}
		$this->putString(isset($additionalSkinData['SkinResourcePatch']) ? $additionalSkinData['SkinResourcePatch'] : '{"geometry" : {"default" : "' . $skinGeometryName . '"}}');
		if (isset($additionalSkinData['SkinImageHeight']) && isset($additionalSkinData['SkinImageWidth'])) {
			$width = $additionalSkinData['SkinImageWidth'];
			$height = $additionalSkinData['SkinImageHeight'];
		} else {
			$width = 64;
			$height = strlen($skinData) >> 8;
			while ($height > $width) {
				$width <<= 1;
				$height >>= 1;
			}
		}
		$this->putLInt($width);
		$this->putLInt($height);
		$this->putString($skinData);

		if (isset($additionalSkinData['AnimatedImageData'])) {
			$this->putLInt(count($additionalSkinData['AnimatedImageData']));
			foreach ($additionalSkinData['AnimatedImageData'] as $animation) {
				$this->putLInt($animation['ImageWidth']);
				$this->putLInt($animation['ImageHeight']);
				$this->putString($animation['Image']);
				$this->putLInt($animation['Type']);
				$this->putLFloat($animation['Frames']);
				if ($playerProtocol >= Info::PROTOCOL_419) {
					$this->putLInt($animation['AnimationExpression']??0);
				}
			}
		} else {
			$this->putLInt(0);
		}
			
		if (empty($capeData)) {
			$this->putLInt(0);
			$this->putLInt(0);
			$this->putString('');
		} else {
			if (isset($additionalSkinData['CapeImageWidth']) && isset($additionalSkinData['CapeImageHeight'])) {
				$width = $additionalSkinData['CapeImageWidth'];
				$height = $additionalSkinData['CapeImageHeight'];
			} else {
				$width = 1;
				$height = strlen($capeData) >> 2;
				while ($height > $width) {
					$width <<= 1;
					$height >>= 1;
				}
			}
			$this->putLInt($width);
			$this->putLInt($height);
			$this->putString($capeData);
		}

		$this->putString($skinGeometryData); // Skin Geometry Data
		$this->putString(isset($additionalSkinData['SkinAnimationData']) ? $additionalSkinData['SkinAnimationData'] : ''); // Serialized Animation Data

		$this->putByte(isset($additionalSkinData['PremiumSkin']) ? $additionalSkinData['PremiumSkin'] : 0); // Is Premium Skin 
		$this->putByte(isset($additionalSkinData['PersonaSkin']) ? $additionalSkinData['PersonaSkin'] : 0); // Is Persona Skin 
		$this->putByte(isset($additionalSkinData['CapeOnClassicSkin']) ? $additionalSkinData['CapeOnClassicSkin'] : 0); // Is Persona Cape on Classic Skin 

		$this->putString(isset($additionalSkinData['CapeId']) ? $additionalSkinData['CapeId'] : '');
		if (isset($additionalSkinData['FullSkinId'])) {
			$this->putString($additionalSkinData['FullSkinId']); // Full Skin ID	
		} else {
			$uniqId = $skinId . $skinGeometryName . "-" . microtime(true);
			$this->putString($uniqId); // Full Skin ID	
		}
		$this->putString($additionalSkinData['ArmSize']??''); //ArmSize
		$this->putString($additionalSkinData['SkinColor']??''); //SkinColor
		$this->putLInt(isset($additionalSkinData['PersonaPieces'])?count($additionalSkinData['PersonaPieces']):0);   //Persona Pieces -> more info to come
		foreach ($additionalSkinData['PersonaPieces']??[] as $piece) {
			$this->putString($piece['PieceId']);
			$this->putString($piece['PieceType']);
			$this->putString($piece['PackId']);
			$this->putBool($piece['IsDefaultPiece']);
			$this->putString($piece['ProductId']);
		}
		$this->putLInt(isset($additionalSkinData['PieceTintColors'])?count($additionalSkinData['PieceTintColors']):0); //PieceTintColors -> more info to come
		foreach ($additionalSkinData['PieceTintColors']??[] as $tint) {
			$this->putString($tint['PieceType']);
			$this->putLInt(count($tint['Colors']));
			foreach($tint['Colors'] as $color){
				$this->putString($color);
			}
		}
	}

	public function getSerializedSkin($playerProtocol, &$skinId, &$skinData, &$skinGeometryName, &$skinGeometryData, &$capeData, &$additionalSkinData) {
		$skinId = $this->getString();
		if ($playerProtocol >= Info::PROTOCOL_428) {
			$additionalSkinData['PlayFabId'] = $this->getString();
		}
		$additionalSkinData['SkinResourcePatch'] = $this->getString();
		$geometryData = json_decode($additionalSkinData['SkinResourcePatch'], true);
		$skinGeometryName = isset($geometryData['geometry']['default']) ? $geometryData['geometry']['default'] : '';
		
		$additionalSkinData['SkinImageWidth'] = $this->getLInt();
		$additionalSkinData['SkinImageHeight'] = $this->getLInt();
		$skinData = $this->getString();

		$animationCount = $this->getLInt();
		$additionalSkinData['AnimatedImageData'] = [];
		for ($i = 0; $i < $animationCount; $i++) {
			$additionalSkinData['AnimatedImageData'][] = [
				'ImageWidth' => $this->getLInt(),
				'ImageHeight' => $this->getLInt(),
				'Image' => $this->getString(),
				'Type' => $this->getLInt(),
				'Frames' => $this->getLFloat(),
				'AnimationExpression' => ($playerProtocol >= Info::PROTOCOL_419)?$this->getLInt():0
			];
		}

		$additionalSkinData['CapeImageWidth'] = $this->getLInt();
		$additionalSkinData['CapeImageHeight'] = $this->getLInt();
		$capeData = $this->getString();
		
		$skinGeometryData = $this->getString();
		if (strpos($skinGeometryData, 'null') === 0) {
			$skinGeometryData = '';
		}
		$additionalSkinData['SkinAnimationData'] = $this->getString();

		$additionalSkinData['PremiumSkin'] = $this->getByte();
		$additionalSkinData['PersonaSkin'] = $this->getByte();
		$additionalSkinData['CapeOnClassicSkin'] = $this->getByte();
		
		$additionalSkinData['CapeId'] = $this->getString();
		$additionalSkinData['FullSkinId'] = $this->getString(); // Full Skin ID

		$additionalSkinData['ArmSize'] = $this->getString();
		$additionalSkinData['SkinColor'] = $this->getString();
		$personaPieceCount = $this->getLInt();
		$personaPieces = [];
		for($i = 0; $i < $personaPieceCount; ++$i){
			$personaPieces[] = [
				'PieceId' => $this->getString(),
				'PieceType' => $this->getString(),
				'PackId' => $this->getString(),
				'IsDefaultPiece' => $this->getByte(),
				'ProductId' => $this->getString()
			];
		}
		$additionalSkinData['PersonaPieces'] = $personaPieces;
		$pieceTintColorCount = $this->getLInt();
		$pieceTintColors = [];
		for($i = 0; $i < $pieceTintColorCount; ++$i){
			$pieceType = $this->getString();
			$colorCount = $this->getLInt();
			$colors = [];
			for($j = 0; $j < $colorCount; ++$j){
				$colors[] = $this->getString();
			}
			$pieceTintColors[] = [
				'PieceType' => $pieceType,
				'Colors' => $colors
			];
		}
		$additionalSkinData['PieceTintColors'] = $pieceTintColors;
	}

	public function checkSkinData(&$skinData, &$skinGeometryName, &$skinGeometryData, &$additionalSkinData) {
		if (empty($skinGeometryName) && !empty($additionalSkinData['SkinResourcePatch'])) {
			if (($jsonSkinResourcePatch = @json_decode($additionalSkinData['SkinResourcePatch'], true)) && isset($jsonSkinResourcePatch['geometry']['default'])) {
				$skinGeometryName = $jsonSkinResourcePatch['geometry']['default'];
			}
		} 
		if (!empty($skinGeometryName) && stripos($skinGeometryName, 'geometry.') !== 0) {
			if (!empty($skinGeometryData) && ($jsonSkinData = @json_decode($skinGeometryData, true))) {
				foreach ($jsonSkinData as $key => $value) {
					if ($key == $skinGeometryName) {
						unset($jsonSkinData[$key]);
						$jsonSkinData['geometry.' . $key] = $value;
						$skinGeometryName = 'geometry.' . $key;
						$skinGeometryData = json_encode($jsonSkinData);
						if (!empty($additionalSkinData['SkinResourcePatch']) && ($jsonSkinResourcePatch = @json_decode($additionalSkinData['SkinResourcePatch'], true)) && !empty($jsonSkinResourcePatch['geometry'])) {
							foreach ($jsonSkinResourcePatch['geometry'] as &$geometryName) {
								if ($geometryName == $key) {
									$geometryName = $skinGeometryName;
									$additionalSkinData['SkinResourcePatch'] = json_encode($jsonSkinResourcePatch);
									break;
								}
							}
						}						
						break;
					}
				}
			}
		}
		if (isset($additionalSkinData['PersonaSkin']) && $additionalSkinData['PersonaSkin']) {
			static $defaultSkins = [];
			if (empty($defaultSkins)) {
				$defaultSkins[] = [file_get_contents(__DIR__ . "/defaultSkins/Alex.dat"), 'geometry.humanoid.customSlim'];
				$defaultSkins[] = [file_get_contents(__DIR__ . "/defaultSkins/Steve.dat"), 'geometry.humanoid.custom'];
			}
			$additionalSkinData['skinData'] = $skinData;
			$additionalSkinData['skinGeometryName'] = $skinGeometryName;
			$additionalSkinData['skinGeometryData'] = $skinGeometryData;
			$randomSkinData =  $defaultSkins[array_rand($defaultSkins)];
			$skinData = $randomSkinData[0];
			$skinGeometryData = '';
			$skinGeometryName = $randomSkinData[1];
			$additionalSkinData = [];
		} elseif (in_array($skinGeometryName, ['geometry.humanoid.customSlim', 'geometry.humanoid.custom'])) {
			$skinGeometryData = '';
			$additionalSkinData = [];
		}
	}
	
	public function prepareGeometryDataForOld($skinGeometryData) {
		if (!empty($skinGeometryData)) {
			if (($tempData = @json_decode($skinGeometryData, true))) {
				unset($tempData["format_version"]);
				return json_encode($tempData);
			}
		}
		return $skinGeometryData;
	}

	public function setDeviceId($deviceId) {
		$this->deviceId = $deviceId;
	}

	public function getDeviceId($deviceId) {
		return $this->deviceId;
	}

	public function getEntityUniqueId() {
		return $this->getSignedVarInt();
	}

	public function putEntityUniqueId($id) {
		$this->putSignedVarInt($id);
	}

	public function getEntityRuntimeId() {
		return $this->getVarInt();
	}

	public function putEntityRuntimeId($id) {
		$this->putVarInt($id);
	}
}
