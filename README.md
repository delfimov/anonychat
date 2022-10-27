# AnonyChat

A websocket secure (wss) chat without registration, logging, chat history, trackers, etc.

⚠️ This project is not finished and is not ready for production use. ⚠️

Based on [walkor/workerman](https://github.com/walkor/workerman) asynchronous event driven PHP socket framework.

Working example: [anonychat.elfimov.ru](https://anonychat.elfimov.ru/).


## Key features
* Just because you're not paranoid does not mean you're not being watched
* As simple as possible (KISS + YAGNI on steroids)
* No unnecessary dependencies
* Messages are not stored on the server
* Messages are only visible to connected users
* All user names are unique
* All room names are unique
* Only one connection per username
* User can only connect to one room


## How to deploy

1. Prerequesites: Linux Server, Nginx, PHP 7.1+, Composer.
1. Copy the project files or `git clone` to your server.
1. Run `composer install --no-dev --prefer-dist --optimize-autoloader --classmap-authoritative` to install workerman and wind up the autoload.
1. Set up Nginx configuration for your domain.
1. Get SSL certificates ([Let's Encript](https://letsencrypt.org/) is ok) and make symlinks to `./var/certificates`.
1. Copy `./config/config.php.example` to `./config/config.php` and edit port number, certificate filenames, server domain.
1. Open a port for wss on your server (`firewall-cmd --zone=public --permanent --add-port=9889/tcp && firewall-cmd --reload`.
1. Run `php server.php start`.