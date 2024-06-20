---
sidebar_position: 1
---

# Installation

Use the image from [Docker Hub](https://hub.docker.com/r/adrianschubek/dir-browser/tags).

```
docker run -d -p 8080:80 -v /my/local/folder:/var/www/html/public:ro -v rdb:/var/lib/redis/ adrianschubek/dir-browser
```

where `/my/local/folder` is the local folder you want to serve and `8080` is the port you want to use.

Access the directory browser at `http://localhost:8080`.

:::tip
You may want to run the container with the `--restart always` flag to ensure that the container is always running even after a system reboot.
:::

## Configuration

To set configuration options you can use `docker run`
- with the `-e THEME=cosmo -e DATE_FORMAT=local` arguments
- or load it from an environment file using [`--env-file .env`](https://docs.docker.com/reference/cli/docker/container/run/#env).
```ini title=".env"
THEME=cosmo
DATE_FORMAT=local
```

## Docker Compose

You can also use [Docker Compose](https://docs.docker.com/compose/) to run the container.

```yaml title="docker-compose.yml"
version: 3
services:
  dir-browser:
    image: adrianschubek/dir-browser:latest
    restart: always
    ports:
      - 8080:80
    volumes:
      - /my/local/folder:/var/www/html/public:ro
      - rdb:/var/lib/redis/
    environment: # here you can set configuration options (see configuration section for more details)
      - THEME=cosmo
      - DATE_FORMAT=local
```

:::info
If you are using a [reverse proxy](/getting-started/reverse-proxy.md) you may want the dir-browser to be accessible *only* from the reverse proxy. 
In this case you should modify the `ports` section and add `127.0.0.1` before the port number.
```yaml
    ports: 
    // red-next-line
     - 8080:80
    // green-next-line
     - 127.0.0.1:8080:80
```
:::

## Updating

To update the container, simply pull the latest image from Docker Hub, stop the container and start it again.

:::tip
Find the container ID using `docker ps`.
:::

```
docker pull adrianschubek/dir-browser
docker rm -f <containerID>
docker run -d -p 8080:80 -v /my/local/folder:/var/www/html/public:ro -v rdb:/var/lib/redis/ adrianschubek/dir-browser
```
