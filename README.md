# dir-browser
Directory Browser

docker run --rm --name dir -p 8080:80 -v /home/adrian/Uni/BP/frontend:/var/www/html/public:ro  -it $(docker build -q .)