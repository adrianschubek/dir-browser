---
sidebar_position: -1
---

import Image from "@theme/IdealImage";

# Metadata

Files and folders can be enhanced with custom metadata to provide additional context, labels, or visibility controls. Metadata is defined in a JSON file named `<filename>.dbmeta.json` (for files) or `<foldername>.dbmeta.json` (for folders), located in the same directory as the target item.

Metadata files are automatically hidden from the directory listing and cannot be accessed directly by users.

<Image img={require("@site/static/img/metadata.png")} />

```json title="/foo   bar/cool project.dbmeta.json"
{
  "description": "A short project description ⭐",
  "labels": ["danger:Laravel", "primary:PHP 8", "dark:Hot 🔥"],
  "hidden": false,
  "hash_required": false
}
```

:::info Readme Rendering
If you want to provide longer, formatted descriptions for a folder using Markdown, please refer to the [Readme Rendering](./readme.md) documentation regarding `.dbmeta.md` files.
:::

:::info Backward Compatibility
Individual file password protection via metadata files was deprecated in v4. Please use [Folder Passwords](./password.mdx) for access control.
:::

## Properties

### Description
A concise summary of the file or folder, displayed alongside the name in the file tree.

*   **Type:** `string`
*   **Default:** Empty

### Labels
An array of badges to display next to the item. Each label must follow the format `style:text`.

The following styles are supported:
- `primary`
- `secondary`
- `success`
- `danger`
- `warning`
- `info`
- `light`
- `dark`

:::warning
Labels must not contain semicolons (`;`).
:::

*   **Type:** `string[]`
*   **Default:** `[]`

### Hidden
Determines whether the item is visible in the directory listing.

:::warning Security Note
Setting `hidden` to `true` only removes the item from the UI. The file remains accessible via its direct URL. To restrict access entirely, use an [Ignore Pattern](./ignore.mdx) or [Folder Passwords](./password.mdx).
:::

*   **Type:** `boolean`
*   **Default:** `false`

### Hash Required
Enforces integrity checks by requiring a valid hash in the request URL to allow downloads. For more details, see [Integrity & Hashes](./hashes.md).

*   **Type:** `boolean`
*   **Default:** `false`

import EnvConfig from "@site/src/components/EnvConfig";

<EnvConfig name="METADATA" init="true" values="true,false" versions="3.3" desc="Enables or disables metadata parsing globally. Disabling this will also disable all features dependent on metadata, such as custom labels and descriptions." />