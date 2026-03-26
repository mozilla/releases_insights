#!/bin/bash

# Version 1.1 - Added column alignment and red color for dump statements
TARGET="app/views/templates/"

# 1. Run grep and capture raw output
RAW_MATCHES=$(grep -rnE "\{\{-? ?dump|{%-? ?dump" "$TARGET" 2>/dev/null)

if [ -n "$RAW_MATCHES" ]; then
    # 2. Count unique files found
    FILE_COUNT=$(echo "$RAW_MATCHES" | cut -d: -f1 | sort -u | wc -l | xargs)

    # 3. Format the output using awk with column padding and color
    # \033[31m is Red, \033[0m is Reset
    echo -e "$RAW_MATCHES" | awk -F ':' '{
        content = substr($0, index($0, $3));
        printf "Line %-4s %-52s \033[31m%s\033[0m\n", $2, $1, content
    }'

    # 4. Handle pluralization with Bold Red Error message
    if [ "$FILE_COUNT" -eq 1 ]; then
        echo -e "\e[1;31mError: dump() found in the file listed above.\e[0m"
    else
        echo -e "\e[1;31mError: dump() found in the $FILE_COUNT files listed above.\e[0m"
    fi

    exit 1
else
    echo -e "\e[1;32mNo dump() calls found in twig files.\e[0m"
    exit 0
fi