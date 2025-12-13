---
sidebar_position: 3
---

# Search & Sorting

![image](search2.gif)

Click on the column header to sort by that column. Click again to reverse the sort order.

Click on the search icon to open the search input field and choose an engine to search the current folder and all descendants of the current folder.

## Engines

### Simple search

Basic string-matching search engine. Enabled by default.

### Glob-based search

More powerful search engine for advanced users. It is simple to use and faster than the regex engine. Enabled by default.

| Pattern        | Description                                                                                                                                |
|----------------|--------------------------------------------------------------------------------------------------------------------------------------------|
| `*`           | Matches zero or more characters.                                                                                                           |
| `?`           | Matches exactly one character (any character).                                                                                             |
| `[...]`       | Matches one character from a group of characters. If the first character is `!`, it matches any character *not* in the group.              |
| `\`           | Escapes the following character.                                                             |

:::info
Expansion `{a,b,c}` is not available because it is not supported by the underlying base image (Alpine Linux).
Globstar `**` is not supported by PHP natively.
:::

> [Learn more](https://en.wikipedia.org/wiki/Glob_(programming))

### Regex-based search

This engine is the most powerful but slower than the glob engine. It is useful for complex searches. Disabled by default.

> [Learn more](https://en.wikipedia.org/wiki/Regular_expression)

import EnvConfig from '@site/src/components/EnvConfig';

<EnvConfig name="SEARCH|SEARCH_ENGINE|SEARCH_MAX_DEPTH|SEARCH_MAX_RESULTS|REVERSE_SORT" init="true|s,g|25|100|false" values="true,false|s,g,r|integer|integer|true,false" versions="3.7|3.8|3.7|3.7|1.0" desc="Enables or disables the search functionality|s=simple, g=glob, r=regex. Multiple values seperated using commas.|Maximum recursive search depth (simple and regex engine only)|Maximum number of results in a single request|By default files and folders are sorted by name using natural sort."/>
