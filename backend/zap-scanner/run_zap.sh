#!/bin/bash
set -e

TARGET_URL="$1"
OUTPUT_DIR="${2:-/zap/wrk}"  # Default to /zap/wrk if not given

if [ -z "$TARGET_URL" ]; then
  echo "Usage: run_zap.sh <target_url> [output_dir]"
  exit 1
fi

echo "Received target: $TARGET_URL"
echo "Reports will be saved to: $OUTPUT_DIR"

# Ensure output dir exists
mkdir -p "$OUTPUT_DIR"

# Define output file paths
REPORT_JSON="/zap/wrk/report.json"
REPORT_HTML="/zap/wrk/report.html"



# Start the full scan and export reports
zap-full-scan.py \
  -t "$TARGET_URL" \
  -J "$REPORT_JSON" \
  -r "$REPORT_HTML" \
  -d

sleep 5  # Let daemon stabilize

ZAP_PORT=$(cat /zap/zap.port)

# Wait until passive scanning is done
while true; do
  RECORDS_LEFT=$(curl -s "http://localhost:$ZAP_PORT/JSON/pscan/view/recordsToScan/" | jq -r '.recordsToScan')
  echo "Waiting for passive scan to finish... ($RECORDS_LEFT left)"
  if [ "$RECORDS_LEFT" -eq 0 ]; then
    break
  fi
  sleep 2
done

# Print alerts to terminal
echo -e "\n================= JSON ALERTS =================\n"
curl -s "http://localhost:$ZAP_PORT/JSON/core/view/alerts/?start=0&count=9999" | jq .
echo -e "\n================= END OF ALERTS ===============\n"

# Indicate saved files
echo -e "\n✅ Reports written to:"
echo "- JSON: $REPORT_JSON"
echo "- HTML: $REPORT_HTML"
