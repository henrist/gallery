# Gallery
This application is a simple gallery for viewing images stored on disk without any database.

Feel free to provide a pull request.

## Requirements
* PHP 5.3
* Composer
* exif-support
* gd

## Installation
* Pull from git
* Copy `app/config.example.php` to `app/config.php` and modify it
* Create the directory `cache` and make sure it is owned by the user running the php script or has chmod 777
* Run `composer install`

### Apache
* Adjust `public/.htaccess`
* Open your browser!

### Built-in PHP server
* You can also test this by going to `public` folder and running `php -S 0.0.0.0:8080 index.php` and then going to http://servername:8080/

### Other
* Make sure index.php receives all 404 requests

## TODO
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
