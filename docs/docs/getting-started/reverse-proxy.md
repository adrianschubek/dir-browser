---
sidebar_position: 2
---
# Reverse Proxy

For production use, you should use a reverse proxy like [nginx](https://nginx.org/), [traefik](https://traefik.io/traefik/) or [Apache](https://httpd.apache.org/) to serve the directory browser. This has many advantages, like SSL support, caching and more.

Here are some configurations for different reverse proxies:

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

<details>
<summary>nginx</summary>

```nginx
server {
  listen 80;
  listen 443 ssl;
  server_name domain.tld;

  location / {
    proxy_pass http://127.0.0.1:8080/;
    proxy_set_header Host $host;
    proxy_set_header X-Real-IP $remote_addr;
  }

  ssl_certificate /path/to/cert.pem;
  ssl_certificate_key /path/to/cert.key;
}
```

</details>

<details>
<summary>traefik</summary>
TODO
</details>