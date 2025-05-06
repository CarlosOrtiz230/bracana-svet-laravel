FROM ghcr.io/zaproxy/zaproxy:stable

# Switch to root so we can install script
USER root

COPY run_zap.sh /usr/local/bin/run_zap.sh
RUN chmod +x /usr/local/bin/run_zap.sh

# Switch back to non-root user for security and correct file ownership
USER zap


# Default command
CMD ["run_zap.sh"]
