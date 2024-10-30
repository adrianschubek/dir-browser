---
sidebar_position: 3
---

# Search & Sorting

![image](search1.png)

Click on the column header to sort by that column. Click again to reverse the sort order.

Click on the search icon to open the search input field. All engines search the current folder and all descendants of the current folder.

### Simple search

Most intuitive string-matching search engine with basic functionality.

### Glob-based search

More powerful search engine for advanced users. It is simple to use and faster than the regex engine.

| Pattern        | Description                                                                                                                                |
|----------------|--------------------------------------------------------------------------------------------------------------------------------------------|
| `*`           | Matches zero or more characters.                                                                                                           |
| `?`           | Matches exactly one character (any character).                                                                                             |
| `[...]`       | Matches one character from a group of characters. If the first character is `!`, it matches any character *not* in the group.              |
| `\`           | Escapes the following character.                                                             |

:::info
Expansion `{a,b,c}` is not available because it is not supported by the underlying base image (Alpine Linux).

Globstar `**` is not supported by PHP natively and may be implemented in the future.
:::

> [Learn more](https://en.wikipedia.org/wiki/Glob_(programming))

### Regex-based search

This engine is the most powerful but slower than the glob engine. It is useful for complex searches.

> [Learn more](https://en.wikipedia.org/wiki/Regular_expression)

import EnvConfig from '@site/src/components/EnvConfig';

<EnvConfig name="SEARCH|SEARCH_ENGINE|SEARCH_MAX_DEPTH|SEARCH_MAX_RESULTS|REVERSE_SORT" init="true|simple|25|100|false" values="true,false|simple,glob,regex|integer|integer|true,false" versions="3.7|3.7|3.7|3.7|1.0" desc="Enables or disables the search functionality|Search engine to evaluate query|Maximum recursive search depth (simple and regex engine only)|Maximum number of results in a single request|By default files and folders are sorted by name using natural sort."/>
