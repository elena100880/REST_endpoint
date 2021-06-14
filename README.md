# REST_endpoint
test exercise


**Launch with Docker in Linux**:

Execute commands:
+ `git clone https://github.com/elena100880/REST_endpoint`

in project folder:
+ `compose install`
+ `docker-compose up`

Then open localhost/index.php/<route_path> in your browser.


***
**Dockerfile**

Docker-compose.yaml file in the project folder uses an official image php:8.0-apache.

Also, you can use my Dockerfile from rep: https://github.com/elena100880/dockerfile.

It includes php:8.0-apache official image and the installation of Composer, XDebug, Nano, some PHP extensions and enabling using mod rewrite (so you can skip index.php in URLs).

Execute the following commands:

  + `docker build . -t php:8.0-apache-xdebug` in the folder with Dockerfile.
  + `docker run -p -d 80:80 -v "$PWD":/var/www -w="/var/www" php:8.0-apache-xdebug composer install` in the project folder.
  + `docker run -d -p 80:80 -v "$PWD":/var/www --name oo php:8.0-apache-xdebug` in the project folder to launch the project.

***
**DataBase**

For easier using  **/var/data.db** file is added to the repository. 
SQLite DB used.