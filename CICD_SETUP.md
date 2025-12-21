# CI/CD Setup Guide - WebVersion Laravel

## 📋 Overview

```
GitHub (Push) → Jenkins (Build & Test) → Azure App Service
```

## 🗂️ Files yang Dibuat

| File | Fungsi |
|------|--------|
| `Jenkinsfile` | Pipeline definition untuk Jenkins |
| `Dockerfile` | Container image untuk production |
| `docker-compose.yml` | Local development dengan Docker |
| `docker/nginx.conf` | Nginx configuration |
| `docker/supervisord.conf` | Process manager config |
| `azure-pipelines.yml` | Alternatif: Azure DevOps pipeline |
| `scripts/setup-jenkins.sh` | Script setup Jenkins server |
| `scripts/deploy.sh` | Deployment script untuk Azure |
| `.env.testing.example` | Environment untuk testing |
| `.dockerignore` | Files to exclude dari Docker build |

---

## 🔧 Step 1: Setup Azure Resources

### 1.1 Create Resource Group
```bash
az group create --name webversion-rg --location southeastasia
```

### 1.2 Create App Service Plan
```bash
az appservice plan create \
    --name webversion-plan \
    --resource-group webversion-rg \
    --sku B1 \
    --is-linux
```

### 1.3 Create Web App
```bash
az webapp create \
    --name webversion-app \
    --resource-group webversion-rg \
    --plan webversion-plan \
    --runtime "PHP|8.2"
```

### 1.4 Create Service Principal (untuk Jenkins)
```bash
az ad sp create-for-rbac \
    --name "jenkins-webversion" \
    --role contributor \
    --scopes /subscriptions/{SUBSCRIPTION_ID}/resourceGroups/webversion-rg \
    --sdk-auth
```

**Simpan output JSON ini! Akan dipakai di Jenkins credentials.**

---

## 🔧 Step 2: Setup Jenkins Server

### 2.1 Jalankan Setup Script
```bash
# Di server Jenkins (Ubuntu/Debian)
chmod +x scripts/setup-jenkins.sh
./scripts/setup-jenkins.sh
```

### 2.2 Install Jenkins Plugins
Setelah Jenkins running, install plugins:
- Git
- Pipeline
- Azure Credentials
- SSH Agent
- GitHub Integration
- Slack Notification (optional)

### 2.3 Configure Jenkins Credentials

**Manage Jenkins → Credentials → System → Global credentials**

| ID | Type | Description |
|----|------|-------------|
| `github-token` | Secret text | GitHub Personal Access Token |
| `azure-service-principal` | Azure Service Principal | Output dari step 1.4 |
| `azure-resource-group` | Secret text | `webversion-rg` |
| `azure-app-name` | Secret text | `webversion-app` |
| `laravel-env-production` | Secret file | File `.env` production |

---

## 🔧 Step 3: Setup GitHub Webhook

1. Go to GitHub repo → Settings → Webhooks
2. Add webhook:
   - **Payload URL:** `http://JENKINS_IP:8080/github-webhook/`
   - **Content type:** `application/json`
   - **Events:** Just the push event
   - **Active:** ✓

---

## 🔧 Step 4: Create Jenkins Pipeline

1. **New Item** → Enter name: `webversion-pipeline`
2. Select **Pipeline**
3. Configure:
   - **GitHub project:** `https://github.com/username/WebVersion`
   - **Build Triggers:** ✓ GitHub hook trigger for GITScm polling
   - **Pipeline:**
     - Definition: Pipeline script from SCM
     - SCM: Git
     - Repository URL: `https://github.com/username/WebVersion.git`
     - Credentials: `github-token`
     - Branch: `*/main`
     - Script Path: `Jenkinsfile`

---

## 🔧 Step 5: Configure Azure App Service

### 5.1 Set Environment Variables
```bash
az webapp config appsettings set \
    --name webversion-app \
    --resource-group webversion-rg \
    --settings \
    APP_ENV=production \
    APP_DEBUG=false \
    APP_KEY=base64:YOUR_APP_KEY \
    DB_CONNECTION=mysql \
    DB_HOST=your-db-host \
    DB_DATABASE=webversion \
    DB_USERNAME=your-username \
    DB_PASSWORD=your-password
```

### 5.2 Enable Deployment Logs
```bash
az webapp log config \
    --name webversion-app \
    --resource-group webversion-rg \
    --docker-container-logging filesystem
```

---

## 🚀 Deployment Flow

### Automatic (via GitHub push)
```
1. Push ke branch `main`
2. GitHub webhook trigger Jenkins
3. Jenkins:
   - Checkout code
   - Install dependencies
   - Run tests
   - Build assets
   - Deploy ke Azure
4. Azure App Service restart
```

### Manual
```bash
# Trigger build manual di Jenkins
curl -X POST http://JENKINS_IP:8080/job/webversion-pipeline/build \
    --user admin:API_TOKEN
```

---

## 🐳 Local Development dengan Docker

```bash
# Build dan run
docker-compose up -d

# Akses di http://localhost:8080

# Stop
docker-compose down
```

---

## 📝 Environment Variables yang Perlu Diset

### Production (.env)
```env
APP_NAME=WebVersion
APP_ENV=production
APP_KEY=base64:xxxxx
APP_DEBUG=false
APP_URL=https://webversion-app.azurewebsites.net

DB_CONNECTION=mysql
DB_HOST=your-azure-mysql.mysql.database.azure.com
DB_PORT=3306
DB_DATABASE=webversion
DB_USERNAME=admin@your-azure-mysql
DB_PASSWORD=your-secure-password

CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

REDIS_HOST=your-redis.redis.cache.windows.net
REDIS_PASSWORD=your-redis-key
REDIS_PORT=6380
REDIS_SCHEME=tls
```

---

## 🔍 Troubleshooting

### Jenkins build failed
```bash
# Check Jenkins logs
sudo tail -f /var/log/jenkins/jenkins.log
```

### Azure deployment failed
```bash
# Check deployment logs
az webapp log tail --name webversion-app --resource-group webversion-rg
```

### Permission issues
```bash
# Di Azure App Service SSH
chmod -R 775 storage bootstrap/cache
```

---

## 📚 Resources

- [Jenkins Documentation](https://www.jenkins.io/doc/)
- [Azure App Service PHP](https://docs.microsoft.com/en-us/azure/app-service/configure-language-php)
- [Laravel Deployment](https://laravel.com/docs/deployment)
