<?php
/**
 * Merge Javascript and CSS files
 *
 * @author		Edgar Alves
 * @since		24-03-2011
 * @version		1.1
 * @filesource	Merge.php
 */

class Merge {
	/**
	 * self::cacheFolder
	 * Cache folder
	 */
	const cacheFolder = 'cache';
	/**
	 * self::minify
	 * Minify flag
	 */
	const minify = false;
	/**
	 * self::gzip
	 * Compression
	 */
	const gzip = true;

	/**
	 * Merge::javascript()
	 * Merge javascript files
	 *
	 * @param	array	$files	File list
	 * @param	Boolean	$isUtf8	Treat merged file as UTF8 encode
	 *
	 * @return string File Name
	 */
	public static function javascript($files, $isUtf8 = false) {
		$appDir = dirname($_SERVER['SCRIPT_FILENAME']) . '/';
		$cacheDir = $appDir . self::cacheFolder;

		//# Get File list
		$fileList = is_array($files) ? $files : explode("|", $files);

		//# Get Files Version
		$version = self::getFilesVersion($fileList, $appDir);

		//# Check Cache
		if (!file_exists($cacheDir)) { mkdir($cacheDir); }

		//# Merged File
		/**
		 * Merged File
		 * Exception to get the base file in apps with single file and apps with
		 * smarty directory structure
		 */
		if (stripos(basename($_SERVER['SCRIPT_FILENAME']), 'index') === false) {
			$baseFile = basename($_SERVER['SCRIPT_FILENAME'], '.php');
		} else {
			$baseFile = basename($appDir);
		}

		$fileName = $cacheDir . '/js.'.$baseFile.'.' . $version . '.php';
		if (!file_exists($fileName)) {
			//# Clear dir
			array_map("unlink", glob($cacheDir . '/js.' . $baseFile . '.*.php'));

			//# Make file
			self::combine($fileList, $appDir, $fileName, $version, $isUtf8);
		}

		return str_replace($appDir, '', $fileName);
	}

	/**
	 * Merge::css()
	 * Merge CSS files
	 *
	 * @param	array	$files
	 * @param	Boolean	$isUtf8 Treat merged file as UTF8 encode
	 *
	 * @return string
	 */
	public static function css($files, $isUtf8 = false) {
		//# Vars
		$appDir = dirname($_SERVER['SCRIPT_FILENAME']) . '/';
		$cacheDir = $appDir . self::cacheFolder;

		//# Get File list
		$fileList = is_array($files) ? $files : explode("|", $files);

		//# Get Files Version
		$version = self::getFilesVersion($fileList, $appDir);

		//# Check Cache
		if (!file_exists($cacheDir)) { mkdir($cacheDir); }

		//# Merged File
		$fileName = $cacheDir . '/css.'.basename($appDir).'.' . $version . '.php';
		if (!file_exists($fileName)) {
			//# Clear dir
			array_map("unlink", glob($cacheDir . '/css.*.php'));

			//# Make file
			self::combine($fileList, $appDir, $fileName, $version, $isUtf8);
		}

		return str_replace($appDir, '', $fileName);
	}

	/**
	 * Merge::getFilesVersion()
	 * Check files version using the modified date
	 *
	 * @param	array $files
	 * @param	string $dir
	 *
	 * @return string
	 */
	private static function getFilesVersion($files, $dir) {
		$size = 0;
		$date = 0;

		foreach ($files as $file) {
			//# Check if file dir should be document root or application root
			if (in_array(substr($file, 0, 1), array('\\', '/'))) {
				$useDir = $_SERVER['DOCUMENT_ROOT'];
			} else {
				$useDir = $dir;
			}

			if (file_exists($useDir . $file)) {
				$stat = stat($useDir . $file);

				$size += $stat['size'];
				if ($stat['mtime'] > $date) { $date = $stat['mtime']; }
			}
		}

		return substr(md5($size + $date), 0, 8);
	}

