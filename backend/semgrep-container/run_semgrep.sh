#!/bin/bash
set -e

CODE_FILE="$1"           # e.g. /src/1746801791_test_vulnerabilities.java
COMPLEXITY="${2:-medium}"
OUTPUT_DIR="${3:-/src}"

if [ -z "$CODE_FILE" ]; then
  echo "Usage: run_semgrep.sh <code_file> [complexity] [output_dir]"
  exit 1
fi

FILENAME=$(basename "$CODE_FILE")
BASENAME="${FILENAME%.*}"

echo "File to scan: $CODE_FILE"
echo "Complexity level: $COMPLEXITY"
echo "Output will be in: $OUTPUT_DIR"

mkdir -p "$OUTPUT_DIR"
REPORT_JSON="$OUTPUT_DIR/semgrep_result_${BASENAME}.json"

CONFIG="auto"
case "$COMPLEXITY" in
  low) CONFIG="p/ci" ;;
  medium) CONFIG="auto" ;;
  high|very_high) CONFIG="r2c" ;;
  *) echo "Invalid complexity level: $COMPLEXITY"; exit 1 ;;
esac

# ✅ FIX: Use directory as root, --include to focus on file
semgrep --config="$CONFIG" /src --include "$FILENAME" --json -o "$REPORT_JSON"

if [ -f "$REPORT_JSON" ]; then
  echo "JSON report saved to $REPORT_JSON"
else
  echo "Report not generated!"
  exit 2
fi

echo "Semgrep scan finished."
