#!/bin/bash
# Stop PayGlobe Merchant API

SERVICE_NAME="merchant-api"

echo "Stopping $SERVICE_NAME..."
sudo systemctl stop $SERVICE_NAME

# Wait for shutdown
sleep 3

# Check status
sudo systemctl status $SERVICE_NAME --no-pager

echo ""
echo "Service stopped successfully!"
