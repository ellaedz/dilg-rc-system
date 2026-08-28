targetScope = 'resourceGroup'

param location string = resourceGroup().location
param environmentId string
param registryLoginServer string
param laravelIdentityId string
param workerIdentityId string
param fastApiIdentityId string
param laravelImageByDigest string
param fastApiImageByDigest string
param queueStorageAccountName string
param laravelEnvironment array
param fastApiEnvironment array
param workerEnvironment array
param laravelSecretReferences array
param workerSecretReferences array

resource laravel 'Microsoft.App/containerApps@2024-03-01' = {
  name: 'ca-civiclear-laravel'
  location: location
  identity: {
    type: 'UserAssigned'
    userAssignedIdentities: { '${laravelIdentityId}': {} }
  }
  properties: {
    managedEnvironmentId: environmentId
    workloadProfileName: 'Consumption'
    configuration: {
      activeRevisionsMode: 'Single'
      ingress: {
        external: true
        allowInsecure: false
        targetPort: 8080
        transport: 'auto'
      }
      registries: [{ server: registryLoginServer, identity: laravelIdentityId }]
      secrets: laravelSecretReferences
    }
    template: {
      containers: [{
        name: 'laravel'
        image: laravelImageByDigest
        env: laravelEnvironment
        resources: { cpu: json('0.5'), memory: '1Gi' }
        probes: [
          { type: 'Startup', httpGet: { path: '/up', port: 8080 }, initialDelaySeconds: 5, periodSeconds: 5, failureThreshold: 30 }
          { type: 'Liveness', httpGet: { path: '/up', port: 8080 }, periodSeconds: 30, failureThreshold: 3 }
        ]
      }]
      scale: { minReplicas: 0, maxReplicas: 2 }
    }
  }
}

resource fastapi 'Microsoft.App/containerApps@2024-03-01' = {
  name: 'ca-civiclear-fastapi'
  location: location
  identity: {
    type: 'UserAssigned'
    userAssignedIdentities: { '${fastApiIdentityId}': {} }
  }
  properties: {
    managedEnvironmentId: environmentId
    workloadProfileName: 'Consumption'
    configuration: {
      activeRevisionsMode: 'Single'
      ingress: {
        external: false
        allowInsecure: false
        targetPort: 8080
        transport: 'http'
      }
      registries: [{ server: registryLoginServer, identity: fastApiIdentityId }]
    }
    template: {
      containers: [{
        name: 'fastapi'
        image: fastApiImageByDigest
        env: fastApiEnvironment
        resources: { cpu: json('1.0'), memory: '2Gi' }
        probes: [
          { type: 'Startup', httpGet: { path: '/ready', port: 8080 }, initialDelaySeconds: 10, periodSeconds: 5, failureThreshold: 60 }
          { type: 'Liveness', httpGet: { path: '/health', port: 8080 }, periodSeconds: 30, failureThreshold: 3 }
        ]
      }]
      scale: { minReplicas: 0, maxReplicas: 1 }
    }
  }
}

resource worker 'Microsoft.App/jobs@2025-01-01' = {
  name: 'job-civiclear-ai'
  location: location
  identity: {
    type: 'UserAssigned'
    userAssignedIdentities: { '${workerIdentityId}': {} }
  }
  properties: {
    environmentId: environmentId
    workloadProfileName: 'Consumption'
    configuration: {
      triggerType: 'Event'
      replicaRetryLimit: 0
      replicaTimeout: 180
      registries: [{ server: registryLoginServer, identity: workerIdentityId }]
      secrets: workerSecretReferences
      eventTriggerConfig: {
        parallelism: 1
        replicaCompletionCount: 1
        scale: {
          minExecutions: 0
          maxExecutions: 1
          pollingInterval: 30
          rules: [{
            name: 'primary-queue'
            type: 'azure-queue'
            identity: workerIdentityId
            metadata: {
              accountName: queueStorageAccountName
              queueName: 'civiclear-ai-processing'
              queueLength: '1'
            }
          }]
        }
      }
    }
    template: {
      containers: [{
        name: 'worker'
        image: laravelImageByDigest
        command: ['php', 'artisan', 'phase10b:process-azure-ai-message']
        env: workerEnvironment
        resources: { cpu: json('0.5'), memory: '1Gi' }
      }]
    }
  }
}

output laravelFqdn string = laravel.properties.configuration.ingress.fqdn
output fastApiFqdn string = fastapi.properties.configuration.ingress.fqdn
