

This is reimplementaion of [Open Clipart Library](http://openclipart.org/) web site (using [Slim](http://www.slimframework.com/) and [Twig](http://twig.sensiolabs.org/))

# Installation

    * Clone the ocal repo: git clone --recursive git://github.com/openclipart/openclipart.git
    * Installing the required packages: sudo apt-get install node-less node-uglify php5-mysql
    * Create the db/user that ocal needs
    * Create the tables: mysql> source /patch/to/openclipart/resources/scripts/sql/schema.sql
    * Edit the config file: cp config.example.json config.json, then edit the config.json base on your db config

# TODO

* NSFW(Not Safe For Work) checkbox in file upload
* Refactor file storage folder structure
