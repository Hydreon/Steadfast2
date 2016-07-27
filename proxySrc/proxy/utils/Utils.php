<?php

namespace proxy\utils;

class Utils {

	public static $online = true;
	public static $ip = false;
	public static $os;
	private static $serverUniqueId = null;

	public static function getCallableIdentifier(callable $variable) {
		if (is_array($variable)) {
			return sha1(strtolower(spl_object_hash($variable[0])) . "::" . strtolower($variable[1]));
		} else {
			return sha1(strtolower($variable));
		}
	}

	public static function randomUUID() {
		return Utils::toUUID(Binary::writeInt(time()) . Binary::writeShort(getmypid()) . Binary::writeShort(getmyuid()) . Binary::writeInt(mt_rand(-0x7fffffff, 0x7fffffff)) . Binary::writeInt(mt_rand(-0x7fffffff, 0x7fffffff)), 2);
	}

	public static function dataToUUID(...$params) {
		return Utils::toUUID(hash("md5", implode($params), true), 3);
	}

	public static function toUUID($data, $version = 2, $fixed = "8") {
		if (strlen($data) !== 16) {
			throw new \InvalidArgumentException("Data must be 16 bytes");
		}

		$hex = bin2hex($data);
		return substr($hex, 0, 8) . "-" . substr($hex, 8, 4) . "-" . hexdec($version) . substr($hex, 13, 3) . "-" . $fixed{0} . substr($hex, 17, 3) . "-" . substr($hex, 20, 12);
	}

	public static function getOS($recalculate = false) {
		if (self::$os === null or $recalculate) {
			$uname = php_uname("s");
			if (stripos($uname, "Darwin") !== false) {
				if (strpos(php_uname("m"), "iP") === 0) {
					self::$os = "ios";
				} else {
					self::$os = "mac";
				}
			} elseif (stripos($uname, "Win") !== false or $uname === "Msys") {
				self::$os = "win";
			} elseif (stripos($uname, "Linux") !== false) {
				if (@file_exists("/system/build.prop")) {
					self::$os = "android";
				} else {
					self::$os = "linux";
				}
			} elseif (stripos($uname, "BSD") !== false or $uname === "DragonFly") {
				self::$os = "bsd";
			} else {
				self::$os = "other";
			}
		}

		return self::$os;
	}

	public static function getRealMemoryUsage() {
		$stack = 0;
		$heap = 0;

		if (Utils::getOS() === "linux" or Utils::getOS() === "android") {
			$mappings = file("/proc/self/maps");
			foreach ($mappings as $line) {
				if (preg_match("#([a-z0-9]+)\\-([a-z0-9]+) [rwxp\\-]{4} [a-z0-9]+ [^\\[]*\\[([a-zA-z0-9]+)\\]#", trim($line), $matches) > 0) {
					if (strpos($matches[3], "heap") === 0) {
						$heap += hexdec($matches[2]) - hexdec($matches[1]);
					} elseif (strpos($matches[3], "stack") === 0) {
						$stack += hexdec($matches[2]) - hexdec($matches[1]);
					}
				}
			}
		}

		return [$heap, $stack];
	}

	public static function getMemoryUsage($advanced = false) {
		$reserved = memory_get_usage();
		$VmSize = null;
		$VmRSS = null;
		if (Utils::getOS() === "linux" or Utils::getOS() === "android") {
			$status = file_get_contents("/proc/self/status");
			if (preg_match("/VmRSS:[ \t]+([0-9]+) kB/", $status, $matches) > 0) {
				$VmRSS = $matches[1] * 1024;
			}

			if (preg_match("/VmSize:[ \t]+([0-9]+) kB/", $status, $matches) > 0) {
				$VmSize = $matches[1] * 1024;
			}
		}



		if ($VmRSS === null) {
			$VmRSS = memory_get_usage();
		}

		if (!$advanced) {
			return $VmRSS;
		}

		if ($VmSize === null) {
			$VmSize = memory_get_usage(true);
		}

		return [$reserved, $VmRSS, $VmSize];
	}

