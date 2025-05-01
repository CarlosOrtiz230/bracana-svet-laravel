#!/bin/bash
set -e

TARGET_URL="$1"

if [ -z "$TARGET_URL" ]; then
  echo "Usage: run_nikto.sh <target_url>"
  exit 1
fi

echo "Received target: $TARGET_URL"

TIMESTAMP=$(date +"%Y-%m-%d_%H-%M-%S")

./nikto.pl -host "$TARGET_URL" -Tuning 1234567890abcde -Format json -output "/nikto/wrk/nikto_report_${TIMESTAMP}.json"
./nikto.pl -host "$TARGET_URL" -Tuning 1234567890abcde -Format htm -output "/nikto/wrk/nikto_report_${TIMESTAMP}.html"

if [ -n "$HOST_UID" ] && [ -n "$HOST_GID" ]; then
  chown "$HOST_UID:$HOST_GID" /nikto/wrk/nikto_report_"$TIMESTAMP".*
fi

echo -e "\n✅ Reports saved in /nikto/wrk/"
