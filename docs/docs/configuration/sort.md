---
sidebar_position: 3
---

# Sort

By default files and folders are sorted by name using natural sort. You can reverse it by setting the `REVERSE_SORT` environment variable.

```
docker run -d -p 8080:80 -v /my/local/folder:/var/www/html/public:ro -e REVERSE_SORT=1 -v redissave:/var/lib/redis/ -it adrianschubek/dir-browser
```