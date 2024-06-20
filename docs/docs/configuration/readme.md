---
sidebar_position: 4
---
# Readme Rendering

If there is a `readme.md` (case insensitive) (can be configured) file in the current directory, it will be rendered. GitHub flavored markdown is supported.

:::info
By default unsafe HTML inside markdown (such as `<script>`) will be escaped. You can allow any html by enabling the option. However, allowing untrusted HTML can result in XSS attacks.
:::



import EnvConfig from '@site/src/components/EnvConfig';

<EnvConfig name="README_RENDER|README_NAME|README_FIRST|ALLOW_RAW_HTML" init="true|readme.md|false|false" values="true,false|<string>|true,false|true,false" versions="1.1|3.2|3.2|1.1" desc="|The case-insensitive file name which should be rendered|Render the readme above the file tree instead of below it.|" />