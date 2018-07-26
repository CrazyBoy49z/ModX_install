<?php
/**
 * @author  Yura Finiv as CrazyBoy49z
 * @license GNU GPL v3
 * @version 0.0.2
 * Date: 26.07.2018
 * Time: 23:32
 */
echo '<pre>';
print_r('ModX install by CrazyBoy49z<br/>');
$timestart = microtime(TRUE);
if (!isset($_GET['v'])){
	die('Version modx is null download.php?v=2.6.5');
}
$ver = explode('.', $_GET['v']);
$ver = (int)$ver[0].'.'.(int)$ver[1].'.'.(int)$ver[2];
$name = "modx-{$ver}-pl";
unset($ver);
$namezip = "{$name}.zip";
$urldownload = "https://modx.com/download/direct?id={$namezip}";
$unpackFold = './';
$GLOBALS['status'] = array();

function logs($log){
	global $timestart;
	$timeend = microtime(TRUE);
	$time = round($timeend - $timestart, 4);
	print_r("{$time} : {$log} <br/>");
}

logs("Get file $namezip");
$file = file_get_contents($urldownload);
unset($urldownload);
if (empty($file)){
	logs("Bad version modx");
	return;
}
logs("Download ModX");
file_put_contents($namezip, $file);
unset($file);
logs("Unzip {$namezip}");
$unzipper = new Unzipper;
if (file_exists($namezip)){
	$unzipper->prepareExtraction($namezip, $unpackFold);
	logs("Unpack {$namezip}");
	logs($GLOBALS['status']);
	unset($GLOBALS['status']);
	logs('Move ModX folders');
	$scanned_directory = array_diff(scandir($name), array('..', '.'));
	foreach ($scanned_directory as $value){
		rename(dirname($name).'/'.$name.'/'.$value, './'.$value);
	}
	log("Delete {$name} folders");
	rrmdir($name);
	unlink($namezip);
}else{
	logs("File {$namezip} not exist");
	die();
}
unset($unzipper);
function rrmdir($src) {
	$dir = opendir($src);
	while(false !== ( $file = readdir($dir)) ) {
		if (( $file != '.' ) && ( $file != '..' )) {
			$full = $src . '/' . $file;
			if ( is_dir($full) ) {
				rrmdir($full);
			}
			else {
				unlink($full);
			}
		}
	}
	closedir($dir);
	rmdir($src);
}

logs('Complete');
echo '</pre>';
if (isset($_GET['d'])){
	@unlink( FILE );
}

/**
 * The Unzipper extracts .zip or .rar archives and .gz files on webservers.
 * It's handy if you do not have shell access. E.g. if you want to upload a lot
 * of files (php framework or image collection) as an archive to save time.
 * As of version 0.1.0 it also supports creating archives.
 *
 * @author  Andreas Tasch, at[tec], attec.at
 * @license GNU GPL v3
 * @package attec.toolbox
 * @version 0.1.1
 */

/**
 * Class Unzipper
 */
