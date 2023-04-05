---
sidebar_position: 1
---
# Download Count

Download count tracks the number of times a file has been downloaded/visited/opened. It will be saved in a persistent redis database (`-v redissave:/var/lib/redis/`).
Folders will not be tracked.

Files are tracked based on their (full) file path. Therefore renaming a file will change/reset the download count.


:::info
This feature is **enabled** by default. 
:::

