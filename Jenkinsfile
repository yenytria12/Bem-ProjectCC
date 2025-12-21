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
                bat 'composer install --no-dev --optimize-autoloader --no-interaction'
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
                bat 'php artisan test'
            }
        }
        
        stage('Deploy to Azure') {
            steps {
                withCredentials([string(credentialsId: 'azure-publish-password', variable: 'AZURE_PASS')]) {
                    bat """
                        powershell Compress-Archive -Path * -DestinationPath deploy.zip -Force
                        curl -X POST -u "$bem-webservice:${AZURE_PASS}" --data-binary @deploy.zip https://bem-webservice.scm.azurewebsites.net/api/zipdeploy
                    """
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
