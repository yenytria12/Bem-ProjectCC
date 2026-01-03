pipeline {
    agent any
    
    environment {
        AZURE_APP_NAME = 'bem-projectcc'
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
                sh 'composer install --no-dev --optimize-autoloader --no-interaction'
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
                    rm -rf node_modules
                    rm -rf .git
                    rm -rf tests
                    rm -f deploy.zip
                    zip -r deploy.zip . -x "*.git*" -x "node_modules/*" -x "tests/*"
                '''
            }
        }
        
        stage('Deploy to Azure') {
            steps {
                withCredentials([usernamePassword(credentialsId: 'azure-publish-creds', usernameVariable: 'AZURE_USER', passwordVariable: 'AZURE_PASS')]) {
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
        success {
            echo '✅ Deployment successful!'
        }
        failure {
            echo '❌ Deployment failed!'
        }
        always {
            cleanWs()
        }
    }
}
