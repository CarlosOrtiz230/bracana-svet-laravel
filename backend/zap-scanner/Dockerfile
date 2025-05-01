FROM ghcr.io/zaproxy/zaproxy:stable

# Switch to root so we can copy and chmod into /usr/local/bin
USER root

COPY run_zap.sh /usr/local/bin/run_zap.sh
RUN chmod +x /usr/local/bin/run_zap.sh

#  switch back to default 'zap' user for runtime
# USER zap

CMD ["/bin/bash"]
