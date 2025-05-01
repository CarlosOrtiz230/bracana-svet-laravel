FROM perl:slim

# Install Nikto dependencies
RUN apt-get update && apt-get install -y \
    git \
    libnet-ssleay-perl \
    openssl \
    libwhisker2-perl \
    liburi-perl \
    ca-certificates \
    curl \
 && rm -rf /var/lib/apt/lists/*

# Clone official Nikto repo
RUN git clone https://github.com/sullo/nikto /opt/nikto

# Set working directory to where nikto.pl actually is
WORKDIR /opt/nikto/program

# Make the scanner executable
RUN chmod +x nikto.pl

# Set default command
ENTRYPOINT ["perl", "nikto.pl"]
