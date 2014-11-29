<?php namespace hsw\gallery;

class App {
    /**
     * The app as a singleton
     */
    private static $app;

    /**
     * Get application object
     */
    public static function get() {
        return static::$app;
    }

    /**
     * Get config
     */
    public static function config($name) {
        return static::$app->config[$name];
    }

    /**
     * Set config
     */
    public static function configSet($name, $value) {
        static::$app->config[$name] = $value;
    }

    /**
     * The config for the app
     */
    public $config;

    public function __construct() {
        if (static::$app) throw new Exception("Application already exists.");
        static::$app = $this;

        $this->loadConfig();
    }

    /**
     * Load config
     */
    public function loadConfig() {
        $this->config = require 'config.php';
    }

    /**
     * Run the application
     */
    public function run() {
        ini_set("memory_limit", "256M");

        gallery::init();

        // extract the path
        // default (if '') is root folder
        $path = urldecode($_SERVER['REQUEST_URI']);
        if (($pos = strpos($path, "?")) !== false)
        {
            $path = substr($path, 0, $pos);
        }

        $path = substr($path, 9);

        $node = gallery::parse_url($path);
        if (!$node)
        {
            gallery::error("Could not find specified path.");
        }

        // file?
        if (get_class($node) == "hsw\\gallery\\file")
        {
            // no image?
            if (!$node->image) die("No image.");

            // has cache?
            if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']))
            {
                $m = filemtime($node->path);
                if (strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) == $m)
                {
                    header("Last-Modified: ".gmdate(DATE_RFC822, $m), true, 304);
                    die;
                }
            }

            // TODO: use mw and mh parameters
            $mw = isset($_GET['mw']) && is_numeric($_GET['mw']) && $_GET['mw'] > 0 && $_GET['mw'] < 3000 ? $_GET['mw'] : 300;
            $mh = isset($_GET['mh']) && is_numeric($_GET['mh']) && $_GET['mh'] > 0 && $_GET['mh'] < 3000 ? $_GET['mh'] : 480;
            $node->image->get_thumb($mw, $mh)->output();
            die;

            // TODO
            die("File handling is under development..");
        }

        // folder
        $node->load_contents();

        // load template
        require "views/template.php";
    }
}