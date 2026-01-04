# Dokumentasi CI/CD & Cloud Deployment
## BEM-ProjectCC - Tugas Besar Cloud Computing

---

## 1. Pendahuluan

Dokumen ini menjelaskan proses deployment aplikasi **BEM-ProjectCC** menggunakan layanan cloud **Microsoft Azure** dengan implementasi **CI/CD (Continuous Integration/Continuous Deployment)** melalui **Jenkins**. Aplikasi ini merupakan sistem manajemen organisasi berbasis Laravel yang dilengkapi dengan integrasi payment gateway Midtrans dan autentikasi Google OAuth.

### 1.1 Tujuan
- Melakukan deployment aplikasi Laravel ke Azure Web App
- Mengimplementasikan pipeline CI/CD dengan Jenkins
- Mengkonfigurasi database MySQL dengan koneksi SSL yang aman
- Memastikan aplikasi dapat diakses publik melalui subdomain Azure

### 1.2 Tim Pengembang

| Nama | Role | Kontribusi |
|------|------|------------|
| **Aurel** | Systems Developer | Pengembangan aplikasi MVP, integrasi Midtrans, backend dan frontend |
| **Embun** | DevOps Engineer | Setup Jenkins CI/CD, integrasi GitHub SCM, konfigurasi Docker |
| **Ye** | Server Administrator | Deployment Azure, konfigurasi database, hosting dan subdomain |

---

## 2. Arsitektur Sistem

### 2.1 Diagram Arsitektur

Sistem deployment menggunakan arsitektur three-tier yang terdiri dari Source Control Management (GitHub), CI/CD Server (Jenkins), dan Production Environment (Azure).

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

### 2.2 Alur Deployment

Proses deployment dimulai ketika developer melakukan push kode ke repository GitHub pada branch `main`. Jenkins kemudian mendeteksi perubahan (melalui trigger manual atau webhook), mengunduh kode terbaru, menjalankan proses build (composer install, npm build), dan mendeploy hasil build ke Azure Web App menggunakan Kudu ZIP Deploy API.

---

## 3. Pembuatan Resource Azure

### 3.1 Resource Group

Langkah pertama adalah membuat Resource Group sebagai wadah untuk semua resource Azure yang akan digunakan dalam project ini.

```bash
az group create --name TubesCloudComp --location "Indonesia Central"
```

Resource Group bernama **TubesCloudComp** dibuat di region Indonesia Central untuk meminimalkan latency karena target pengguna berada di Indonesia.

### 3.2 Azure Web App

Azure Web App digunakan sebagai platform hosting untuk aplikasi Laravel. Kami memilih runtime PHP 8.2 pada sistem operasi Linux.

```bash
az webapp create --resource-group TubesCloudComp --plan bem-app-plan --name bem-projectcc --runtime "PHP|8.2"
```

Web App dikonfigurasi dengan:
- **Nama:** bem-projectcc
- **URL:** https://bem-projectcc.azurewebsites.net
- **Runtime:** PHP 8.2 (Linux)
- **App Service Plan:** Free Tier (F1)

### 3.3 Azure MySQL Flexible Server

Database MySQL dibuat menggunakan Azure Database for MySQL Flexible Server yang menyediakan high availability dan SSL encryption.

```bash
az mysql flexible-server create --resource-group TubesCloudComp --name bem-mysql-db --admin-user bemadmin --admin-password BemProject2026! --sku-name Standard_B1ms
```

Konfigurasi database:
- **Server Name:** bem-mysql-db.mysql.database.azure.com
- **Database:** bem_projectcc
- **SKU:** Standard_B1ms (1 vCore, 2GB RAM)
- **SSL:** Required (require_secure_transport=ON)

### 3.4 Jenkins Virtual Machine

Jenkins diinstall pada Virtual Machine Azure untuk menjalankan proses CI/CD. Awalnya menggunakan SKU B1s (1GB RAM), namun kemudian di-upgrade ke B2s (4GB RAM) karena proses `npm ci` membutuhkan lebih banyak memori.

```bash
az vm create --resource-group TubesCloudComp --name jenkins-server --image Ubuntu2404 --size Standard_B2s --admin-username azureuser --generate-ssh-keys
```

Spesifikasi VM Jenkins:
- **Nama:** jenkins-server
- **IP Publik:** 70.153.85.94
- **URL Jenkins:** http://70.153.85.94:8080
- **SKU:** Standard_B2s (2 vCPU, 4GB RAM)
- **OS:** Ubuntu 24.04 LTS

Setelah VM dibuat, Jenkins diinstall beserta dependencies yang dibutuhkan:
- OpenJDK 17
- PHP 8.3 dengan extensions (intl, gd, bcmath, mysql, zip)
- Composer
- Node.js v20
- Git

---

