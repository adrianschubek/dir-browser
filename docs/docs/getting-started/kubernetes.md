---
sidebar_position: 3
---

# Kubernetes

Run dir-browser in Kubernetes with a persistent content volume and a separate
volume for Redis data.

The example below creates a `PersistentVolumeClaim`, a single-replica
`Deployment`, and a `ClusterIP` `Service`:

```yaml title="dir-browser.yaml"
apiVersion: v1
kind: PersistentVolumeClaim
metadata:
  name: dir-browser-content
spec:
  accessModes:
    - ReadWriteOnce
  resources:
    requests:
      storage: 10Gi
---
apiVersion: apps/v1
kind: Deployment
metadata:
  name: dir-browser
spec:
  replicas: 1
  selector:
    matchLabels:
      app: dir-browser
  template:
    metadata:
      labels:
        app: dir-browser
    spec:
      containers:
        - name: dir-browser
          image: adrianschubek/dir-browser:latest
          ports:
            - name: http
              containerPort: 80
          env:
            - name: THEME
              value: cosmo
            - name: DATE_FORMAT
              value: local
          readinessProbe:
            httpGet:
              path: /__health
              port: http
            initialDelaySeconds: 5
            periodSeconds: 10
          livenessProbe:
            httpGet:
              path: /__health
              port: http
            initialDelaySeconds: 15
            periodSeconds: 30
          volumeMounts:
            - name: content
              mountPath: /var/www/html/public
              readOnly: true
            - name: redis-data
              mountPath: /var/lib/redis
      volumes:
        - name: content
          persistentVolumeClaim:
            claimName: dir-browser-content
        - name: redis-data
          emptyDir: {}
---
apiVersion: v1
kind: Service
metadata:
  name: dir-browser
spec:
  selector:
    app: dir-browser
  ports:
    - name: http
      port: 80
      targetPort: http
```

Apply the manifest:

```bash
kubectl apply -f dir-browser.yaml
```

For a local test, forward port `8080` to the service:

```bash
kubectl port-forward service/dir-browser 8080:80
```

Open `http://localhost:8080`.

## Content volume

Mount the directory to expose at `/var/www/html/public`. The mount can be
read-only because dir-browser does not modify served files.

The example uses `ReadWriteOnce`, which is suitable for one replica on most
storage providers. To run multiple replicas, use storage that supports
`ReadOnlyMany` or `ReadWriteMany`, and ensure every pod sees the same content.

Redis data in the example uses `emptyDir`, so download counters and other cached
state are reset when the pod is replaced. Replace it with a persistent volume
when that state must survive pod recreation.

## Ingress

Expose the service with an Ingress controller and TLS as appropriate for your
cluster:

```yaml title="ingress.yaml"
apiVersion: networking.k8s.io/v1
kind: Ingress
metadata:
  name: dir-browser
spec:
  ingressClassName: nginx
  rules:
    - host: files.example.com
      http:
        paths:
          - path: /
            pathType: Prefix
            backend:
              service:
                name: dir-browser
                port:
                  number: 80
```

When publishing dir-browser below a URL prefix instead of `/`, configure
[`BASE_PATH`](./reverse-proxy.md#subfolderdifferent-basepath).

## git-sync sidecar

A `git-sync` sidecar can update the served content without rebuilding the
image. Mount the complete shared volume into both containers and select the
published `link` with `PUBLIC_ROOT`:

```yaml
spec:
  template:
    spec:
      containers:
        - name: git-sync
          image: registry.k8s.io/git-sync/git-sync:v4.7.1
          args:
            - --repo=https://github.com/example/content.git
            - --ref=main
            - --root=/git
            - --link=link
            - --period=60s
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
            - name: redis-data
              mountPath: /var/lib/redis
      volumes:
        - name: git-data
          emptyDir: {}
        - name: redis-data
          emptyDir: {}
```

Mounting only the symlink is not sufficient because its target must also be
visible inside the dir-browser container. See
[Public Root](../configuration/public-root.md#git-sync) for the complete
example, path restrictions, and permission notes.

## Configuration and secrets

All dir-browser settings can be supplied through `env`, a `ConfigMap`, or a
`Secret`. Store passwords and authentication values in a Kubernetes `Secret`
rather than directly in the Deployment manifest.

See [Installation](./installation.md#configuration) for configuration basics.
