# Redis

Redis is used to store the [download counter](./../configuration/download-count.md) data. If the download counter is disabled, Redis will not be loaded/started at all.

Counters are stored with the request path as key (relative to the mounted folder), for example `/reports/2024.pdf`.

### Access

Sometimes it is useful to access the Redis instance directly e.g. for resetting a counter. This can be done using the `redis-cli` tool inside the container.

1. First create a shell. Replace `<name>` with the name of the container.
```bash
docker exec -it <name> sh
```
2. Start the Redis CLI.
```bash
redis-cli
```

See https://redis.io/docs/latest/commands/ for a list of available commands.

### List all stored counters
For small datasets you can use `KEYS`, but note that it can be slow on large databases.

```bash title="$> KEYS *"
...
25) "/src/index.php"
26) "/docs/versioned_docs/version-1.x/configuration/download-count.md"
27) "/docs/static/img/pw1.png"
28) "/docs/versioned_docs/version-1.x/development/_category_.json"
29) "/docs/versioned_docs/version-1.x/getting-started/installation.md"
30) "/docs/static/img/cerulean_dark.png"
31) "/docs/versioned_sidebars/version-1.x-sidebars.json"
32) "/docs/versioned_docs/version-2.x/configuration/highlight-updated.md"
33) "/docs/static/img/secure.svg"
34) "/docs/src/pages/index.module.css"
...
```
### Read a counter
```bash title="$> GET /example.txt"
123
```
### Reset/Modify a counter
```bash title="$> SET /example.txt 0"
OK
```