#!/bin/bash

# Debounce time in seconds
DEBOUNCE_TIME=$WORKER_WATCH_DEBOUNCE

# Declare an associative array to track timers
declare -A file_timers

process_event() {
  local file="$1"
  # if just a regular file or folder edited -> scan it
  # if if it is a dbmeta.yml file -> scan the whole folder by stripping the .dbmeta.yml suffix
  if [[ "$file" == *".dbmeta.yml" ]]; then
    file="${file%.dbmeta.yml}"
  fi

  php /var/www/html/worker.php --scan "$file"
}

# watch mounted /var/www/html/public for changes
inotifywait -q -m -r -e create,attrib,delete,modify,move --format '%w%f' /var/www/html/public | while read -r file; do

  # Cancel any existing debounce timer for this path
  if [[ -n "${file_timers[$file]}" ]]; then
    # echo "Cancel indexing for $file due to new event (inotify)"
    kill "${file_timers[$file]}" 2>/dev/null
  fi

  # echo "Queueing event for $file (inotify)"
  # Start a new debounce timer for this path
  (
    sleep "$DEBOUNCE_TIME"
    process_event "$file"
  ) &

  # Record the PID of the background process
  file_timers["$file"]=$!
done
