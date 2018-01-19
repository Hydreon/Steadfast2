<?php

namespace pocketmine\mods;

class ResourcePack {
	public $id = "";
	public $version = "";
	public $size = 0;
	public $contentKey = "";
	public $subPackName = "";
	public $hash = 0;
	private $zippedData = "";
	
	public function __construct($path, $modName) {
		$manifestData = "";
		$zipFileHandler = zip_open($path);
		while (($zipEntry = zip_read($zipFileHandler)) !== false) {
			if (zip_entry_name($zipEntry) == "manifest.json") {
				if (zip_entry_open($zipFileHandler, $zipEntry)) {
					$manifestData = zip_entry_read($zipEntry, 8192);
					zip_entry_close($zipEntry);
					break;
				}
				zip_close($zipFileHandler);
				throw new \Exception("Error during manifest reading");
			}
		}
		zip_close($zipFileHandler);
		
		if ($manifestData) {
			$manifest = json_decode($manifestData, true);
			if ($manifest && self::isManifestValid($manifest)) {
				$this->id = $manifest["header"]["uuid"];
				$this->version = implode(".", $manifest["header"]["version"]);
				$this->size = filesize($path);
				$this->hash = hash_file("sha256", $path, true);
				$this->zippedData = file_get_contents($path);
			} else {
				throw new \Exception("Wrong resource pack manifest file");
			}
		} else {
			throw new \Exception("Wrong resource pack file");
		}
	}
	
	private static function isManifestValid($manifest) {
		return isset($manifest["header"]) && isset($manifest["header"]["uuid"]) && isset($manifest["header"]["version"]);
	}
	
	public function readChunk($index, $chunkSize) {
		if ($index * $chunkSize > strlen($this->zippedData)) {
			return "";
		}
		return substr($this->zippedData, $index * $chunkSize, $chunkSize);
	}
}
