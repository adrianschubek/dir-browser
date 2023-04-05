---
sidebar_position: 1
---

# Installation

Use the image from [Docker Hub](https://hub.docker.com/r/adrianschubek/dir-browser/tags).

```
docker run -d -p 8080:80 -v /my/local/folder:/var/www/html/public:ro -v redissave:/var/lib/redis/ -it adrianschubek/dir-browser
```

where `/my/local/folder` is the local folder you want to serve and `8080` is the port you want to use.

Access the directory browser at `http://localhost:8080`.

:::tip
Also make sure to run the container with the `--restart always` flag to ensure that the container is always running even after a system reboot.
:::

### Multiple instances

Make sure to change the _port_ when running multiple instances.

## Updating

To update the container, simply pull the latest image from Docker Hub, stop the container and start it again.

:::tip
Find the container ID using `docker ps`.
:::

```
docker pull adrianschubek/dir-browser
docker stop <containerID>
docker run -d -p 8080:80 -v /my/local/folder:/var/www/html/public:ro -v redissave:/var/lib/redis/ -it adrianschubek/dir-browser
```
