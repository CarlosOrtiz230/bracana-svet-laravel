#!/bin/bash

INPUT_FILE="$1"
OUTPUT_FILE="$2"

# Simulate scan (replace this with real ZAP commands later)
echo "[{ \"severity\": \"High\", \"message\": \"Example: XSS vulnerability detected\" }]" > "$OUTPUT_FILE"

