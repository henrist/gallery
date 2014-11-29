<?php namespace hsw\gallery;

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

        if (strlen($path) == strlen(App::config('src_path'))) $this->is_root_folder = true;
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
        return substr($this->path, strlen(App::config('src_path'))+1);
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
        while (strlen($d) >= strlen(App::config('src_path')))
        {
            $hierarchy[] = new folder($d);
            $d = dirname($d);
        }

        return array_reverse($hierarchy);
    }
}