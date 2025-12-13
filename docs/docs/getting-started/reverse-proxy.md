---
sidebar_position: 2
---
# Reverse Proxy

For production, put dir-browser behind a reverse proxy like [nginx](https://nginx.org/), [traefik](https://traefik.io/traefik/) or [Apache](https://httpd.apache.org/). This gives you TLS termination, central logging, and optional caching.

Here are some basic configurations for different reverse proxies. You may need to adjust them to your needs.

<details>
<summary>nginx</summary>

```nginx
server {
  listen 80;
  listen 443 ssl;
  server_name domain.tld;

  location / {
    proxy_pass http://127.0.0.1:8080/;
  }

  ssl_certificate /path/to/cert.pem;
  ssl_certificate_key /path/to/cert.key;
}
```

</details>

<details>
<summary>Apache</summary>

```apache
<VirtualHost *:80>
  ServerName domain.tld

  ProxyPass / http://127.0.0.1:8080/
  ProxyPassReverse / http://127.0.0.1:8080/
</VirtualHost>

<VirtualHost *:443>
  ServerName domain.tld

  ProxyPass / http://127.0.0.1:8080/
  ProxyPassReverse / http://127.0.0.1:8080/

  SSLEngine on
  SSLCertificateFile cert.pem
  SSLCertificateKeyFile cert.key
</VirtualHost>
```

</details>

### Subfolder/Different basepath
You can deploy the application under a subpath (base path), for example `/foobar/`. All internal links and API requests will be generated relative to that base path.

Set the `BASE_PATH` environment variable to your subpath (without the trailing slash). For example:

```bash
docker run -d -p 8080:80 -e BASE_PATH="/foobar" -v /my/local/folder:/var/www/html/public:ro -v rdb:/var/lib/redis/ -it adrianschubek/dir-browser:latest
```

<!-- import EnvConfig from '@site/src/components/EnvConfig';

<EnvConfig name="BASE_PATH" init="<empty>" values="<path>"/> -->

And you may need to modify your reverse proxy configuration. In NGINX adapt the `location` block to the following:

```nginx
  location /foobar/ {
    proxy_pass http://127.0.0.1:8080/;
  }
```

:::warning
Make sure to add the `/` at the end of the `location` line and after the `proxy_pass` URL. Otherwise the application will not work correctly.
:::