---
sidebar_position: 2
---

# Development

Contributions are welcome. To run dir-browser locally from source:

1. Clone the repository: https://github.com/adrianschubek/dir-browser
2. Build and run the image from the project root. Replace `/some/local/folder` with a local folder you want to serve.

```
docker run --rm --name dir -p 8080:80 -v /some/local/folder:/var/www/html/public:ro -v redissave:/var/lib/redis/  -it $(docker build -q -f Dockerfile .)
```
It may take a few minutes to build the image.

3. Open http://localhost:8080

After making code changes, rebuild and re-run the container.

This project uses special syntax from [utpp](https://github.com/adrianschubek/utpp), a CLI tool that preprocesses templates and can execute JavaScript at build time. If you want to learn more, see https://utpp.adriansoftware.de/.
