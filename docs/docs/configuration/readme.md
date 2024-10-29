---
sidebar_position: 4
slug: readme
---
# Readme Rendering

If there is a `readme.md` (case insensitive) (can be configured) file in the current directory, it will be rendered. GitHub flavored markdown is supported. See [Demo Page](https://dir-demo.adriansoftware.de/examples/).

### `.dbmeta.md` file
You can specify a `.dbmeta.md` file in a directory to render it while also hiding it from the file tree, API and downloads at the same time. A `.dbmeta.md` file will override a existing `readme.md` file. See [Demo](https://dir-demo.adriansoftware.de/examples/markdown/).

:::info
By default unsafe HTML inside markdown (such as `<script>`) will be escaped. You can allow any html by enabling the option. However, allowing untrusted HTML can result in XSS attacks.
:::



import EnvConfig from '@site/src/components/EnvConfig';

<EnvConfig name="README_RENDER|README_NAME|README_FIRST|ALLOW_RAW_HTML|README_META" init="true|readme.md|false|false|true" values="true,false|<string>|true,false|true,false|true,false" versions="1.1|3.2|3.2|1.1|3.5" desc="|The case-insensitive file name which should be rendered|Render the readme above the file tree instead of below it.||Renders a .dbmeta.md file if it exists" />