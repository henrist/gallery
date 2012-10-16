<?php

/**
 * @author Henrik Steen
 */

namespace hsw\gallery;

// ini_set("memory_imit", "256M");

require "objects.php";
require "config.php";

gallery::init();

// default is root folder
$path = "";

// user-specified path
if (isset($_GET['path']))
{
	$path = $_GET['path'];
}

$node = gallery::parse_url($path);
if (!$node)
{
	gallery::error("Could not find specified path.");
}

// file?
if (get_class($node) == "file")
{
	// TODO
	die("File handling is under development..");
}

// folder






var_dump(exif::get_exif_data($src_path."/2012-07-13 Besøk i Stavern/DSC_5990.JPG"));