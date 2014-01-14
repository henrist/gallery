<?php

/**
 * @author Henrik Steen
 */

namespace hsw\gallery;

class gallery
{
	public static $src_path;
	public static $src_url;
	public static $cache_path;

	public static function init()
	{
		// check for exif
		if (!exif::check_exif())
		{
			gallery::error("Exif not enabled.");
		}

		// check for cache directory
		if (self::$cache_path)
		{
			if (!file_exists(self::$cache_path))
			{
				// TODO error handling
				self::cache_dir_create(self::$cache_path);
			} else {
				if (!is_dir(self::$cache_path)) {
					// disable cache
					self::$cache_path = null;
				}
			}
		}
	}

	public static function error($msg)
	{
		die($msg);
	}

	public static function parse_url($url)
	{
		$s = realpath(self::$src_path . "/" . $url);

		// check for invalid paths
		if (substr($s, 0, strlen(self::$src_path)) != self::$src_path) return false; // hack attempt?

		// folder?
		if (is_dir($s))
		{
			return new folder($s);
		}

		// file?
		else if (is_file($s))
		{
			return new file($s);
		}

		// unknown type
		return false;
	}

	public static function cache_dir_create($dir)
	{
		mkdir($dir);
		chmod($dir, 0777);
	}
}

/**
 * For handling Exif-data
 */
class exif
{
	public $data;
	public $model;
	public $orientation;
	public $exposuretime;
	public $fnumber;
	public $iso;
	public $date;
	public $focallength;

	public static function check_exif()
	{
		return function_exists("exif_read_data");
	}

	public static function get_exif_data($file)
	{
		// data to get:
		// * Model
		// * Orientation
		// * ExposureTime
		// * FNumber
		// * ISOSpeedRatings
		// * DateTimeOriginal (DateTime)
		// * FocalLength
		
		$exif = new self();

		// only extract from jpeg
		if (preg_match("/\\.jpe?g$/i", $file))
		{
			$data = $exif->data = @exif_read_data($file, "IFD0", 0);
			
			$exif->model = isset($data['Model']) ? $data['Model'] : "Unknown";
			$exif->orientation = isset($data['Orientation']) ? $data['Orientation'] : 0;
			$exif->exposuretime = isset($data['ExposureTime']) ? $data['ExposureTime'] : "Unknown";
			$exif->fnumber = isset($data['FNumber']) ? $data['FNumber'] : "Unknown";
			$exif->iso = isset($data['ISOSpeedRatings']) ? $data['ISOSpeedRatings'] : "Unknown";
			$exif->date = isset($data['DateTimeOriginal']) ? $data['DateTimeOriginal'] : (isset($data['DateTime']) ? $data['DateTime'] : filectime($file));
			$exif->focallength = isset($data['FocalLength']) ? $data['FocalLength'] : "Unknown";
		}

		return $exif;
	}

	public function getdate()
	{
		return strtotime(str_replace(":", "-", $this->date));
	}

	public function get_rotation()
	{
		// 90 degrees right
		if ($this->orientation == 6) return 270;

		// 90 degrees left
		if ($this->orientation == 8) return 90;

		// not known to be rotated
		return 0;
	}
}

/**
 * Handling folders
 */
class folder
{
	public $path;
	public $folders = array();
	public $files = array();

	private $contents_loaded = false;
	public $is_root_folder = false;

	/** @var image */
	private $folder_image;

	public function __construct($path)
	{
		$this->path = $path;

		if (strlen($path) == strlen(gallery::$src_path)) $this->is_root_folder = true;
	}

	private static function get_dh($path)
	{
		$dh = opendir($path);
		if (!$dh) throw new Exception("Could not open directory: $this->path");

		return $dh;
	}

