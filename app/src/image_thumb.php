<?php namespace hsw\gallery;

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
        if (!App::config('cache_path')) return null;

        $f = md5($this->image->file->path) . "-{$this->max_width}x{$this->max_height}_{$this->quality}.jpg";
        $d = App::config('cache_path') . "/" . substr($f, 0, 1);

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
