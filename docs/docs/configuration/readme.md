---
sidebar_position: 4
---
# Readme rendering

If there is a `readme.md` (case insensitive) file in the current directory, it will be rendered. GitHub flavored markdown is supported.

:::info
By default unsafe HTML inside markdown (such as `<script>`) will be escaped. You can allow any html by enabling the option. However, allowing untrusted HTML can result in XSS attacks.
:::



import EnvConfig from '@site/src/components/EnvConfig';

<EnvConfig name="NO_README_RENDER|ALLOW_RAW_HTML" init="false|false" values="true,false|true,false"/>