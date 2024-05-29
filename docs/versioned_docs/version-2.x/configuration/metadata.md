---
sidebar_position: -1
---

import Image from "@theme/IdealImage";

# Metadata

Files and folder can be enriched with metadata and displayed. Metadata is stored in a file called `<name>.dbmeta.json`, where `<name>` is the exact file (including extension) or folder name. Put it in the same folder as the target file or folder. Metadata is stored in a JSON format specified below. 

The `*.dbmeta.json` files are hidden from the user and cannot be viewed.

<!-- This feature is enabled by default. To disable this, set the environment variable `NO_METADATA` to `true` when starting the container. -->

<Image img={require("@site/static/img/metadata.png")} />

```json title="/foo   bar/cool project.dbmeta.json"
{
  "description": "A short project description ⭐",
  "labels": ["danger:Laravel", "primary:PHP 8", "dark:Hot 🔥"],
  "hidden": false,
  "password": "mysecurepassword"
}
```
<!-- TODO: "password": "mysecurepassword" -->

## Properties

#### Description

A short description of the file or folder. This is displayed in the file tree. 

> Default is empty.

#### Labels

Labels always start with a style and a colon `:`, followed by the label text. The following styles are available:
- `primary`
- `secondary`
- `success`
- `danger`
- `warning`
- `info`
- `light`
- `dark`

> Default is empty.

#### Hidden

If set to `true`, the file or folder is hidden from the file tree. However, it can still be accessed by URL directly. Can be combined with password protection.

> Default is `false`.

#### Password

See [Password Protection](password.mdx).

> Default is empty.