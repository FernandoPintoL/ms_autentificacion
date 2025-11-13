#!/bin/bash
# Script para testear el contenedor Docker localmente

set -e

echo "=== Docker Build & Test Script ==="
echo ""

# Cargar variables de entorno (si existen)
if [ -f ".env.production" ]; then
    set -a
    source .env.production
    set +a
fi

# Variables por defecto para testing
DB_HOST="${DB_HOST:-localhost}"
DB_PORT="${DB_PORT:-5432}"
DB_DATABASE="${DB_DATABASE:-test_db}"
DB_USERNAME="${DB_USERNAME:-postgres}"
DB_PASSWORD="${DB_PASSWORD:-password}"
APP_KEY="${APP_KEY:-base64:test123456789012345678901234567890}"

echo "Testing configuration:"
echo "  DB_HOST: $DB_HOST"
echo "  DB_PORT: $DB_PORT"
echo "  DB_DATABASE: $DB_DATABASE"
echo ""

# Construir imagen
echo "[1/3] Building Docker image..."
docker build -t ms-autentificacion:test .

# Ejecutar contenedor
echo "[2/3] Starting container..."
docker run -d \
    --name ms-autentificacion-test \
    -p 8080:80 \
    -e APP_ENV=production \
    -e DB_CONNECTION=pgsql \
    -e DB_HOST=$DB_HOST \
    -e DB_PORT=$DB_PORT \
    -e DB_DATABASE=$DB_DATABASE \
    -e DB_USERNAME=$DB_USERNAME \
    -e DB_PASSWORD=$DB_PASSWORD \
    -e APP_KEY=$APP_KEY \
    ms-autentificacion:test

echo ""
echo "[3/3] Testing endpoints..."
sleep 5

# Test health endpoint
echo "Testing /api/health..."
if curl -s http://localhost:8080/api/health | jq . > /dev/null; then
    echo "✓ /api/health works"
else
    echo "✗ /api/health failed"
    echo "Container logs:"
    docker logs ms-autentificacion-test
fi

# Test root endpoint
echo "Testing / (homepage)..."
if curl -s http://localhost:8080/ > /dev/null; then
    echo "✓ / works"
else
    echo "✗ / failed"
fi

echo ""
echo "Container is running at http://localhost:8080"
echo ""
echo "View logs with: docker logs -f ms-autentificacion-test"
echo "Stop with: docker stop ms-autentificacion-test && docker rm ms-autentificacion-test"
