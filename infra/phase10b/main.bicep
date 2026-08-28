targetScope = 'resourceGroup'

@description('Short lowercase suffix that makes resource names unique.')
@minLength(3)
@maxLength(10)
param suffix string

param location string = resourceGroup().location

var tags = {
  system: 'civiclear'
  phase: '10b'
  environment: 'pilot'
}

resource laravelIdentity 'Microsoft.ManagedIdentity/userAssignedIdentities@2023-01-31' = {
  name: 'id-civiclear-laravel-${suffix}'
  location: location
  tags: tags
}

resource workerIdentity 'Microsoft.ManagedIdentity/userAssignedIdentities@2023-01-31' = {
  name: 'id-civiclear-worker-${suffix}'
  location: location
  tags: tags
}

resource fastApiIdentity 'Microsoft.ManagedIdentity/userAssignedIdentities@2023-01-31' = {
  name: 'id-civiclear-fastapi-${suffix}'
  location: location
  tags: tags
}

resource registry 'Microsoft.ContainerRegistry/registries@2023-07-01' = {
  name: 'crciviclear${suffix}'
  location: location
  tags: tags
  sku: { name: 'Basic' }
  properties: {
    adminUserEnabled: false
    publicNetworkAccess: 'Enabled'
  }
}

resource queueStorage 'Microsoft.Storage/storageAccounts@2023-05-01' = {
  name: 'stciviclear${suffix}'
  location: location
  tags: tags
  kind: 'StorageV2'
  sku: { name: 'Standard_LRS' }
  properties: {
    allowBlobPublicAccess: false
    allowCrossTenantReplication: false
    allowSharedKeyAccess: true
    defaultToOAuthAuthentication: true
    minimumTlsVersion: 'TLS1_2'
    publicNetworkAccess: 'Enabled'
    supportsHttpsTrafficOnly: true
  }
}

resource queueService 'Microsoft.Storage/storageAccounts/queueServices@2023-05-01' = {
  parent: queueStorage
  name: 'default'
  properties: {
    cors: { corsRules: [] }
  }
}

resource primaryQueue 'Microsoft.Storage/storageAccounts/queueServices/queues@2023-05-01' = {
  parent: queueService
  name: 'civiclear-ai-processing'
  properties: {}
}

resource quarantineQueue 'Microsoft.Storage/storageAccounts/queueServices/queues@2023-05-01' = {
  parent: queueService
  name: 'civiclear-ai-quarantine'
  properties: {}
}

resource logs 'Microsoft.OperationalInsights/workspaces@2022-10-01' = {
  name: 'log-civiclear-${suffix}'
  location: location
  tags: tags
  properties: {
    retentionInDays: 30
    sku: { name: 'PerGB2018' }
  }
}

resource environment 'Microsoft.App/managedEnvironments@2024-03-01' = {
  name: 'cae-civiclear-${suffix}'
  location: location
  tags: tags
  properties: {
    appLogsConfiguration: {
      destination: 'log-analytics'
      logAnalyticsConfiguration: {
        customerId: logs.properties.customerId
        sharedKey: logs.listKeys().primarySharedKey
      }
    }
    workloadProfiles: [
      {
        name: 'Consumption'
        workloadProfileType: 'Consumption'
      }
    ]
  }
}

resource vault 'Microsoft.KeyVault/vaults@2023-07-01' = {
  name: 'kv-civiclear-${suffix}'
  location: location
  tags: tags
  properties: {
    tenantId: subscription().tenantId
    enableRbacAuthorization: true
    enablePurgeProtection: true
    enableSoftDelete: true
    publicNetworkAccess: 'Enabled'
    sku: {
      family: 'A'
      name: 'standard'
    }
  }
}

output registryName string = registry.name
output registryLoginServer string = registry.properties.loginServer
output queueStorageName string = queueStorage.name
output primaryQueueName string = primaryQueue.name
output quarantineQueueName string = quarantineQueue.name
output environmentId string = environment.id
output keyVaultId string = vault.id
output laravelIdentityId string = laravelIdentity.id
output laravelIdentityClientId string = laravelIdentity.properties.clientId
output laravelIdentityPrincipalId string = laravelIdentity.properties.principalId
output workerIdentityId string = workerIdentity.id
output workerIdentityClientId string = workerIdentity.properties.clientId
output workerIdentityPrincipalId string = workerIdentity.properties.principalId
output fastApiIdentityId string = fastApiIdentity.id
