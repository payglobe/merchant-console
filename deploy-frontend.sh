#!/bin/bash
# Deploy PHP Dashboard Frontend to pgbe2

echo "=== Deploying PayGlobe PHP Dashboard ==="

# Create directory structure
sudo mkdir -p /var/www/html/merchant/frontend

# Move dashboard from /tmp
sudo mv /tmp/dashboard /var/www/html/merchant/frontend/

# Set proper permissions
sudo chown -R www-data:www-data /var/www/html/merchant/frontend/dashboard
sudo chmod -R 755 /var/www/html/merchant/frontend/dashboard

echo ""
echo "=== Deployment Complete! ==="
echo ""
echo "Dashboard accessible at:"
echo "https://ricevute.payglobe.it/merchant/frontend/dashboard/"
echo ""
echo "Files deployed:"
ls -lah /var/www/html/merchant/frontend/dashboard/
ls -lah /var/www/html/merchant/frontend/dashboard/js/

echo ""
echo "=== Testing Apache Configuration ==="
sudo apache2ctl configtest 2>/dev/null || sudo httpd -t 2>/dev/null

echo ""
echo "Done! 🚀"
