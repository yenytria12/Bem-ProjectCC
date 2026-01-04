# BEM-ProjectCC - CI/CD & Cloud Deployment Documentation

## Peran Anggota Tim

| Role | Nama | Tanggung Jawab |
|------|------|----------------|
| **Systems Developer** | Aurel | Pengembangan aplikasi MVP, integrasi Midtrans, backend/frontend |
| **DevOps Engineer** | Embun | Setup Jenkins CI/CD, integrasi GitHub SCM, Docker |
| **Server Administrator** | Ye | Deployment Azure, custom subdomain, konektivitas database, hosting |

---

## Arsitektur Deployment

```
┌─────────────────┐     ┌─────────────────┐     ┌──────────────────┐
│   GitHub Repo   │────▶│  Jenkins CI/CD  │────▶│  Azure Web App   │
│  (Source Code)  │     │   (Build/Test)  │     │   (Production)   │
└─────────────────┘     └─────────────────┘     └──────────────────┘
                                                        │
                                                        ▼
                                               ┌──────────────────┐
                                               │  Azure MySQL DB  │
                                               │  (Flexible Server)│
                                               └──────────────────┘
```

---

## Resource Azure

| Resource | Nama | Spesifikasi |
|----------|------|-------------|
| Resource Group | TubesCloudComp | - |
| Web App | bem-projectcc | PHP 8.2, Linux |
| MySQL Server | bem-mysql-db | Flexible Server, Standard_B1ms |
| Database | bem_projectcc | MySQL 8.0 |
| Jenkins VM | jenkins-server | Standard_B2s (2vCPU, 4GB RAM) |

### URLs
- **Production:** https://bem-projectcc.azurewebsites.net
- **Jenkins:** http://70.153.85.94:8080

---

## Konfigurasi Jenkins CI/CD

### Pipeline Stages
1. **Checkout** - Clone dari GitHub repository
2. **Install PHP Dependencies** - `composer install --no-dev`
3. **Install Node Dependencies** - `npm ci && npm run build`
4. **Prepare Deployment** - Create deployment zip
5. **Deploy to Azure** - ZIP Deploy via Kudu API

### Jenkinsfile
```groovy
pipeline {
    agent any
    environment {
        AZURE_APP_NAME = 'bem-projectcc'
    }
    stages {
        stage('Checkout') { ... }
        stage('Install PHP Dependencies') {
            steps {
                sh 'composer install --no-dev --optimize-autoloader --no-interaction --no-scripts'
            }
        }
        stage('Install Node Dependencies') {
            steps {
                sh 'npm ci'
                sh 'npm run build'
            }
        }
        stage('Deploy to Azure') {
            steps {
                withCredentials([...]) {
                    sh 'curl -X POST ... zipdeploy'
                }
            }
        }
    }
}
```

### Credentials Required
| ID | Type | Description |
|----|------|-------------|
| `azure-publish-creds` | Username/Password | Azure Web App publish credentials |

---

## Environment Variables (Azure Web App)

### Core Application
| Variable | Value |
|----------|-------|
| APP_NAME | BEM-ProjectCC |
| APP_ENV | production |
| APP_DEBUG | true/false |
| APP_KEY | base64:... |
| APP_URL | https://bem-projectcc.azurewebsites.net |

### Database (MySQL SSL)
| Variable | Value |
|----------|-------|
| DB_CONNECTION | mysql |
| DB_HOST | bem-mysql-db.mysql.database.azure.com |
| DB_PORT | 3306 |
| DB_DATABASE | bem_projectcc |
| DB_USERNAME | bemadmin |
| DB_PASSWORD | ******** |
| MYSQL_ATTR_SSL_CA | /home/site/wwwroot/DigiCertGlobalRootCA.crt.pem |

### Integrasi
| Variable | Description |
|----------|-------------|
| GOOGLE_CLIENT_ID | Google OAuth Client ID |
| GOOGLE_CLIENT_SECRET | Google OAuth Client Secret |
| GOOGLE_REDIRECT_URI | https://bem-projectcc.azurewebsites.net/auth/google/callback |
| MIDTRANS_MERCHANT_ID | Midtrans Merchant ID |
| MIDTRANS_CLIENT_KEY | Midtrans Client Key |
| MIDTRANS_SERVER_KEY | Midtrans Server Key |

---

## MySQL SSL Configuration

Azure MySQL Flexible Server membutuhkan SSL. Konfigurasi di `config/database.php`:

```php
'mysql' => [
    // ...
    'options' => extension_loaded('pdo_mysql') && env('MYSQL_ATTR_SSL_CA') ? [
        PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
        PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
    ] : [],
],
```

Certificate file: `DigiCertGlobalRootCA.crt.pem` (included in repository)

---

## Deployment Manual

### First Time Setup
1. **Run migrations:**
   ```bash
   php artisan migrate --seed --force
   ```

2. **Clear cache:**
   ```bash
   php artisan config:clear
   php artisan cache:clear
   ```

### Trigger Deployment
1. Push changes ke `main` branch
2. Buka Jenkins → Build Now
3. Wait for deployment to complete
4. Verify di https://bem-projectcc.azurewebsites.net

---

## Troubleshooting

### SSL Connection Error
- Pastikan `MYSQL_ATTR_SSL_CA` di-set ke path certificate
- Pastikan certificate file ada di deployment

### Vite Manifest Not Found
- Pastikan `npm run build` berhasil di Jenkins
- Cek folder `public/build` ada setelah deployment

### 502 Bad Gateway
- Restart Web App dari Azure Portal
- Cek startup command configuration

---

## Tim Pengembang

**Mata Kuliah:** Cloud Computing  
**Kelompok:** TubesCloudComp

| Nama | NIM | Role |
|------|-----|------|
| Aurel | - | Systems Developer |
| Embun | - | DevOps Engineer |
| Ye | - | Server Administrator |
