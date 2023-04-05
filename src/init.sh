# initialize configs
utpp "/etc/nginx/**;/etc/supervisor/**;/usr/local/etc/php*/**;/var/www/html/index.php" && /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf