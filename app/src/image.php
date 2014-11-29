<?php namespace hsw\gallery;

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