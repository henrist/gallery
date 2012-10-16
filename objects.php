<?php

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
		$data = $exif->data = exif_read_data($file, "IFD0", 0);
		var_dump($data);
		
		$exif->model = isset($data['Model']) ? $data['Model'] : "Unknown";
		$exif->orientation = isset($data['Orientation']) ? $data['Orientation'] : 0;
		$exif->exposuretime = isset($data['ExposureTime']) ? $data['ExposureTime'] : "Unknown";
		$exif->fnumber = isset($data['FNumber']) ? $data['FNumber'] : "Unknown";
		$exif->iso = isset($data['ISOSpeedRatings']) ? $data['ISOSpeedRatings'] : "Unknown";
		$exif->date = isset($data['DateTimeOriginal']) ? $data['DateTimeOriginal'] : (isset($data['DateTime']) ? $data['DateTime'] : filectime($file));
		$exif->focallength = isset($data['FocalLength']) ? $data['FocalLength'] : "Unknown";

		return $exif;
	}

	public function getdate()
	{
		return strtotime(str_replace(":", "-", $this->date));
	}
}

/**
 * Handling folders
 */
class folder
{
	public $path;

	public function __construct($path)
	{
		$this->path = $path;
	}
}

/**
 * Handling files
 */
class file
{

}

/**
 * Handling images
 */
class image
{

}