class Unzipper {
	public $localdir = '.';
	public $zipfiles = array();
	public function __construct() {
		// Read directory and pick .zip, .rar and .gz files.
		if ($dh = opendir($this->localdir)) {
			while (($file = readdir($dh)) !== FALSE) {
				if (pathinfo($file, PATHINFO_EXTENSION) === 'zip'
					|| pathinfo($file, PATHINFO_EXTENSION) === 'gz'
					|| pathinfo($file, PATHINFO_EXTENSION) === 'rar'
				) {
					$this->zipfiles[] = $file;
				}
			}
			closedir($dh);
			if (!empty($this->zipfiles)) {
				$GLOBALS['status'] = array('info' => '.zip or .gz or .rar files found, ready for extraction');
			}
			else {
				$GLOBALS['status'] = array('info' => 'No .zip or .gz or rar files found. So only zipping functionality available.');
			}
		}
	}
	/**
	 * Prepare and check zipfile for extraction.
	 *
	 * @param string $archive
	 *   The archive name including file extension. E.g. my_archive.zip.
	 * @param string $destination
	 *   The relative destination path where to extract files.
	 */
	public function prepareExtraction($archive, $destination = '') {
		// Determine paths.
		if (empty($destination)) {
			$extpath = $this->localdir;
		}
		else {
			$extpath = $this->localdir . '/' . $destination;
			// Todo: move this to extraction function.
			if (!is_dir($extpath)) {
				mkdir($extpath);
			}
		}
		// Only local existing archives are allowed to be extracted.
		if (in_array($archive, $this->zipfiles)) {
			self::extract($archive, $extpath);
		}
	}
	/**
	 * Checks file extension and calls suitable extractor functions.
	 *
	 * @param string $archive
	 *   The archive name including file extension. E.g. my_archive.zip.
	 * @param string $destination
	 *   The relative destination path where to extract files.
	 */
	public static function extract($archive, $destination) {
		$ext = pathinfo($archive, PATHINFO_EXTENSION);
		switch ($ext) {
			case 'zip':
				self::extractZipArchive($archive, $destination);
				break;
			case 'gz':
				self::extractGzipFile($archive, $destination);
				break;
			case 'rar':
				self::extractRarArchive($archive, $destination);
				break;
		}
	}
	/**
	 * Decompress/extract a zip archive using ZipArchive.
	 *
	 * @param $archive
	 * @param $destination
	 */
	public static function extractZipArchive($archive, $destination) {
		// Check if webserver supports unzipping.
		if (!class_exists('ZipArchive')) {
			$GLOBALS['status'] = array('error' => 'Error: Your PHP version does not support unzip functionality.');
			return;
		}
		$zip = new ZipArchive;
		// Check if archive is readable.
		if ($zip->open($archive) === TRUE) {
			// Check if destination is writable
			if (is_writeable($destination . '/')) {
				$zip->extractTo($destination);
				$zip->close();
				$GLOBALS['status'] = array('success' => 'Files unzipped successfully');
			}
			else {
				$GLOBALS['status'] = array('error' => 'Error: Directory not writeable by webserver.');
			}
		}
		else {
			$GLOBALS['status'] = array('error' => 'Error: Cannot read .zip archive.');
		}
	}
	/**
	 * Decompress a .gz File.
	 *
	 * @param string $archive
	 *   The archive name including file extension. E.g. my_archive.zip.
	 * @param string $destination
	 *   The relative destination path where to extract files.
	 */
	public static function extractGzipFile($archive, $destination) {
		// Check if zlib is enabled
		if (!function_exists('gzopen')) {
			$GLOBALS['status'] = array('error' => 'Error: Your PHP has no zlib support enabled.');
			return;
		}
		$filename = pathinfo($archive, PATHINFO_FILENAME);
		$gzipped = gzopen($archive, "rb");
		$file = fopen($destination . '/' . $filename, "w");
		while ($string = gzread($gzipped, 4096)) {
			fwrite($file, $string, strlen($string));
		}
		gzclose($gzipped);
		fclose($file);
		// Check if file was extracted.
		if (file_exists($destination . '/' . $filename)) {
			$GLOBALS['status'] = array('success' => 'File unzipped successfully.');
			// If we had a tar.gz file, let's extract that tar file.
			if (pathinfo($destination . '/' . $filename, PATHINFO_EXTENSION) == 'tar') {
				$phar = new PharData($destination . '/' . $filename);
				if ($phar->extractTo($destination)) {
					$GLOBALS['status'] = array('success' => 'Extracted tar.gz archive successfully.');
					// Delete .tar.
					unlink($destination . '/' . $filename);
				}
			}
		}
		else {
			$GLOBALS['status'] = array('error' => 'Error unzipping file.');
		}
	}
	/**
	 * Decompress/extract a Rar archive using RarArchive.
	 *
	 * @param string $archive
	 *   The archive name including file extension. E.g. my_archive.zip.
	 * @param string $destination
	 *   The relative destination path where to extract files.
	 */
	public static function extractRarArchive($archive, $destination) {
		// Check if webserver supports unzipping.
		if (!class_exists('RarArchive')) {
			$GLOBALS['status'] = array('error' => 'Error: Your PHP version does not support .rar archive functionality. <a class="info" href="http://php.net/manual/en/rar.installation.php" target="_blank">How to install RarArchive</a>');
			return;
		}
		// Check if archive is readable.
		if ($rar = RarArchive::open($archive)) {
			// Check if destination is writable
			if (is_writeable($destination . '/')) {
				$entries = $rar->getEntries();
				foreach ($entries as $entry) {
					$entry->extract($destination);
				}
				$rar->close();
				$GLOBALS['status'] = array('success' => 'Files extracted successfully.');
			}
			else {
				$GLOBALS['status'] = array('error' => 'Error: Directory not writeable by webserver.');
			}
		}
		else {
			$GLOBALS['status'] = array('error' => 'Error: Cannot read .rar archive.');
		}
	}
}
