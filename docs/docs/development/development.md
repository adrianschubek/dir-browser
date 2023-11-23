---
sidebar_position: 2
---

# Development

Contributions are welcome. If you would like to contribute, please follow the steps below.

1. Clone the repository https://github.com/adrianschubek/dir-browser
2. Run the following command in the project folder. Be sure to replace `/some/local/folder` with a valid path to a folder.

```
docker run --rm --name dir -p 8080:80 -v /some/local/folder:/var/www/html/public:ro -v redissave:/var/lib/redis/  -it $(docker build -q -f Dockerfile .)
```

3. Open http://localhost:8080 in your browser

If you make any changes to the code, you have to rerun the docker command.

This project uses special syntax from the [utpp](https://github.com/adrianschubek/utpp) project, a CLI tool to pre-process and execute JavScript inside configuration files at startup. It's created by the same author as this project. If you would like to learn more about it, please visit the [documentation](https://utpp.adriansoftware.de/).
