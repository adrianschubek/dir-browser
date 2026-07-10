---
sidebar_position: 2
---

# PHP

## display_errors

By default `display_errors` is set to `Off` in the `php.ini` file.

## memory_limit

By default `memory_limit` is set to `-1` (unlimited) in the `php.ini` file.

## max_execution_time

By default `max_execution_time` is set to `600` seconds in the `php.ini` file.


import EnvConfig from '@site/src/components/EnvConfig';

<EnvConfig name="DISPLAY_ERRORS|MEM_LIMIT|MAX_EXEC_TIME" init="Off|-1|600" values="On,Off|<size>|<seconds>"/>