	public function load_contents()
	{
		$this->contents_loaded = true;
		$this->folders = array();
		$this->files = array();

		$dh = self::get_dh($this->path);

		$sort_folders = array();
		$sort_files = array();

		while (($file = readdir($dh)) !== false)
		{
			//if ($file == "." || $file == "..") continue;

			// ignore hidden files
			if (substr($file, 0, 1) == ".") continue;

			$p = $this->path . "/" . $file;

			// folder?
			if (is_dir($p))
			{
				$obj = new folder($p);
				$this->folders[] = $obj;
				$sort_folders[] = $obj->getsort();
			}

			// file?
			else if (is_file($p))
			{
				$obj = new file($p);
				$this->files[] = $obj;
				$sort_files[] = $obj->getsort();
			}

			// ignore other types
		}

		// sort array
		array_multisort($this->folders, $sort_folders);
		array_multisort($this->files, $sort_files);

		closedir($dh);
	}

	/**
	 * Get image representing this folder
	 * @return image
	 */
	public function get_image()
	{
		// cache?
		if ($this->folder_image !== null) return $this->folder_image;

		$this->folder_image = false;
		$dir = $this->path;
		
		// simply pick the first image inside this folder
		// search subdirectories if first directory have no images
		while ($dir !== false)
		{
			$dh = self::get_dh($dir);
			$ds = false;

			// search folder
			while (($file = readdir($dh)) !== false)
			{
				if ($file == "." || $file == "..") continue;
				$p = $dir . "/" . $file;

				if (is_file($p) && image::is_image_type($p))
				{
					$f = new file($p);
					$this->folder_image = $f->image;
					closedir($dh);
					break 2;
				}

				elseif (is_dir($p) && $ds === false) {
					$ds = $p;
				}
			}

			closedir($dh);
			$dir = $ds;
		}

		return $this->folder_image;
	}

	/**
	 * Get folder name
	 */
	public function getname()
	{
		if ($this->is_root_folder) return 'Gallery';
		return basename($this->path);
	}

	/**
	 * Get value to use for sorting
	 */
	public function getsort()
	{
		$s = $this->getname();
		return $s;
	}

	public function get_url()
	{
		return '/'.substr($this->path, strlen(gallery::$src_path)+1);
	}

	public function get_link()
	{
		return $this->get_url();
	}

	/**
	 * Get hierarchy of parent folders
	 */
	public function get_parents()
	{
		$hierarchy = array();
		$hierarchy[] = $this;

		$d = dirname($this->path);
		while (strlen($d) >= strlen(gallery::$src_path))
		{
			$hierarchy[] = new folder($d);
			$d = dirname($d);
		}

		return array_reverse($hierarchy);
	}
}

/**
 * Handling files
 */
class file
{
	public $path;

	/** @var image */
	public $image;

	public function __construct($path)
	{
		$this->path = $path;

		// image?
		if (image::is_image_type($path))
		{
			$this->image = new image($this);
		}
	}

	public function getname($extension = false)
	{
		if ($extension) return basename($this->path);
		return file::strip_extension(basename($this->path));
	}

	public function getsort()
	{
		return basename($this->path);
	}

	public function get_url()
	{
		return '/'.substr($this->path, strlen(gallery::$src_path)+1);
	}

	public static function strip_extension($name)
	{
		$pos = strrpos($name, ".");

		// don't strip if only has a . at the beginng or have no extension
		if (!$pos) return $name;

		return substr($name, 0, $pos);
	}

	public function get_link()
	{
		return gallery::$src_url . $this->get_url();
	}

	public function get_size($format = true)
	{
		$size = filesize($this->path);
		if (!$format) return $size;

		$t = array("B", "KiB", "MiB");
		$i = 0;
		foreach ($t as $item)
		{
			if ($size < 1024) return round($size, ceil($i))." $item";
			$size /= 1024;
			$i += 0.5;
		}

		return round($size, ceil($i))." GiB";
	}
}

/**
 * Handling images
 */
class image
{
	/** @var file */
	public $file;

	/** @var exif */
	private $exif;
	
	public function __construct(file $file)
	{
		$this->file = $file;
	}

	public static function is_image_type($path)
	{
		return preg_match("/\\.(jpe?g|gif|png)$/i", $path);
	}

	public function get_url($max_width = 300, $max_height = 480)
	{
		return $this->file->get_url().'?mw='.$max_width.'&mh='.$max_height;
	}