## 4. Konfigurasi Jenkins CI/CD

### 4.1 Instalasi Jenkins

Jenkins diinstall pada VM menggunakan repository resmi:

```bash
sudo apt update
sudo apt install -y fontconfig openjdk-17-jre
curl -fsSL https://pkg.jenkins.io/debian-stable/jenkins.io-2023.key | sudo tee /usr/share/keyrings/jenkins-keyring.asc
echo deb [signed-by=/usr/share/keyrings/jenkins-keyring.asc] https://pkg.jenkins.io/debian-stable binary/ | sudo tee /etc/apt/sources.list.d/jenkins.list
sudo apt update
sudo apt install -y jenkins
sudo systemctl start jenkins
```

### 4.2 Konfigurasi Credentials

Untuk melakukan deployment ke Azure, Jenkins membutuhkan credentials publish profile dari Azure Web App. Credentials ini dikonfigurasi di Jenkins dengan ID `azure-publish-creds`.

Langkah mendapatkan credentials:
1. Buka Azure Portal → Web App → Deployment Center → FTPS Credentials
2. Copy username (format: $bem-projectcc) dan password
3. Di Jenkins: Manage Jenkins → Credentials → Add Credentials
4. Isi username dan password dengan ID `azure-publish-creds`

### 4.3 Pipeline Configuration

Jenkins Pipeline dikonfigurasi untuk membaca `Jenkinsfile` dari repository GitHub. File ini mendefinisikan tahapan build dan deployment.

**Jenkinsfile:**

```groovy
pipeline {
    agent any
    
    environment {
        AZURE_APP_NAME = 'bem-projectcc'
    }
    
    stages {
        stage('Checkout') {
            steps {
                checkout scm
            }
        }
        
        stage('Install PHP Dependencies') {
            steps {
                sh 'COMPOSER_PROCESS_TIMEOUT=600 composer install --no-dev --optimize-autoloader --no-interaction --no-scripts'
            }
        }
        
        stage('Install Node Dependencies') {
            steps {
                sh 'npm ci'
                sh 'npm run build'
            }
        }
        
        stage('Prepare Deployment') {
            steps {
                sh '''
                    rm -rf node_modules .git tests
                    zip -r deploy.zip . -x "*.git*" -x "node_modules/*"
                '''
            }
        }
        
        stage('Deploy to Azure') {
            steps {
                withCredentials([usernamePassword(credentialsId: 'azure-publish-creds', 
                    usernameVariable: 'AZURE_USER', passwordVariable: 'AZURE_PASS')]) {
                    sh '''
                        curl -X POST \
                            -u "${AZURE_USER}:${AZURE_PASS}" \
                            --data-binary @deploy.zip \
                            "https://${AZURE_APP_NAME}.scm.azurewebsites.net/api/zipdeploy" \
                            -H "Content-Type: application/zip"
                    '''
                }
            }
        }
    }
    
    post {
        success { echo '✅ Deployment successful!' }
        failure { echo '❌ Deployment failed!' }
        always { cleanWs() }
    }
}
```

---

## 5. Konfigurasi Environment Variables

### 5.1 Pengaturan di Azure Web App

Environment variables dikonfigurasi melalui Azure CLI untuk keamanan dan fleksibilitas. Semua konfigurasi sensitif disimpan sebagai App Settings di Azure Web App.

```bash
az webapp config appsettings set --resource-group TubesCloudComp --name bem-projectcc --settings \
    APP_NAME="BEM-ProjectCC" \
    APP_ENV="production" \
    APP_DEBUG="false" \
    APP_URL="https://bem-projectcc.azurewebsites.net" \
    APP_KEY="base64:2lHjpMRuXJFXBV+FDZFwPUm0A2i+aECddfu+Y4yO8oI=" \
    DB_CONNECTION="mysql" \
    DB_HOST="bem-mysql-db.mysql.database.azure.com" \
    DB_PORT="3306" \
    DB_DATABASE="bem_projectcc" \
    DB_USERNAME="bemadmin" \
    DB_PASSWORD="BemProject2026!" \
    MYSQL_ATTR_SSL_CA="/home/site/wwwroot/DigiCertGlobalRootCA.crt.pem"
```

### 5.2 Integrasi External Services

**Google OAuth:**
```bash
az webapp config appsettings set --resource-group TubesCloudComp --name bem-projectcc --settings \
    GOOGLE_CLIENT_ID="<YOUR_GOOGLE_CLIENT_ID>" \
    GOOGLE_CLIENT_SECRET="<YOUR_GOOGLE_CLIENT_SECRET>" \
    GOOGLE_REDIRECT_URI="https://bem-projectcc.azurewebsites.net/auth/google/callback"
```

