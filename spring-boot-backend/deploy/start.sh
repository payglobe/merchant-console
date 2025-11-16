#!/bin/bash
# Start PayGlobe Merchant API

SERVICE_NAME="merchant-api"

echo "Starting $SERVICE_NAME..."
sudo systemctl start $SERVICE_NAME

# Wait for startup
sleep 5

# Check status
sudo systemctl status $SERVICE_NAME --no-pager

# Health check
echo ""
echo "Checking health..."
curl -f http://localhost:8986/actuator/health

echo ""
echo "Service started successfully!"
