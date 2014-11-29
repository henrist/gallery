<?php namespace hsw\gallery;

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
        return substr($this->path, strlen(App::config('src_path'))+1);
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
        return App::config('src_url') . '/' . $this->get_url();
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