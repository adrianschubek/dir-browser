---
sidebar_position: 1
---

# Public Root

dir-browser serves `/var/www/html/public` by default. `PUBLIC_ROOT` can select
that directory or a nested directory as the browser-visible filesystem root:

```bash
docker run -d -p 8080:80 \
  -e PUBLIC_ROOT=/var/www/html/public/releases/current \
  -v /my/shared/content:/var/www/html/public:ro \
  adrianschubek/dir-browser
```

`PUBLIC_ROOT` must be an absolute, normalized path at or below
`/var/www/html/public`. It may be a symbolic link, but its resolved target must
remain inside that mounted content tree. Symlinks below the selected root are
subject to the same boundary. Invalid, missing, inaccessible, or out-of-bound
roots are never served; content requests return `503` while the health endpoint
remains available.

`BASE_PATH` and `PUBLIC_ROOT` solve different problems: `BASE_PATH` changes the
URL prefix used behind a reverse proxy, while `PUBLIC_ROOT` changes the
filesystem directory exposed at URL `/`.

## git-sync

Mount the whole git-sync shared volume so both the `link` symlink and its
worktree target exist in dir-browser's mount namespace, then select the link:

```yaml
apiVersion: apps/v1
kind: Deployment
metadata:
  name: files
spec:
  template:
    spec:
      containers:
        - name: git-sync
          image: registry.k8s.io/git-sync/git-sync:v4.7.1
          args:
            - --root=/git
            - --link=link
          volumeMounts:
            - name: git-data
              mountPath: /git
        - name: dir-browser
          image: adrianschubek/dir-browser:latest
          env:
            - name: PUBLIC_ROOT
              value: /var/www/html/public/link
          volumeMounts:
            - name: git-data
              mountPath: /var/www/html/public
              readOnly: true
      volumes:
        - name: git-data
          emptyDir: {}
```

Mounting only the symlink is insufficient when its target is not visible in the
dir-browser container. Link changes are resolved again on every request, so
git-sync can publish a new worktree without restarting dir-browser. There is a
small unavoidable race if the link changes after PHP authorizes a file but
before Nginx opens it; both versions remain constrained to the mounted tree and
selected repository root.

## Permissions

dir-browser does not change ownership or modes on mounted user data. Regular
files owned by git-sync's UID/GID `65533` with mode `0744` are readable by the
current root PHP and Nginx processes when their parent directories are
traversable. Directories need execute permission in addition to read
permission—normally mode `0755`.

If a deployment drops root's DAC override capability, uses root-squashed
storage, or applies SELinux/AppArmor restrictions, give every ancestor
directory execute permission, run dir-browser with a compatible identity, or
configure the appropriate Kubernetes security context/storage policy.

import EnvConfig from "@site/src/components/EnvConfig";

<EnvConfig
  name="PUBLIC_ROOT"
  init="/var/www/html/public"
  values="/var/www/html/public,<nested path>"
  desc="Filesystem root exposed by dir-browser; must remain inside the content mount."
/>
