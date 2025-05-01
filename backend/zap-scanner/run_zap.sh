#!/bin/bash
set -e

TARGET_URL="$1"

if [ -z "$TARGET_URL" ]; then
  echo "Usage: run_zap.sh <target_url>"
  exit 1
fi

echo "Received target: $TARGET_URL"

# Define output file path
REPORT_JSON="/zap/wrk/report.json"
REPORT_HTML="/zap/wrk/report.html"


# Start the full scan, also export HTML and JSON
zap-full-scan.py \
  -t "$TARGET_URL" \
  -J "$REPORT_JSON" \
  -r "$REPORT_HTML" \
  -d

# Wait a few seconds for the daemon to settle
sleep 5

# Get ZAP port
ZAP_PORT=$(cat /zap/zap.port)

# Wait until passive scanner finishes
while true; do
  RECORDS_LEFT=$(curl -s "http://localhost:$ZAP_PORT/JSON/pscan/view/recordsToScan/" | jq -r '.recordsToScan')
  echo "Waiting for passive scan to finish... ($RECORDS_LEFT left)"
  if [ "$RECORDS_LEFT" -eq 0 ]; then
    break
  fi
  sleep 2
done

# Print all alerts
echo -e "\n================= JSON ALERTS =================\n"
curl -s "http://localhost:$ZAP_PORT/JSON/core/view/alerts/?start=0&count=9999" | jq .
echo -e "\n================= END OF ALERTS ===============\n"

# Confirm report files
echo -e "\n✅ Reports written to:"
echo "- JSON: $REPORT_JSON"
echo "- HTML: $REPORT_HTML"
