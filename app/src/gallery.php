<?php namespace hsw\gallery;

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
        if (App::config('cache_path'))
        {
            if (!file_exists(App::config('cache_path')))
            {
                // TODO error handling
                self::cache_dir_create(App::config('cache_path'));
            } else {
                if (!is_dir(App::config('cache_path'))) {
                    // disable cache
                    App::configSet('cache_path', null);
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
        $s = realpath(App::config('src_path') . "/" . $url);
        if ($s === false) {
            return false;
        }

        // check for invalid paths
        if (substr($s, 0, strlen(App::config('src_path'))) != App::config('src_path')) return false; // hack attempt?

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