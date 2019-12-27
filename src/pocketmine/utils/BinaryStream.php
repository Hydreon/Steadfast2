<?php

namespace pocketmine\utils;

use pocketmine\item\Item;
use pocketmine\nbt\NBT;
use pocketmine\network\protocol\Info;

class BinaryStream {
	
	private $offset;
	private $buffer;

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
		return $len === 1 ? $this->buffer{$this->offset++} : substr($this->buffer, ($this->offset += $len) - $len, $len);
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
		return ord($this->buffer{$this->offset++});
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

	public function getSlot($playerProtocol) {
		$id = $this->getSignedVarInt();
		if ($id == 0) {
			return Item::get(Item::AIR, 0, 0);
		}
		
		$aux = $this->getSignedVarInt();
		$meta = $aux >> 8;
		$count = $aux & 0xff;
		
		$nbtLen = $this->getLShort();		
		$nbt = "";	
		if ($nbtLen > 0) {
			$nbt = $this->get($nbtLen);
		} elseif($nbtLen == -1) {
			$nbtCount = $this->getVarInt();
			if ($nbtCount > 100) {
				throw new \Exception('get slot nbt error, too many count');
			}
			for ($i = 0; $i < $nbtCount; $i++) {
				$nbtTag = new NBT(NBT::LITTLE_ENDIAN);
				$offset = $this->getOffset();
				if ($offset > strlen($this->getBuffer())) {
					throw new \Exception('get slot nbt error');
				}
				$nbtTag->read(substr($this->getBuffer(), $offset), false, true);
				$nbt = $nbtTag->getData();
				$this->setOffset($offset + $nbtTag->getOffset());
			}
		}
		$item = Item::get($id, $meta, $count, $nbt);
		$canPlaceOnBlocksCount = $this->getSignedVarInt();
		for ($i = 0; $i < $canPlaceOnBlocksCount; $i++) {
			$item->addCanPlaceOnBlocks($this->getString());
		}
		$canDestroyBlocksCount = $this->getSignedVarInt();
		for ($i = 0; $i < $canDestroyBlocksCount; $i++) {
			$item->addCanDestroyBlocks($this->getString());
		}
		return $item;
	}

	public function putSlot(Item $item, $playerProtocol) {
		if ($item->getId() === 0) {
			$this->putSignedVarInt(0);
			return;
		}
		$this->putSignedVarInt($item->getId());
		$this->putSignedVarInt(($item->getDamage() === null ? 0  : ($item->getDamage() << 8)) + $item->getCount());	
		$nbt = $item->getCompound();	
		$this->putLShort(strlen($nbt));
		$this->put($nbt);
		$canPlaceOnBlocks = $item->getCanPlaceOnBlocks();
		$canDestroyBlocks = $item->getCanDestroyBlocks();
		$this->putSignedVarInt(count($canPlaceOnBlocks));
		foreach ($canPlaceOnBlocks as $blockName) {
			$this->putString($blockName);
		}
		$this->putSignedVarInt(count($canDestroyBlocks));
		foreach ($canDestroyBlocks as $blockName) {
			$this->putString($blockName);
		}
	}

