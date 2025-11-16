#!/bin/bash

# =====================================================
# PayGlobe Merchant API - Deploy Script
# =====================================================

set -e  # Exit on error

echo "========================================="
echo " PayGlobe Merchant API - Deploy Script"
echo "========================================="

# Variables
SERVER="pgbe2"
USER="pguser"
APP_DIR="/opt/merchant-console"
JAR_FILE="merchant-api.jar"
SERVICE_NAME="merchant-api"

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Functions
info() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

warn() {
    echo -e "${YELLOW}[WARN]${NC} $1"
}

error() {
    echo -e "${RED}[ERROR]${NC} $1"
    exit 1
}

# Step 1: Build JAR locally
info "Building JAR file..."
mvn clean package -DskipTests || error "Maven build failed"

if [ ! -f "target/$JAR_FILE" ]; then
    error "JAR file not found: target/$JAR_FILE"
fi

info "JAR built successfully: target/$JAR_FILE"

# Step 2: Check SSH connection
info "Checking SSH connection to $USER@$SERVER..."
ssh $USER@$SERVER "echo 'SSH connection OK'" || error "Cannot connect to $SERVER"

# Step 3: Create directories on server
info "Creating directories on server..."
ssh $USER@$SERVER "sudo mkdir -p $APP_DIR && sudo chown $USER:$USER $APP_DIR"
ssh $USER@$SERVER "sudo mkdir -p /var/log/$SERVICE_NAME && sudo chown $USER:$USER /var/log/$SERVICE_NAME"

# Step 4: Stop service if running
info "Stopping existing service..."
ssh $USER@$SERVER "sudo systemctl stop $SERVICE_NAME || true"

# Step 5: Backup old JAR
info "Backing up old JAR..."
ssh $USER@$SERVER "if [ -f $APP_DIR/$JAR_FILE ]; then mv $APP_DIR/$JAR_FILE $APP_DIR/$JAR_FILE.backup; fi"

# Step 6: Copy new JAR
info "Copying new JAR to server..."
scp target/$JAR_FILE $USER@$SERVER:$APP_DIR/

# Step 7: Copy systemd service file
info "Installing systemd service..."
scp deploy/$SERVICE_NAME.service $USER@$SERVER:/tmp/
ssh $USER@$SERVER "sudo mv /tmp/$SERVICE_NAME.service /etc/systemd/system/"

# Step 8: Reload systemd and start service
info "Reloading systemd..."
ssh $USER@$SERVER "sudo systemctl daemon-reload"

info "Enabling service..."
ssh $USER@$SERVER "sudo systemctl enable $SERVICE_NAME"

info "Starting service..."
ssh $USER@$SERVER "sudo systemctl start $SERVICE_NAME"

# Step 9: Wait for startup
info "Waiting for application to start (10 seconds)..."
sleep 10

# Step 10: Check service status
info "Checking service status..."
ssh $USER@$SERVER "sudo systemctl status $SERVICE_NAME --no-pager" || warn "Service status check failed"

# Step 11: Health check
info "Running health check..."
ssh $USER@$SERVER "curl -f http://localhost:8986/actuator/health" || error "Health check failed"

# Step 12: Apache configuration (optional)
read -p "Do you want to configure Apache reverse proxy? (y/n) " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    info "Configuring Apache..."
    scp deploy/apache-merchant.conf $USER@$SERVER:/tmp/
    ssh $USER@$SERVER "sudo mv /tmp/apache-merchant.conf /etc/apache2/sites-available/"
    ssh $USER@$SERVER "sudo a2enmod proxy proxy_http headers rewrite || true"
    ssh $USER@$SERVER "sudo a2ensite apache-merchant || true"
    ssh $USER@$SERVER "sudo apache2ctl configtest"
    ssh $USER@$SERVER "sudo systemctl reload apache2"
    info "Apache configured successfully"
fi

# Success
echo
echo "========================================="
echo -e "${GREEN}✓ Deployment completed successfully!${NC}"
echo "========================================="
echo
info "Application URL: http://ricevute.payglobe.it/api/v2/auth/health"
info "Logs: sudo journalctl -u $SERVICE_NAME -f"
echo
warn "REMEMBER: Change JWT_SECRET in /etc/systemd/system/$SERVICE_NAME.service for production!"
