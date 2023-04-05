---
sidebar_position: 2
---
# Development

1. Clone the repository
2. `docker run --rm --name dir -p 8080:80 -v /some/local/folder:/var/www/html/public:ro -v redissave:/var/lib/redis/  -it $(docker build -q -f Dockerfile .)`