	public function feof() {
		return !isset($this->buffer{$this->offset});
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
	
	public function putSerializedSkin($playerProtocol, $skinId, $skinData, $skinGeomtryName, $skinGeomtryData, $capeData, $additionalSkinData) {
		if (!isset($additionalSkinData['PersonaSkin']) || !$additionalSkinData['PersonaSkin']) {
			$additionalSkinData = [];
		}
		if (isset($additionalSkinData['skinData'])) {
			$skinData = $additionalSkinData['skinData'];
		}
		if (isset($additionalSkinData['skinGeomtryName'])) {
			$skinGeomtryName = $additionalSkinData['skinGeomtryName'];
		}
		if (isset($additionalSkinData['skinGeomtryData'])) {
			$skinGeomtryData = $additionalSkinData['skinGeomtryData'];
		}		
		if (empty($skinGeomtryName)) {
			$skinGeomtryName = "geometry.humanoid.custom";
		}
		$this->putString($skinId);
		$this->putString(isset($additionalSkinData['SkinResourcePatch']) ? $additionalSkinData['SkinResourcePatch'] : '{"geometry" : {"default" : "' . $skinGeomtryName . '"}}');
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

		$this->putString($skinGeomtryData); // Skin Geometry Data
		$this->putString(isset($additionalSkinData['SkinAnimationData']) ? $additionalSkinData['SkinAnimationData'] : ''); // Serialized Animation Data

		$this->putByte(isset($additionalSkinData['PremiumSkin']) ? $additionalSkinData['PremiumSkin'] : 0); // Is Premium Skin 
		$this->putByte(isset($additionalSkinData['PersonaSkin']) ? $additionalSkinData['PersonaSkin'] : 0); // Is Persona Skin 
		$this->putByte(isset($additionalSkinData['CapeOnClassicSkin']) ? $additionalSkinData['CapeOnClassicSkin'] : 0); // Is Persona Cape on Classic Skin 
		
		$this->putString(isset($additionalSkinData['CapeId']) ? $additionalSkinData['CapeId'] : '');
		$uniqId = $skinId . $skinGeomtryName . "-" . microtime(true);
		$this->putString($uniqId); // Full Skin ID		
	}

	public function getSerializedSkin($playerProtocol, &$skinId, &$skinData, &$skinGeomtryName, &$skinGeomtryData, &$capeData, &$additionalSkinData) {		
		$skinId = $this->getString();
		$additionalSkinData['SkinResourcePatch'] = $this->getString();
		$geometryData = json_decode($additionalSkinData['SkinResourcePatch'], true);
		$skinGeomtryName = isset($geometryData['geometry']['default']) ? $geometryData['geometry']['default'] : '';
		
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
			];
		}
		
		$additionalSkinData['CapeImageWidth'] = $this->getLInt();
		$additionalSkinData['CapeImageHeight'] = $this->getLInt();
		$capeData = $this->getString();
		
		$skinGeomtryData = $this->getString();
		if (strpos($skinGeomtryData, 'null') === 0) {
			$skinGeomtryData = '';
		}
		$additionalSkinData['SkinAnimationData'] = $this->getString();

		$additionalSkinData['PremiumSkin'] = $this->getByte();
		$additionalSkinData['PersonaSkin'] = $this->getByte();
		$additionalSkinData['CapeOnClassicSkin'] = $this->getByte();
		
		$additionalSkinData['CapeId'] = $this->getString();
		$this->getString(); // Full Skin ID	
	}

	public function checkSkinData(&$skinData, &$skinGeomtryName, &$skinGeomtryData, &$additionalSkinData) {
		if (empty($skinGeomtryName) && !empty($additionalSkinData['SkinResourcePatch'])) {
			if (($jsonSkinResourcePatch = @json_decode($additionalSkinData['SkinResourcePatch'], true)) && isset($jsonSkinResourcePatch['geometry']['default'])) {
				$skinGeomtryName = $jsonSkinResourcePatch['geometry']['default'];
			}
		} 
		if (!empty($skinGeomtryName) && stripos($skinGeomtryName, 'geometry.') !== 0) {
			if (!empty($skinGeomtryData) && ($jsonSkinData = @json_decode($skinGeomtryData, true))) {
				foreach ($jsonSkinData as $key => $value) {
					if ($key == $skinGeomtryName) {
						unset($jsonSkinData[$key]);
						$jsonSkinData['geometry.' . $key] = $value;
						$skinGeomtryName = 'geometry.' . $key;
						$skinGeomtryData = json_encode($jsonSkinData);
						if (!empty($additionalSkinData['SkinResourcePatch']) && ($jsonSkinResourcePatch = @json_decode($additionalSkinData['SkinResourcePatch'], true)) && !empty($jsonSkinResourcePatch['geometry'])) {
							foreach ($jsonSkinResourcePatch['geometry'] as &$geometryName) {
								if ($geometryName == $key) {
									$geometryName = $skinGeomtryName;
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
			$additionalSkinData['skinGeomtryName'] = $skinGeomtryName;
			$additionalSkinData['skinGeomtryData'] = $skinGeomtryData;
			$randomSkinData =  $defaultSkins[array_rand($defaultSkins)];
			$skinData = $randomSkinData[0];
			$skinGeomtryData = '';
			$skinGeomtryName = $randomSkinData[1];
		} elseif (in_array($skinGeomtryName, ['geometry.humanoid.customSlim', 'geometry.humanoid.custom'])) {
			$skinGeomtryData = '';
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
	
}
