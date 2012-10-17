<?php

namespace hsw\gallery;

if (!class_exists("hsw\gallery\gallery")) die("Are you sure you are at the right address?");

?>
<!DOCTYPE html>
<html>
<head>
	<title>Gallery</title>
	<style>

	</style>
	<script>
	</script>
</head>
<body>
	<h1>Gallery</h1>
<?php

// generate hierarchy list
$hierarchy = $node->get_parents();
$hier = array();
foreach ($hierarchy as $folder)
{
	$hier[] = '<a href="'.htmlspecialchars($folder->get_link()).'">'.htmlspecialchars($folder->getname()).'</a>';
}
$hier = implode(" &raquo; ", $hier);

echo '
	<p>Hierarchy: '.$hier.'</p>
	<p>Folders:</p>';

if (count($node->folders) == 0) {
	echo '
	<p>There are no subfolders here.</p>';
} else {
	$i = 0;
	foreach ($node->folders as $folder)
	{
		if ($i++ == 30)
		{
			echo '
	<p>Limited to 30 entries while testing...</p>';
			break;
		}

		$thumb = $folder->get_thumbnail();
		
		$t = 'none';
		if ($thumb)
		{
			$t = '<img src="'.htmlspecialchars($thumb->get_url()).'" alt="'.htmlspecialchars($thumb->file->getname()).'" />';
		}

		echo '
	<p>Folder: <a href="'.htmlspecialchars($folder->get_link()).'">'.htmlspecialchars($folder->getname()).'</a> - thumb: '.$t.'</p>';
	}
}

echo '
	<p>Files:</p>';

if (count($node->files) == 0) {
	echo '
	<p>There are no files here.</p>';
} else {
	$i = 0;
	foreach ($node->files as $file)
	{
		if ($i++ == 10)
		{
			echo '
	<p>Limited to 10 entries while testing...</p>';
			break;
		}

		$thumb = $file->image;
		
		$t = 'none';
		if ($thumb)
		{
			$t = '<img src="'.htmlspecialchars($thumb->get_url()).'" alt="'.htmlspecialchars($thumb->file->getname()).'" />';
		}

		echo '
	<p>File: <a href="'.htmlspecialchars($file->get_link()).'">'.htmlspecialchars($file->getname()).'</a> ('.$file->get_size().') - thumb: '.$t.'</p>';
	}
}

?>
</body>
</html>