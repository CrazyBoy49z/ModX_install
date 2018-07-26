<?php
/**
 * User: CrazyBoy49z
 * Date: 26.07.2018
 * Time: 17:32
 */
$timestart = microtime(TRUE);
define('VERSION', '0.0.1');
$name = "modx.zip";
$urldownload = "https://github.com/modxcms/revolution/archive/2.6.x.zip";
$unpackFold = './';
$foldOld = 'revolution-2.6.x';

echo '<pre>';
print_r('ModX install by CrazyBoy49z<br/>');
file_put_contents($name,
	file_get_contents($urldownload)
);
$timeend = microtime(TRUE);
$time = round($timeend - $timestart, 4);
print_r($time. ': Download ModX from GitHub<br/>');

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

$GLOBALS['status'] = array();
$unzipper = new Unzipper;
if (file_exists($name)){
	$unzipper->prepareExtraction($name, $unpackFold);
}else{
	$timeend = microtime(TRUE);
	$time = round($timeend - $timestart, 4);
	die($time. ": File {$name} not exist<br/>");
}
$timeend = microtime(TRUE);
$time = round($timeend - $timestart, 4);
print_r($time. ": Unpack {$name}<br/>");
print_r($GLOBALS['status']);

$scanned_directory = array_diff(scandir($foldOld), array('..', '.','_build','.editorconfig','.gitignore','.travis.yml','README.md','LICENSE.md','.github'));
foreach ($scanned_directory as $value){
	rename(dirname($foldOld).'/'.$foldOld.'/'.$value, './'.$value);
}

$timeend = microtime(TRUE);
$time = round($timeend - $timestart, 4);
print_r($time. ': Move ModX folders<br/>');

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
$timeend = microtime(TRUE);
$time = round($timeend - $timestart, 4);
print_r($time. ': Delete '.$foldOld.' folders<br/>');
rrmdir($foldOld);

$timeend = microtime(TRUE);
$time = round($timeend - $timestart, 4);
print_r($time. ': Delete '.$name.'<br/>');
unlink($name);

$timeend = microtime(TRUE);
$time = round($timeend - $timestart, 4);
print_r($time. ': Complete<br/>');
echo '</pre>';
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
