<?php

use hsw\gallery\App;

// generate hierarchy list
$hierarchy = $node->get_parents();
$hier = array();
foreach ($hierarchy as $folder)
{
    $hier[] = '<a href="'.htmlspecialchars($folder->get_link()).'">'.htmlspecialchars($folder->getname()).'</a>';
}
$hier = implode(" &raquo; ", $hier);

// contents from HEADER.html
$header = $node->getHeader();

?>
<!DOCTYPE html>
<html>
<head>
    <title>HSw Gallery</title>
    <base href="<?=htmlspecialchars(App::config('basepath'));?>">
    <link type="text/css" href="gallery.css" media="all" rel="stylesheet" />
    <script type="text/javascript" src="//cdnjs.cloudflare.com/ajax/libs/jquery/2.1.1/jquery.min.js"></script>
    <script type="text/javascript">
    var curPath = <?=json_encode(App::config('basepath').$node->get_url());?>;
    $(function() {
        $("#jumpFolder").change(function() {
            window.location = curPath + (curPath.slice(-1) == "/" ? "" : "/") + $(this).val();
        });
    });
    </script>
</head>
<body>
    <h1>HSw Gallery</h1>

    <p>Hierarchy: <?=$hier;?></p>

    <select id="jumpFolder">
        <option>Hopp til undermappe</option>

        <?php foreach ($node->getTreeStructure() as $path): ?>
            <option value="<?=htmlspecialchars($path);?>"><?=htmlspecialchars($path);?></option>
        <?php endforeach; ?>
    </select>

    <?php if ($header != ''): ?>
        <div class="header">
            <?=$header;?>
        </div>
    <?php endif; ?>

    <?php if (count($node->folders) > 0): ?>
        <ul class="folders">
            <?php foreach ($node->folders as $folder):
                $img_url = 'resources/no-image.png';
                $img_alt = '';

                $folderimg = $folder->get_image();
                if ($folderimg)
                {
                    $thumb = $folderimg->get_thumb();
                    $img_url = $thumb->get_url();
                    $img_alt = $folderimg->file->getname();
                } ?>

                <li>
                    <a href="<?=htmlspecialchars($folder->get_link());?>">
                        <img src="<?=htmlspecialchars($img_url);?>" alt="<?=htmlspecialchars($img_alt);?>" />
                        <span><?=htmlspecialchars($folder->getname());?></span>
                    </a>
                </li>

            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <?php
    // no files to show?
    if (count($node->files) == 0): ?>
        <div class="files_none">
            <p>There are no files here.</p>
        </div>

    <?php
    // show the files
    else: ?>

        <ul class="files">

            <?php


            $i = 0;
            foreach ($node->files as $file):
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

                ?><!--
                --><li>
                    <a href="<?=htmlspecialchars($link);?>">
                        <img src="<?=htmlspecialchars($img_url);?>" alt="<?=htmlspecialchars($img_alt);?>" />
                        <span><?=htmlspecialchars($file->getname($file->image == false));?> (<?=$file->get_size();?>)</span>
                    </a>
                    <?=$fullsize;?>
                </li><?php

            endforeach;
            ?>

        </ul>
    <?php endif; ?>

    <footer>
        <p><a href="http://github.com/henrist/gallery">HSw Gallery</a> - made by <a href="http://hsw.no">Henrik Steen</a></p>
    </footer>
</body>
</html>