**Midtrans Payment Gateway:**
```bash
az webapp config appsettings set --resource-group TubesCloudComp --name bem-projectcc --settings \
    MIDTRANS_MERCHANT_ID="<YOUR_MERCHANT_ID>" \
    MIDTRANS_CLIENT_KEY="<YOUR_CLIENT_KEY>" \
    MIDTRANS_SERVER_KEY="<YOUR_SERVER_KEY>" \
    MIDTRANS_IS_PRODUCTION="false"
```

---

## 6. Konfigurasi MySQL SSL

### 6.1 Permasalahan

Azure MySQL Flexible Server mengharuskan koneksi menggunakan SSL (Secure Socket Layer). Tanpa konfigurasi SSL yang benar, Laravel akan menampilkan error:

```
SQLSTATE[HY000] [2002] Cannot connect to MySQL using SSL
```

### 6.2 Solusi

Untuk mengatasi masalah ini, kami melakukan dua langkah:

**Langkah 1:** Download SSL Certificate dari DigiCert dan include dalam repository:
```bash
curl -o DigiCertGlobalRootCA.crt.pem https://dl.cacerts.digicert.com/DigiCertGlobalRootCA.crt.pem
```

**Langkah 2:** Konfigurasi Laravel database.php dengan opsi SSL:
```php
'mysql' => [
    'driver' => 'mysql',
    'host' => env('DB_HOST', '127.0.0.1'),
    // ... konfigurasi lainnya
    'options' => extension_loaded('pdo_mysql') && env('MYSQL_ATTR_SSL_CA') ? [
        PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
        PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
    ] : [],
],
```

Opsi `MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false` diperlukan karena certificate Azure MySQL tidak dapat diverifikasi secara langsung oleh PHP PDO driver.

---

## 7. Proses Deployment

### 7.1 First-Time Setup

Setelah deployment pertama, administrator perlu menjalankan migrations untuk membuat tabel database:

```bash
# SSH ke Azure Web App
az webapp ssh --resource-group TubesCloudComp --name bem-projectcc

# Jalankan migrations
cd /home/site/wwwroot
php artisan migrate --seed --force
php artisan config:clear
php artisan cache:clear
```

### 7.2 Regular Deployment

Untuk deployment rutin, proses berjalan otomatis melalui Jenkins:

1. Developer push perubahan ke branch `main` di GitHub
2. Buka Jenkins (http://70.153.85.94:8080)
3. Klik "Build Now" pada job TUBES
4. Tunggu proses build (~5-6 menit)
5. Verifikasi di https://bem-projectcc.azurewebsites.net

---

## 8. URL Akses

| Resource | URL |
|----------|-----|
| **Production Website** | https://bem-projectcc.azurewebsites.net |
| **Jenkins Dashboard** | http://70.153.85.94:8080 |
| **GitHub Repository** | https://github.com/yenytria12/Bem-ProjectCC |
| **Azure Portal** | https://portal.azure.com |

---

## 9. Troubleshooting

### 9.1 SSL Connection Error
**Gejala:** Error "Cannot connect to MySQL using SSL"

**Solusi:**
- Pastikan file `DigiCertGlobalRootCA.crt.pem` ada di repository
- Pastikan `MYSQL_ATTR_SSL_CA` di-set dengan path yang benar
- Verifikasi konfigurasi `database.php` sudah include opsi SSL

### 9.2 Vite Manifest Not Found
**Gejala:** Error "Vite manifest not found at /home/site/wwwroot/public/build/manifest.json"

**Solusi:**
- Pastikan Jenkins VM memiliki cukup RAM (minimal 4GB)
- Pastikan stage "Install Node Dependencies" berhasil
- Verifikasi `npm run build` menghasilkan folder `public/build`

### 9.3 502 Bad Gateway
**Gejala:** Website menampilkan error 502 Bad Gateway

**Solusi:**
- Restart Web App dari Azure Portal
- Cek Log Stream untuk melihat error detail
- Verifikasi startup command sudah benar

---

## 10. Kesimpulan

Implementasi CI/CD untuk BEM-ProjectCC berhasil dilakukan dengan mengintegrasikan GitHub, Jenkins, dan Azure. Dengan arsitektur ini:

1. **Deployment menjadi otomatis** - Tidak perlu manual upload file ke server
2. **Konsistensi build** - Setiap deployment melalui proses yang sama
3. **Rollback mudah** - Jika ada error, dapat kembali ke commit sebelumnya
4. **Keamanan terjaga** - Credentials disimpan aman di Jenkins dan Azure

Aplikasi dapat diakses publik melalui https://bem-projectcc.azurewebsites.net dengan dukungan HTTPS, database MySQL terenkripsi SSL, dan integrasi payment gateway Midtrans.

---

**Mata Kuliah:** Cloud Computing  
**Kelompok:** TubesCloudComp  
**Tahun:** 2026
