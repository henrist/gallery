<?php

/**
 * @author Henrik Steen
 */

namespace hsw\gallery;

class gallery
{
	public static $src_path;
	public static $src_url;

	public static function init()
	{
		// check for exif
		if (!exif::check_exif())
		{
			gallery::error("Exif not enabled.");
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
	private $thumbnail;

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
	 * Get image for thumbnail for this folder
	 * @return image
	 */
	public function get_thumbnail()
	{
		// cache?
		if ($this->thumbnail !== null) return $this->thumbnail;

		$this->thumbnail = false;
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
					$this->thumbnail = $f->image;
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

		return $this->thumbnail;
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
		return substr($this->path, strlen(gallery::$src_path)+1);
	}

	public function get_link()
	{
		$url = $this->get_url();
		if ($url == "") return 'index.php';

		return 'index.php?path='.urlencode($url);
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

	public function getname()
	{
		return file::strip_extension(basename($this->path));
	}

	public function getsort()
	{
		return basename($this->path);
	}

	public function get_url()
	{
		return substr($this->path, strlen(gallery::$src_path)+1);
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
		return gallery::$src_url . "/" . $this->get_url();
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

	public function get_url($max_width = 200, $max_height = 300)
	{
		return 'index.php?path='.$this->file->get_url().'&mw='.$max_width.'&mh='.$max_height;
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

	public function generate_image($max_width, $max_height, $quality = 85)
	{
		$src_img = $this->load();

		// get image info - TODO: error handling
		$size = getimagesize($this->file->path);
		$exif = $this->get_exif();

		$src_width = $size[0];
		$src_height = $size[1];

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
		imagejpeg($new_img, null, $quality);
		imagedestroy($new_img);

		$data = ob_get_contents();
		ob_clean();

		return $data;
	}

	public function output_image($data)
	{
		// TODO: cache headers etc.
		header("Content-Type: image/jpeg");
		echo $data;
	}
}