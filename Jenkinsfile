pipeline {
    agent any
    
    environment {
        COMPOSER_HOME = "${WORKSPACE}/.composer"
        APP_ENV = 'production'
        // Azure credentials - set di Jenkins Credentials
        AZURE_RESOURCE_GROUP = credentials('azure-resource-group')
        AZURE_APP_NAME = credentials('azure-app-name')
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
                sh '''
                    composer install --no-dev --optimize-autoloader --no-interaction
                '''
            }
        }
        
        stage('Install Node Dependencies') {
            steps {
                sh '''
                    npm ci
                    npm run build
                '''
            }
        }
        
        stage('Setup Environment') {
            steps {
                withCredentials([file(credentialsId: 'laravel-env-production', variable: 'ENV_FILE')]) {
                    sh 'cp $ENV_FILE .env'
                }
                sh '''
                    php artisan key:generate --force
                    php artisan config:cache
                    php artisan route:cache
                    php artisan view:cache
                '''
            }
        }
        
        stage('Run Tests') {
            steps {
                sh '''
                    cp .env.testing.example .env.testing || cp .env.example .env.testing
                    php artisan test --env=testing
                '''
            }
        }
        
        stage('Security Check') {
            steps {
                sh '''
                    # Check for known vulnerabilities
                    composer audit || true
                '''
            }
        }
        
        stage('Build Deployment Package') {
            steps {
                sh '''
                    # Create deployment zip
                    zip -r deploy.zip . \
                        -x "*.git*" \
                        -x "node_modules/*" \
                        -x "tests/*" \
                        -x "*.env.example" \
                        -x "*.env.testing*" \
                        -x "phpunit.xml" \
                        -x "Jenkinsfile" \
                        -x "docker-compose*" \
                        -x "Dockerfile*"
                '''
            }
        }
        
        stage('Deploy to Azure') {
            when {
                branch 'main'
            }
            steps {
                withCredentials([azureServicePrincipal('azure-service-principal')]) {
                    sh '''
                        # Login to Azure
                        az login --service-principal \
                            -u $AZURE_CLIENT_ID \
                            -p $AZURE_CLIENT_SECRET \
                            --tenant $AZURE_TENANT_ID
                        
                        # Deploy to Azure App Service
                        az webapp deployment source config-zip \
                            --resource-group $AZURE_RESOURCE_GROUP \
                            --name $AZURE_APP_NAME \
                            --src deploy.zip
                        
                        # Run migrations via SSH or Kudu
                        az webapp ssh --resource-group $AZURE_RESOURCE_GROUP --name $AZURE_APP_NAME \
                            --command "cd /home/site/wwwroot && php artisan migrate --force"
                    '''
                }
            }
        }
        
        stage('Deploy to Staging') {
            when {
                branch 'develop'
            }
            steps {
                withCredentials([azureServicePrincipal('azure-service-principal')]) {
                    sh '''
                        az login --service-principal \
                            -u $AZURE_CLIENT_ID \
                            -p $AZURE_CLIENT_SECRET \
                            --tenant $AZURE_TENANT_ID
                        
                        az webapp deployment source config-zip \
                            --resource-group $AZURE_RESOURCE_GROUP \
                            --name "${AZURE_APP_NAME}-staging" \
                            --src deploy.zip
                    '''
                }
            }
        }
    }
    
    post {
        always {
            cleanWs()
        }
        success {
            echo '✅ Deployment successful!'
            // Uncomment untuk notifikasi
            // slackSend(color: 'good', message: "Deployment successful: ${env.JOB_NAME} #${env.BUILD_NUMBER}")
        }
        failure {
            echo '❌ Deployment failed!'
            // slackSend(color: 'danger', message: "Deployment failed: ${env.JOB_NAME} #${env.BUILD_NUMBER}")
        }
    }
}
