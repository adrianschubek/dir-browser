---
sidebar_position: 1
---
# Download Counter

Download count tracks the number of times a file has been downloaded/visited/opened. It will be saved in a persistent redis database (`-v redissave:/var/lib/redis/`).
Folders itself will not be tracked. In [batch downloads](batch.mdx) the download count will intuitively be increased for each (nested) file in the batch.

Files are tracked based on their (full) file path. Therefore renaming a file will change/reset the download count.


import EnvConfig from '@site/src/components/EnvConfig';

<EnvConfig name="DOWNLOAD_COUNTER" init="true" values="true,false" versions="0.1"/>