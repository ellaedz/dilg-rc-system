# syntax=docker/dockerfile:1.7

ARG PYTHON_IMAGE=python:3.12.5-slim-bookworm@sha256:c24c34b502635f1f7c4e99dc09a2cbd85d480b7dcfd077198c6b5af138906390
FROM ${PYTHON_IMAGE} AS runtime

ENV PYTHONDONTWRITEBYTECODE=1 \
    PYTHONUNBUFFERED=1 \
    PIP_DISABLE_PIP_VERSION_CHECK=1 \
    PIP_NO_CACHE_DIR=1 \
    PORT=8080 \
    MAX_IMAGE_UPLOAD_BYTES=5242880 \
    OMP_NUM_THREADS=1 \
    OPENBLAS_NUM_THREADS=1 \
    MKL_NUM_THREADS=1 \
    NUMEXPR_NUM_THREADS=1 \
    TMPDIR=/tmp/civiclear \
    MUNICIPAL_BOUNDARY_PATH=/app/gis/boundary.geojson \
    BARANGAY_BOUNDARY_PATH=/app/gis/santa_cruz_barangays.geojson

RUN apt-get update \
    && apt-get install -y --no-install-recommends ca-certificates libgomp1 \
    && rm -rf /var/lib/apt/lists/* \
    && groupadd --gid 10001 civiclear \
    && useradd --uid 10001 --gid 10001 --create-home --shell /usr/sbin/nologin civiclear \
    && mkdir -p /app /app/gis /tmp/civiclear \
    && chown -R civiclear:civiclear /app /tmp/civiclear

WORKDIR /app
COPY ai-inference-server/requirements.runtime.txt ./requirements.runtime.txt
RUN python -m pip install --requirement requirements.runtime.txt

COPY --chown=civiclear:civiclear ai-inference-server/*.py ./
COPY --chown=civiclear:civiclear ai-inference-server/schemas schemas
COPY --chown=civiclear:civiclear ai-inference-server/services services
COPY --chown=civiclear:civiclear ai-inference-server/models models
COPY --chown=civiclear:civiclear public/gis/boundary.geojson gis/boundary.geojson
COPY --chown=civiclear:civiclear docker/fastapi/entrypoint.sh /usr/local/bin/civiclear-fastapi-entrypoint
RUN chmod 0555 /usr/local/bin/civiclear-fastapi-entrypoint

USER civiclear
EXPOSE 8080
HEALTHCHECK --interval=10s --timeout=5s --start-period=45s --retries=3 \
    CMD python -c "import os, urllib.request; urllib.request.urlopen('http://127.0.0.1:' + os.getenv('PORT', '8080') + '/ready', timeout=3).read()"
ENTRYPOINT ["/usr/local/bin/civiclear-fastapi-entrypoint"]
