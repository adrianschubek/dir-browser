# dir-browser
Directory Browser

docker run --rm --name dir -p 8080:80 -v /home/adrian/lcode/dir-browser/input:/var/www/html/public  -it $(docker build -q .)