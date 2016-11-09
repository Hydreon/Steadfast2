<?php

@define("proxy\\PATH", getcwd() . DIRECTORY_SEPARATOR);

$pharPath = "./proxy.phar";
if (file_exists($pharPath)) {
	echo "Phar file already exists, overwriting...\n";
	@unlink($pharPath);
}
$phar = new \Phar($pharPath);
$phar->setMetadata([
	"name" => 'LifeBoatProxy',
	"version" => '1.0',
	"api" => '1.0',
	"protocol" => 81,
	"creationDate" => time()
]);
$phar->setStub('<?php define("proxy\\\\PATH", "phar://". __FILE__ ."/"); require_once("phar://". __FILE__ ."/proxySrc/proxy/Proxy.php");  __HALT_COMPILER();');
$phar->setSignatureAlgorithm(\Phar::SHA1);
$phar->startBuffering();

$filePath = substr(\proxy\PATH, 0, 7) === "phar://" ? \proxy\PATH : realpath(\proxy\PATH) . "/";
$filePath = rtrim(str_replace("\\", "/", $filePath), "/") . "/";
foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($filePath . "proxySrc")) as $file) {

	$path = ltrim(str_replace(["\\", $filePath], ["/", ""], $file), "/");
	if ($path{0} === "." or strpos($path, "/.") !== false or substr($path, 0, 9) !== "proxySrc/") {
		continue;
	}
	$phar->addFile($file, $path);
	echo ("Adding $path \n");
}
foreach ($phar as $file => $finfo) {
	if ($finfo->getSize() > (1024 * 512)) {
		$finfo->compress(\Phar::GZ);
	}
}
$phar->stopBuffering();

echo ("Phar file has been created\n");


