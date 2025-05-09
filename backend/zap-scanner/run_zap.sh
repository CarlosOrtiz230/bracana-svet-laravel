#!/bin/bash
set -e

TARGET_URL="$1"
COMPLEXITY="${2:-medium}"
OUTPUT_DIR="${3:-/zap/wrk}"

if [ -z "$TARGET_URL" ]; then
  echo "Usage: run_zap.sh <target_url> [complexity] [output_dir]"
  exit 1
fi

echo "Received target: $TARGET_URL"
echo "Complexity level: $COMPLEXITY"
echo "Reports will be saved to: $OUTPUT_DIR"

mkdir -p "$OUTPUT_DIR"

REPORT_JSON="$OUTPUT_DIR/report.json"
REPORT_HTML="$OUTPUT_DIR/report.html"

case "$COMPLEXITY" in
  low)
    ZAP_ARGS="-t $TARGET_URL -m 3 -J $REPORT_JSON -r $REPORT_HTML --hook=skip_all_ascan"
    ;;
  medium)
    ZAP_ARGS="-t $TARGET_URL -m 5 -J $REPORT_JSON -r $REPORT_HTML"
    ;;
  high)
    ZAP_ARGS="-t $TARGET_URL -m 10 -J $REPORT_JSON -r $REPORT_HTML"
    ;;
  very_high)
    ZAP_ARGS="-t $TARGET_URL -m 15 -J $REPORT_JSON -r $REPORT_HTML"
    ;;
  *)
    ZAP_ARGS="-t $TARGET_URL -J $REPORT_JSON -r $REPORT_HTML"
    ;;
esac

echo "[*] Running ZAP with args: $ZAP_ARGS"
zap-full-scan.py $ZAP_ARGS

if [ -f "$REPORT_JSON" ]; then
  echo "JSON report created: $REPORT_JSON"
else
  echo "JSON report not found!"
fi

if [ -f "$REPORT_HTML" ]; then
  echo "HTML report created: $REPORT_HTML"
else
  echo "HTML report not found!"
fi

echo "ZAP scan completed successfully."
exit 0
