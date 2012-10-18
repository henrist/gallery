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
	$node->image->get_thumb()->output(200, 400);
	die;

	// TODO
	die("File handling is under development..");
}

// folder
$node->load_contents();

// load template
require "template.php";