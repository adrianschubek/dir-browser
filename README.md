<div align="center">

![](dir-browser.png)
![](p1.png)
![](p2.png)

</div>

## Demo

https://bp.adriansoftware.de

## Features
- **Download count** for all files
- File stats like modification dates and sizes
- Light and Darkmode
- Extremly **fast** file serving through **nginx**
- **Low memory** footprint (~30MB)
- Easy setup using single **Docker** image

## Installation

```
docker run -p 8080:80 -v /my/local/folder:/var/www/html/public:ro -v redissave:/var/lib/redis/ -it adrianschubek/dir-browser:dev
```

## Roadmap
- [ ] Password protection per file/folder
- [ ] File hashes

<!-- Directory Browser / Lister drop-in

docker run --rm --name dir -p 8080:80 -v /home/adrian/Uni/BP/frontend:/var/www/html/public:ro  -it $(docker build -q .)
docker run --restart always --rm --name dir -p 8080:80 -v /home/adrian/Uni/BP/frontend:/var/www/html/public:ro  -it adrianschubek/dir-browser

docker run --rm --name dir -p 8080:80 -v /home/adrian/Uni/BP/frontend:/var/www/html/public:ro -v redissave:/var/lib/redis/  -it $(docker build -q -f Dockerfile .) -->