pipeline {
    agent any
    
    environment {
        AZURE_APP_NAME = 'bem-webservice'
    }
    
    stages {
        stage('Checkout') {
            steps {
                checkout scm
                echo "Branch: ${env.BRANCH_NAME}"
                echo "Commit: ${env.GIT_COMMIT}"
            }
        }
        
        stage('Install PHP Dependencies') {
            steps {
                bat 'composer install --no-dev --optimize-autoloader --no-interaction --ignore-platform-reqs'
            }
        }
        
        stage('Install Node Dependencies') {
            steps {
                bat 'npm ci'
                bat 'npm run build'
            }
        }
        
        stage('Setup Environment') {
            steps {
                bat 'copy .env.example .env'
                bat 'php artisan key:generate --force'
            }
        }
        
        stage('Run Tests') {
            steps {
                echo 'Skipping tests (PHPUnit not installed in production build)'
                // bat 'php artisan test'
            }
        }
        
        stage('Deploy to Azure') {
            steps {
                withCredentials([usernamePassword(credentialsId: 'azure-publish-creds', usernameVariable: 'AZURE_USER', passwordVariable: 'AZURE_PASS')]) {
                    bat 'powershell Compress-Archive -Path * -DestinationPath deploy.zip -Force'
                    bat 'curl -X POST -u %AZURE_USER%:%AZURE_PASS% --data-binary @deploy.zip https://bem-webservice.scm.azurewebsites.net/api/zipdeploy'
                }
            }
        }
    }
    
    post {
        success {
            echo '✅ Deployment successful!'
        }
        failure {
            echo '❌ Deployment failed!'
        }
    }
}
