# Highlight updated files

When a file has been changed in the last 48 hours the grey bar on the right will turn blue.

:::info
This feature is **enabled** by default. 

To disable it, set the environment variable `HIGHLIGHT_UPDATED` to `false` when starting the container. 
```
docker run -d -p 8080:80 -v /my/local/folder:/var/www/html/public:ro -e HIGHLIGHT_UPDATED=false -v redissave:/var/lib/redis/ -it adrianschubek/dir-browser
```
:::