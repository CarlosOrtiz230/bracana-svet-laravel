#!/bin/bash
set -e

TARGET_URL="$1"
COMPLEXITY="${2:-medium}"             # Correct default for complexity
OUTPUT_DIR="${3:-/zap/wrk}"           # Correct third arg fallback



if [ -z "$TARGET_URL" ]; then
  echo "Usage: run_zap.sh <target_url> [complexity] [output_dir]"
  exit 1
fi

echo "Received target: $TARGET_URL"
echo "Complexity level: $COMPLEXITY"
echo "Reports will be saved to: $OUTPUT_DIR"

# Ensure output dir exists
mkdir -p "$OUTPUT_DIR"

# Define output file paths
REPORT_JSON="/zap/wrk/report.json"
REPORT_HTML="/zap/wrk/report.html"

# Adjust scan options based on complexity
case "$COMPLEXITY" in
  low)
    ZAP_ARGS="-t $TARGET_URL -m 3 -d -J $REPORT_JSON -r $REPORT_HTML --hook=skip_all_ascan"
    ;;
  medium)
    ZAP_ARGS="-t $TARGET_URL -m 5 -d -J $REPORT_JSON -r $REPORT_HTML"
    ;;
  high)
    ZAP_ARGS="-t $TARGET_URL -m 10 -d -J $REPORT_JSON -r $REPORT_HTML"
    ;;
  very_high)
    ZAP_ARGS="-t $TARGET_URL -m 15 -d -J $REPORT_JSON -r $REPORT_HTML --auto"
    ;;
  *)
    ZAP_ARGS="-t $TARGET_URL -d -J $REPORT_JSON -r $REPORT_HTML"
    ;;
esac

 
# Start scan with adjusted complexity
#zap-full-scan.py $ZAP_ARGS
timeout 160s zap-full-scan.py $ZAP_ARGS



sleep 5  # Let daemon stabilize

ZAP_PORT=$(cat /zap/zap.port)

# Wait until passive scanning is done
# while true; do
#   RECORDS_LEFT=$(curl -s "http://localhost:$ZAP_PORT/JSON/pscan/view/recordsToScan/" | jq -r '.recordsToScan')
#   echo "Waiting for passive scan to finish... ($RECORDS_LEFT left)"
#   if [ "$RECORDS_LEFT" -eq 0 ]; then
#     break
#   fi
#   sleep 2
# done

# Print alerts to terminal
echo -e "\n================= JSON ALERTS =================\n"
curl -s "http://localhost:$ZAP_PORT/JSON/core/view/alerts/?start=0&count=9999" | jq .
echo -e "\n================= END OF ALERTS ===============\n"

# Indicate saved files
echo -e "\n✅ Reports written to:"
echo "- JSON: $REPORT_JSON"
echo "- HTML: $REPORT_HTML"

echo "[DEBUG] Finished generating reports"

if [ ! -f "$REPORT_JSON" ]; then
  echo "[ERROR] JSON report not found!"
fi

echo "[DEBUG] About to print success message"


echo "ZAP scan completed successfully."
exit 0