	public function load()
	{
		return imagecreatefromstring(file_get_contents($this->file->path));
	}

	public function get_exif()
	{
		if ($this->exif) return $this->exif;
		$this->exif = exif::get_exif_data($this->file->path);
		return $this->exif;
	}

	public function get_thumb($max_width = 300, $max_height = 480, $quality = 80)
	{
		return new image_thumb($this, $max_width, $max_height, $quality);
	}

	public function output_image($data)
	{
		$cache_time = 86400*14;
		header("Cache-Control: public, max-age=$cache_time, pre-check=$cache_time");
		header("Pragma: public");
		header("Expires: ".gmdate(DATE_RFC822, time()+$cache_time));
		header("Last-Modified: ".gmdate(DATE_RFC822, filemtime($this->file->path)));

		header("Content-Type: image/jpeg");
		header("Content-Size: ".strlen($data));
		echo $data;
	}
}

/**
 * Handling image thumbnails
 */
class image_thumb
{
	/** @var image */
	public $image;
	public $max_width;
	public $max_height;
	public $quality;
	private $cache_file;

	public function __construct(image $image, $max_width, $max_height, $quality)
	{
		$this->image = $image;
		$this->max_width = $max_width;
		$this->max_height = $max_height;
		$this->quality = $quality;
	}

	public function generate($cache = true)
	{
		// check if thumb is cached
		if ($cache)
		{
			$d = $this->cache_get();
			if ($d) return $d;
		}

		$src_img = $this->image->load();

		// get image info - TODO: error handling
		$size = getimagesize($this->image->file->path);
		$exif = $this->image->get_exif();

		$src_width = $size[0];
		$src_height = $size[1];
		$max_width = $this->max_width;
		$max_height = $this->max_height;

		// shall be rotated? switch width/height temporarily and rotate after image has been scaled
		$rotation = $exif->get_rotation();
		if ($rotation) {
			$t = $max_height;
			$max_height = $max_width;
			$max_width = $t;
		}

		// calculate new size
		if ($src_width / $src_height > $max_width / $max_height) {
			$new_width = $max_width;
			$new_height = round($max_width / $src_width * $src_height);
		} else {
			$new_height = $max_height;
			$new_width = round($max_height / $src_height * $src_width);
		}

		// create new image
		$new_img = imagecreatetruecolor($new_width, $new_height);

		// copy to new image
		imagecopyresampled($new_img, $src_img, 0, 0, 0, 0, $new_width, $new_height, $src_width, $src_height);
		imagedestroy($src_img);

		// rotate?
		if ($rotation) {
			$rotated = imagerotate($new_img, $rotation, 0);
			imagedestroy($new_img);
			$new_img = $rotated;

			// switch width/height back
			#$t = $new_height;
			#$new_height = $new_width;
			#$new_width = $t;
		}

		// catch output
		ob_start();
		ob_clean();
		imagejpeg($new_img, null, $this->quality);
		imagedestroy($new_img);

		$data = ob_get_contents();
		ob_clean();

		// save cache
		$this->cache_save($data);

		return $data;
	}

	public function output()
	{
		$this->image->output_image($this->generate());
	}

	public function get_url()
	{
		return $this->image->file->get_url().'?mw='.$this->max_width.'&mh='.$this->max_height;
	}

	private function cache_get_path()
	{
		if ($this->cache_file) return $this->cache_file;
		if (!gallery::$cache_path) return null;

		$f = md5($this->image->file->path) . "-{$this->max_width}x{$this->max_height}_{$this->quality}.jpg";
		$d = gallery::$cache_path . "/" . substr($f, 0, 1);

		// create directory?
		if (!file_exists($d))
		{
			gallery::cache_dir_create($d);
		}

		$this->cache_file = $d . "/" . $f;
		return $this->cache_file;
	}

	private function cache_save($data)
	{
		$f = $this->cache_get_path();
		file_put_contents($f, $data);
	}

	private function cache_get()
	{
		$f = $this->cache_get_path();
		if (!file_exists($f)) return null;

		return file_get_contents($f);
	}
}