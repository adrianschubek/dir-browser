![](dir-browser.png)

Directory Browser / Lister drop-in

docker run --rm --name dir -p 8080:80 -v /home/adrian/Uni/BP/frontend:/var/www/html/public:ro  -it $(docker build -q .)
docker run --restart always --rm --name dir -p 8080:80 -v /home/adrian/Uni/BP/frontend:/var/www/html/public:ro  -it adrianschubek/dir-browser

docker run --rm --name dir -p 8080:80 -v /home/adrian/Uni/BP/frontend:/var/www/html/public:ro -v redissave:/var/lib/redis/  -it $(docker build -q -f Dockerfile .)