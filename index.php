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
	// TODO: use mw and mh parameters
	$node->image->output_image($node->image->generate_image(200, 400));
	die;

	// TODO
	die("File handling is under development..");
}

// folder
$node->load_contents();

// load template
require "template.php";