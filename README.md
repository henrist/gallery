Gallery
=======

Under development...

Feel free to provide a pull request.


Introduction
------------

This application is a simple gallery for viewing images stored on disk without any database.


Requirements
------------

* PHP 5.3
* Composer
* exif-support


Installation
------------

* Pull from git
* Change settings in config.php (the web directory must currently not be in a subdirectory)
* Create the directory 'cache' and make sure it is owned by the user running the php script or has chmod 777
* Run `composer install`
* Open your browser!


TODO
----

* Basic html+css page
* Deleting old cache not in use
* Showing description for a folder (where you can put credits etc)
* Lightbox or something similar?
* Viewing EXIF-data
* Error handling
* Documentation (for code)
* Support symlink paths
* Pagination or another smart solution (viewing images as scrolled?)
* Folder/file filtering
* Optimized thumb creation
* Access restriction using REMOTE_USER-env (both allow by deafult and deny by default)
* Allow placing in a subdirectory
