# =============================================================================
# Edusfera Exam Generator Microservice
# Node.js + KaTeX + Playwright Headless Chromium
# =============================================================================
FROM node:22-slim

ENV NODE_ENV=production \
    PORT=3005 \
    HOST=0.0.0.0 \
    CHROME_EXECUTABLE_PATH=/usr/bin/chromium \
    DEBIAN_FRONTEND=noninteractive

# Install Chromium and fonts (DejaVu Sans for Belarusian/Russian typography)
RUN apt-get update && apt-get install -y --no-install-recommends \
    chromium \
    fonts-dejavu-core \
    fonts-liberation \
    ca-certificates \
    curl \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /app

# Cache package dependencies
COPY package.json ./
RUN npm install --omit=dev

# Copy application source
COPY src/ ./src/

# Security: Run as unprivileged node user
USER node

EXPOSE 3005

HEALTHCHECK --interval=30s --timeout=5s --start-period=5s --retries=3 \
    CMD curl -f http://localhost:3005/health || exit 1

CMD ["node", "src/server.js"]

