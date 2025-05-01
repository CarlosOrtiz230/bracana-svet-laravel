# parse_html_to_json.py
import sys
from bs4 import BeautifulSoup
import json

html_path = sys.argv[1]
json_path = sys.argv[2]

with open(html_path, 'r', encoding='utf-8') as f:
    soup = BeautifulSoup(f, 'html.parser')

tables = soup.find_all("table", class_="dataTable")

vulnerabilities = []
for table in tables:
    row_data = {}
    rows = table.find_all("tr")
    for row in rows:
        headers = row.find_all("td", class_="column-head")
        values = row.find_all("td")[1:] if len(row.find_all("td")) > 1 else []
        if headers and values:
            key = headers[0].text.strip()
            value = values[0].text.strip()
            row_data[key] = value
    if row_data:
        vulnerabilities.append(row_data)

with open(json_path, 'w', encoding='utf-8') as f:
    json.dump(vulnerabilities, f, indent=2)
