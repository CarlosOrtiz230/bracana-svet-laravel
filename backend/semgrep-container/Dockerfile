FROM returntocorp/semgrep:latest

USER root

COPY run_semgrep.sh /usr/local/bin/run_semgrep.sh
RUN chmod +x /usr/local/bin/run_semgrep.sh

# Optional: switch back to semgrep user if desired, but make sure it has write permissions on /src
# USER semgrep

CMD ["run_semgrep.sh"]
