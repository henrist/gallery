<?php namespace hsw\gallery;

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