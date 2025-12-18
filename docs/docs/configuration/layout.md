# Layout

## `LAYOUT`

Controls what happens when a user clicks a file in the directory listing.

Supported values:

- `popup` (default): Opens a preview dialog. The dialog shows file metadata (for example size, timestamps, and configured hashes) and provides actions like downloading or opening the file.
- `basic`: Skips the dialog and starts downloading the file immediately. This is the pre-v4.0 behavior.


import EnvConfig from '@site/src/components/EnvConfig';

<EnvConfig name="LAYOUT|OPEN_NEW_TAB|HIGHLIGHT_UPDATED|TRANSITION|TITLE" init="popup|false|true|false|dir-browser" values="basic,popup|true,false|true,false|true,false|<string>" desc="|Open file in a new tab.|When a file has been changed in the last 48 hours its last modified date gets bold.|Show fade animation when navigating.|Browser tab title prefix (the current path is appended)." versions="3.17|3.2|2.0|3.3.2|4.3"/>