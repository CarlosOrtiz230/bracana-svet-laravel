# Use a lightweight Python base
FROM python:3.10-slim

# Install Semgrep
RUN pip install --no-cache semgrep

# Set your working dir inside the container
WORKDIR /src

# By default, invoke semgrep CLI
ENTRYPOINT ["semgrep"]

