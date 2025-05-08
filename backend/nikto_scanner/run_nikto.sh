#!/bin/bash
set -e

TARGET_URL="$1"
TIMEOUT_DURATION=300  #  5 minutes max per scan

if [ -z "$TARGET_URL" ]; then
  echo "Usage: run_nikto.sh <target_url>"
  exit 1
fi

echo "Received target: $TARGET_URL"

TIMESTAMP=$(date +"%Y-%m-%d_%H-%M-%S")
JSON_OUT="/nikto/wrk/nikto_report_${TIMESTAMP}.json"
HTML_OUT="/nikto/wrk/nikto_report_${TIMESTAMP}.html"

echo "[+] Running Nikto JSON scan..."
if ! timeout "$TIMEOUT_DURATION" ./nikto.pl -host "$TARGET_URL" -Tuning 1234567890abcde -Format json -output "$JSON_OUT"; then
  echo "[!] JSON scan failed or timed out!"
fi

echo "[+] Running Nikto HTML scan..."
if ! timeout "$TIMEOUT_DURATION" ./nikto.pl -host "$TARGET_URL" -Tuning 1234567890abcde -Format htm -output "$HTML_OUT"; then
  echo "[!] HTML scan failed or timed out!"
  exit 1
fi

if [ -n "$HOST_UID" ] && [ -n "$HOST_GID" ]; then
  chown "$HOST_UID:$HOST_GID" "$JSON_OUT" "$HTML_OUT" || echo "[!] chown failed"
fi

echo "✅ Nikto scan completed."
echo "JSON: $JSON_OUT"
echo "HTML: $HTML_OUT"
