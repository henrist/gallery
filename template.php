<?php

namespace hsw\gallery;

if (!class_exists("hsw\gallery\gallery")) die("Are you sure you are at the right address?");

echo '
<!DOCTYPE html>
<html>
<head>
	<title>HSw Gallery</title>
	<link type="text/css" href="gallery.css" media="all" rel="stylesheet" />
	<script>
	</script>
</head>
<body>
	<h1>HSw Gallery</h1>';


// generate hierarchy list
$hierarchy = $node->get_parents();
$hier = array();
foreach ($hierarchy as $folder)
{
	$hier[] = '<a href="'.htmlspecialchars($folder->get_link()).'">'.htmlspecialchars($folder->getname()).'</a>';
}
$hier = implode(" &raquo; ", $hier);

echo '
	<p>Hierarchy: '.$hier.'</p>';



if (count($node->folders) > 0)
{
	echo '
	<ul class="folders">';

	foreach ($node->folders as $folder)
	{
		$img_url = 'resources/no-image.png';
		$img_alt = '';

		$folderimg = $folder->get_image();
		if ($folderimg)
		{
			$thumb = $folderimg->get_thumb();
			$img_url = $thumb->get_url();
			$img_alt = $folderimg->file->getname();
		}

		echo '
		<li><a href="'.htmlspecialchars($folder->get_link()).'">
				<img src="'.htmlspecialchars($img_url).'" alt="'.htmlspecialchars($img_alt).'" />
				<span>'.htmlspecialchars($folder->getname()).'</span>
		</a></li>';
	}

	echo '
	</ul>';
}



// no files to show?
if (count($node->files) == 0) {
	echo '
	<div class="files_none">
		<p>There are no files here.</p>
	</div>';
}

// show the files
else
{
	echo '
	<ul class="files">';

	$i = 0;
	foreach ($node->files as $file)
	{
		if ($i++ == 50) break;

		$img_url = 'resources/no-image.png';
		$img_alt = $file->getname();
		$link = $file->get_link();
		$fullsize = '';

		if ($file->image)
		{
			$thumb = $file->image->get_thumb();
			$img_url = $thumb->get_url();

			// default link is smaller size than original
			$fullsize = '
				<span class="fullsizelink"><a href="'.htmlspecialchars($link).'">Original</a></span>';
			$link = $file->image->get_thumb(1200, 1200, 90)->get_url();
		}

		echo '<!--
		--><li><a href="'.htmlspecialchars($link).'">
				<img src="'.htmlspecialchars($img_url).'" alt="'.htmlspecialchars($img_alt).'" />
				<span>'.htmlspecialchars($file->getname($file->image == false)).' ('.$file->get_size().')</span>
		</a>'.$fullsize.'</li>';
	}

	echo '
	</ul>';

	if ($i == 50)
	{
		echo '
	<p>Limited to 50 entries while testing...</p>';
	}
}



echo '
	<footer>
		<p><a href="http://github.com/henrist/gallery">HSw Gallery</a> - made by <a href="http://hsw.no">Henrik Steen</a></p>
	</footer>
</body>
</html>';