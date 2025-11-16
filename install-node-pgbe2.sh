#!/bin/bash
# Install Node.js on pgbe2 using nvm (no sudo required)

echo "=== Installing nvm (Node Version Manager) ==="
curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/v0.39.7/install.sh | bash

# Load nvm
export NVM_DIR="$HOME/.nvm"
[ -s "$NVM_DIR/nvm.sh" ] && \. "$NVM_DIR/nvm.sh"

echo "=== Installing Node.js v20 LTS ==="
nvm install 20
nvm use 20
nvm alias default 20

echo "=== Node.js installed ==="
node --version
npm --version

echo ""
echo "=== Installing React app dependencies ==="
cd ~/merchant-dashboard
npm install

echo ""
echo "=== Setup complete! ==="
echo ""
echo "To start the React app:"
echo "  cd ~/merchant-dashboard"
echo "  npm run dev"
echo ""
echo "Or run in background:"
echo "  nohup npm run dev > react-app.log 2>&1 &"