	/**
	 * Merge::isUTF8()
	 * Check if string is UTF-8 encoded
	 *
	 * @param	string $dir
	 *
	 * @return string
	 */
	private static function isUTF8($string){
		return preg_match('%(?:
			[\xC2-\xDF][\x80-\xBF]        # non-overlong 2-byte
			|\xE0[\xA0-\xBF][\x80-\xBF]               # excluding overlongs
			|[\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}      # straight 3-byte
			|\xED[\x80-\x9F][\x80-\xBF]               # excluding surrogates
			|\xF0[\x90-\xBF][\x80-\xBF]{2}    # planes 1-3
			|[\xF1-\xF3][\x80-\xBF]{3}                  # planes 4-15
			|\xF4[\x80-\x8F][\x80-\xBF]{2}    # plane 16
			)+%xs', $string);
	}

	/**
	 * Merge::combine()
	 * Combine files building a unique file
	 *
	 * @param	array	$files
	 * @param	string	$dir
	 * @param	string	$fileName
	 * @param	string	$version
	 *
	 * @return void
	 */
	private static function combine($files, $dir, $fileName, $version, $isUtf8) {
		$content = '';
		foreach ($files as $file) {
			//# Check if file dir should be document root or application root
			if (in_array(substr($file, 0, 1), array('\\', '/'))) {
				$useDir = $_SERVER['DOCUMENT_ROOT'];
			} else {
				$useDir = $dir;
			}

			if (file_exists($useDir . $file)) {
				$temp = file_get_contents($useDir . $file);

				if (!$isUtf8 && self::isUTF8($temp)) {
					$temp = iconv('UTF-8', 'ISO-8859-1', $temp);
				}
				$content .= chr(10) . " /* FILE: " . $file . " */ " . chr(10);
				$content .= chr(10) . $temp;
			}
		}

		if (self::minify) {
			if (strpos($fileName, 'js') !== false) {
				include_once($_SERVER['DOCUMENT_ROOT'] . '/libs/jsmin.php');
				$content = JSMin::minify($content);
			} else {
				include_once($_SERVER['DOCUMENT_ROOT'] . '/libs/cssmin.php');
				$content = CssMin::minify($content, array(
						"remove-empty-blocks"           => true,
						"remove-empty-rulesets"         => true,
						"remove-last-semicolons"        => true,
						"convert-css3-properties"       => true,
						"convert-font-weight-values"    => true,
						"convert-named-color-values"    => true,
						"convert-hsl-color-values"      => true,
						"convert-rgb-color-values"      => true,
						"compress-color-values"         => true,
						"compress-unit-values"          => true,
						"emulate-css3-variables"        => true
					)
				);
			}
		}

		/**
		 * Main file with cache headers
		 *
		 * @var string
		 */
		$return = '<?php
			$eTag = md5((filesize(__FILE__) + filemtime(__FILE__)));

			if (array_key_exists(\'HTTP_IF_NONE_MATCH\', $_SERVER) && str_replace(\'"\', \'\', stripslashes($_SERVER[\'HTTP_IF_NONE_MATCH\'])) == $eTag) {
				header($_SERVER[\'SERVER_PROTOCOL\']." 304 Not Modified");
				exit;
			}

			header("content-type: '.((strpos($fileName, 'js') !== false) ? 'application/javascript' : 'text/css').'");
			header(\'Etag: "\'.$eTag.\'"\');
			header("Cache-Control: public");
			header("Last-Modified: ".gmdate(\'D, d M Y H:i:s\', filemtime(__FILE__)));
			header(\'Expires: \'.gmdate(\'D, d M Y H:i:s\', time() + 31356000));

			if (extension_loaded("zlib")) { ';

		if (self::gzip) {
			$return .= "if (!ini_get('zlib.output_compression')) { ob_start('ob_gzhandler'); } ";
		} else {
			$return .= "@apache_setenv('no-gzip', 1); @ini_set('zlib.output_compression', 0); ";
		}
		$return .= "\n}\n?>" . $content;

		file_put_contents($fileName, $return, LOCK_EX);
	}

}