#!/bin/bash

# ===========================================
# Jenkins Server Setup Script (Ubuntu/Debian)
# Run this on your Jenkins server
# ===========================================

set -e

echo "🔧 Setting up Jenkins server..."

# Update system
sudo apt update && sudo apt upgrade -y

# Install Java (required for Jenkins)
echo "☕ Installing Java..."
sudo apt install -y openjdk-17-jdk

# Add Jenkins repository
echo "📦 Adding Jenkins repository..."
curl -fsSL https://pkg.jenkins.io/debian-stable/jenkins.io-2023.key | sudo tee \
  /usr/share/keyrings/jenkins-keyring.asc > /dev/null

echo deb [signed-by=/usr/share/keyrings/jenkins-keyring.asc] \
  https://pkg.jenkins.io/debian-stable binary/ | sudo tee \
  /etc/apt/sources.list.d/jenkins.list > /dev/null

# Install Jenkins
echo "🏗️ Installing Jenkins..."
sudo apt update
sudo apt install -y jenkins

# Start Jenkins
sudo systemctl enable jenkins
sudo systemctl start jenkins

# Install PHP 8.2
echo "🐘 Installing PHP 8.2..."
sudo apt install -y software-properties-common
sudo add-apt-repository -y ppa:ondrej/php
sudo apt update
sudo apt install -y php8.2 php8.2-cli php8.2-fpm php8.2-mysql php8.2-xml \
    php8.2-mbstring php8.2-curl php8.2-zip php8.2-gd php8.2-bcmath

# Install Composer
echo "🎼 Installing Composer..."
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Install Node.js 20
echo "📗 Installing Node.js..."
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs

# Install Azure CLI
echo "☁️ Installing Azure CLI..."
curl -sL https://aka.ms/InstallAzureCLIDeb | sudo bash

# Install Docker (optional, for container deployments)
echo "🐳 Installing Docker..."
sudo apt install -y docker.io
sudo usermod -aG docker jenkins
sudo systemctl enable docker
sudo systemctl start docker

# Print Jenkins initial password
echo ""
echo "=========================================="
echo "✅ Jenkins setup complete!"
echo "=========================================="
echo ""
echo "Jenkins URL: http://$(hostname -I | awk '{print $1}'):8080"
echo ""
echo "Initial Admin Password:"
sudo cat /var/lib/jenkins/secrets/initialAdminPassword
echo ""
echo "=========================================="
echo ""
echo "Next steps:"
echo "1. Open Jenkins URL in browser"
echo "2. Enter the initial admin password"
echo "3. Install suggested plugins"
echo "4. Create admin user"
echo "5. Install additional plugins:"
echo "   - Git"
echo "   - Pipeline"
echo "   - Azure Credentials"
echo "   - SSH Agent"
echo "   - Slack Notification (optional)"
echo ""
