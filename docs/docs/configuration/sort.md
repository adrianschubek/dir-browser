---
sidebar_position: 3
---

# Search & Sorting

![image](https://github.com/adrianschubek/dir-browser/assets/19362349/da17df62-c1ac-4877-bd19-7018ba6af1df)

Click on the column header to sort by that column. Click again to reverse the sort order.

Click on the search icon to show a search input field. The search is case-insensitive and searches the current folder for the search term in the file name.

### Serverside sorting

By default files and folders are sorted by name using natural sort. 

import EnvConfig from '@site/src/components/EnvConfig';

<EnvConfig name="REVERSE_SORT" init="false" values="true,false" versions="1.0"/>