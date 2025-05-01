FROM perl:slim

# Install required packages
RUN apt-get update && apt-get install -y \
    git \
    libnet-ssleay-perl \
    openssl \
    libwhisker2-perl \
    liburi-perl \
    ca-certificates \
    curl \
    && rm -rf /var/lib/apt/lists/*

# Clone Nikto from official repo
RUN git clone https://github.com/sullo/nikto.git /opt/nikto

# Copy the run script into the image
COPY run_nikto.sh /opt/nikto/run_nikto.sh

# Make scripts executable
RUN chmod +x /opt/nikto/program/nikto.pl /opt/nikto/run_nikto.sh

# Set working directory to where nikto.pl lives
WORKDIR /opt/nikto/program

# Run entrypoint
ENTRYPOINT ["/opt/nikto/run_nikto.sh"]
