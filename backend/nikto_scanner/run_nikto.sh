#!/bin/bash
set -e

TARGET_URL="$1"

if [ -z "$TARGET_URL" ]; then
  echo "Usage: run_nikto.sh <target_url>"
  exit 1
fi

echo "Received target: $TARGET_URL"

# Timestamp for uniqueness
TIMESTAMP=$(date +"%Y-%m-%d_%H-%M-%S")

# Define output paths
REPORT_JSON="/nikto/wrk/nikto_report_${TIMESTAMP}.json"
REPORT_HTML="/nikto/wrk/nikto_report_${TIMESTAMP}.html"

# Run Nikto with both formats
nikto -host "$TARGET_URL" \
  -Format json -output "$REPORT_JSON" \
  -Format htm -output "$REPORT_HTML"

# Indicate saved files
echo -e "\n✅ Reports written to:"
echo "- JSON: $REPORT_JSON"
echo "- HTML: $REPORT_HTML"