	public static function getCoreCount($recalculate = false) {
		static $processors = 0;

		if ($processors > 0 and ! $recalculate) {
			return $processors;
		} else {
			$processors = 0;
		}

		switch (Utils::getOS()) {
			case "linux":
			case "android":
				if (file_exists("/proc/cpuinfo")) {
					foreach (file("/proc/cpuinfo") as $l) {
						if (preg_match('/^processor[ \t]*:[ \t]*[0-9]+$/m', $l) > 0) {
							++$processors;
						}
					}
				} else {
					if (preg_match("/^([0-9]+)\\-([0-9]+)$/", trim(@file_get_contents("/sys/devices/system/cpu/present")), $matches) > 0) {
						$processors = (int) ($matches[2] - $matches[1]);
					}
				}
				break;
			case "bsd":
			case "mac":
				$processors = (int) `sysctl -n hw.ncpu`;
				$processors = (int) `sysctl -n hw.ncpu`;
				break;
			case "win":
				$processors = (int) getenv("NUMBER_OF_PROCESSORS");
				break;
		}
		return $processors;
	}

	public static function hexdump($bin) {
		$output = "";
		$bin = str_split($bin, 16);
		foreach ($bin as $counter => $line) {
			$hex = chunk_split(chunk_split(str_pad(bin2hex($line), 32, " ", STR_PAD_RIGHT), 2, " "), 24, " ");
			$ascii = preg_replace('#([^\x20-\x7E])#', ".", $line);
			$output .= str_pad(dechex($counter << 4), 4, "0", STR_PAD_LEFT) . "  " . $hex . " " . $ascii . PHP_EOL;
		}

		return $output;
	}

	public static function printable($str) {
		if (!is_string($str)) {
			return gettype($str);
		}

		return preg_replace('#([^\x20-\x7E])#', '.', $str);
	}

	public static function getURL($page, $timeout = 10, array $extraHeaders = []) {
		if (Utils::$online === false) {
			return false;
		}

		$ch = curl_init($page);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array_merge(["User-Agent: Mozilla/5.0 (Windows NT 6.1; WOW64; rv:12.0) Gecko/20100101 Firefox/12.0 Proxy"], $extraHeaders));
		curl_setopt($ch, CURLOPT_AUTOREFERER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
		curl_setopt($ch, CURLOPT_FORBID_REUSE, 1);
		curl_setopt($ch, CURLOPT_FRESH_CONNECT, 1);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, (int) $timeout);
		curl_setopt($ch, CURLOPT_TIMEOUT, (int) $timeout);
		$ret = curl_exec($ch);
		curl_close($ch);

		return $ret;
	}

	public static function postURL($page, $args, $timeout = 10, array $extraHeaders = []) {
		if (Utils::$online === false) {
			return false;
		}

		$ch = curl_init($page);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
		curl_setopt($ch, CURLOPT_FORBID_REUSE, 1);
		curl_setopt($ch, CURLOPT_FRESH_CONNECT, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $args);
		curl_setopt($ch, CURLOPT_AUTOREFERER, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array_merge(["User-Agent: Mozilla/5.0 (Windows NT 6.1; WOW64; rv:12.0) Gecko/20100101 Firefox/12.0 Proxy"], $extraHeaders));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, (int) $timeout);
		curl_setopt($ch, CURLOPT_TIMEOUT, (int) $timeout);
		$ret = curl_exec($ch);
		curl_close($ch);

		return $ret;
	}

	public static function putURL($page, $args, $timeout = 10, array $extraHeaders = []) {
		if (Utils::$online === false) {
			return false;
		}

		$ch = curl_init($page);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
		curl_setopt($ch, CURLOPT_FORBID_REUSE, 1);
		curl_setopt($ch, CURLOPT_FRESH_CONNECT, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $args);
		curl_setopt($ch, CURLOPT_AUTOREFERER, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array_merge(["User-Agent: Mozilla/5.0 (Windows NT 6.1; WOW64; rv:12.0) Gecko/20100101 Firefox/12.0 Proxy"], $extraHeaders));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, (int) $timeout);
		curl_setopt($ch, CURLOPT_TIMEOUT, (int) $timeout);
		$ret = curl_exec($ch);
		curl_close($ch);

		return $ret;
	}

}
