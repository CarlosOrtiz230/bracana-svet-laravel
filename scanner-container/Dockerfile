FROM kalilinux/kali-rolling

# Avoid interactive prompts during install
ENV DEBIAN_FRONTEND=noninteractive

# Install OWASP ZAP and basic tools
RUN apt update && apt install -y \
    zaproxy \
    curl \
    python3 \
    python3-pip \
    unzip

# Make a working directory
WORKDIR /scans

# Default command (can be overwritten)
CMD ["/bin/bash"]

COPY run_zap.sh /usr/local/bin/run_zap.sh
RUN chmod +x /usr/local/bin/run_zap.sh

CMD ["/bin/bash"]

