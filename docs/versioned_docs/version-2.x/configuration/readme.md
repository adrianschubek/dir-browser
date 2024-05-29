---
sidebar_position: 4
---
# Readme rendering

If there is a `readme.md` (case insensitive) file in the current directory, it will be rendered. GitHub flavored markdown is supported.

:::info
By default unsafe HTML inside markdown (such as `<script>`) will be escaped. You can allow any html by setting the environment variable `ALLOW_RAW_HTML` to `true` when starting the container. However, allowing untrusted HTML can result in XSS attacks.
:::

This feature is enabled by default. To disable this, set the environment variable `NO_README_RENDER` to `true` when starting the container.

```
docker run -d -p 8080:80 -v /my/local/folder:/var/www/html/public:ro -e NO_README_RENDER=true -v redissave:/var/lib/redis/ -it adrianschubek/dir-browser
```