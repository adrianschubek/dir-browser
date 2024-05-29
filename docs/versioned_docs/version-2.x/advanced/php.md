---
sidebar_position: 2
---

# PHP

## display_errors

By default `display_errors` is set to `Off` in the `php.ini` file.

To change it, set the environment variable `DISPLAY_ERRORS` when starting the container.

```
docker run -d -p 8080:80 -v /my/local/folder:/var/www/html/public:ro -e DISPLAY_ERRORS=On -v redissave:/var/lib/redis/ -it adrianschubek/dir-browser
```

## memory_limit

By default `memory_limit` is set to `128M` in the `php.ini` file.

To change it, set the environment variable `MEMORY_LIMIT` when starting the container.

```
docker run -d -p 8080:80 -v /my/local/folder:/var/www/html/public:ro -e MEMORY_LIMIT=256M -v redissave:/var/lib/redis/ -it adrianschubek/dir-browser
```
