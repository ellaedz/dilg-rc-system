targetScope = 'resourceGroup'

param storageAccountName string
param registryName string
param keyVaultName string
param laravelIdentityPrincipalId string
param workerIdentityPrincipalId string
param fastApiIdentityPrincipalId string
param visibilityWriterRoleDefinitionId string

resource storage 'Microsoft.Storage/storageAccounts@2023-05-01' existing = {
  name: storageAccountName
}
resource queueService 'Microsoft.Storage/storageAccounts/queueServices@2023-05-01' existing = {
  parent: storage
  name: 'default'
}
resource primary 'Microsoft.Storage/storageAccounts/queueServices/queues@2023-05-01' existing = {
  parent: queueService
  name: 'civiclear-ai-processing'
}
resource quarantine 'Microsoft.Storage/storageAccounts/queueServices/queues@2023-05-01' existing = {
  parent: queueService
  name: 'civiclear-ai-quarantine'
}
resource registry 'Microsoft.ContainerRegistry/registries@2023-07-01' existing = {
  name: registryName
}
resource vault 'Microsoft.KeyVault/vaults@2023-07-01' existing = {
  name: keyVaultName
}
var queueSenderRole = subscriptionResourceId('Microsoft.Authorization/roleDefinitions', 'c6a89b2d-59bc-44d0-9896-0f6e12d7b80a')
var queueProcessorRole = subscriptionResourceId('Microsoft.Authorization/roleDefinitions', '8a0f0c08-91a1-4084-bc3d-661d67233fed')
var acrPullRole = subscriptionResourceId('Microsoft.Authorization/roleDefinitions', '7f951dda-4ed3-4680-a7ca-43fe172d538d')
var keyVaultSecretsUserRole = subscriptionResourceId('Microsoft.Authorization/roleDefinitions', '4633458b-17de-408a-b874-0445c86b69e6')

resource laravelPrimarySender 'Microsoft.Authorization/roleAssignments@2022-04-01' = {
  name: guid(primary.id, laravelIdentityPrincipalId, queueSenderRole)
  scope: primary
  properties: {
    principalId: laravelIdentityPrincipalId
    principalType: 'ServicePrincipal'
    roleDefinitionId: queueSenderRole
  }
}

resource workerPrimaryProcessor 'Microsoft.Authorization/roleAssignments@2022-04-01' = {
  name: guid(primary.id, workerIdentityPrincipalId, queueProcessorRole)
  scope: primary
  properties: {
    principalId: workerIdentityPrincipalId
    principalType: 'ServicePrincipal'
    roleDefinitionId: queueProcessorRole
  }
}

resource workerPrimaryVisibility 'Microsoft.Authorization/roleAssignments@2022-04-01' = {
  name: guid(primary.id, workerIdentityPrincipalId, visibilityWriterRoleDefinitionId)
  scope: primary
  properties: {
    principalId: workerIdentityPrincipalId
    principalType: 'ServicePrincipal'
    roleDefinitionId: visibilityWriterRoleDefinitionId
  }
}

resource workerQuarantineSender 'Microsoft.Authorization/roleAssignments@2022-04-01' = {
  name: guid(quarantine.id, workerIdentityPrincipalId, queueSenderRole)
  scope: quarantine
  properties: {
    principalId: workerIdentityPrincipalId
    principalType: 'ServicePrincipal'
    roleDefinitionId: queueSenderRole
  }
}

resource laravelAcrPull 'Microsoft.Authorization/roleAssignments@2022-04-01' = {
  name: guid(registry.id, laravelIdentityPrincipalId, acrPullRole)
  scope: registry
  properties: {
    principalId: laravelIdentityPrincipalId
    principalType: 'ServicePrincipal'
    roleDefinitionId: acrPullRole
  }
}
resource workerAcrPull 'Microsoft.Authorization/roleAssignments@2022-04-01' = {
  name: guid(registry.id, workerIdentityPrincipalId, acrPullRole)
  scope: registry
  properties: {
    principalId: workerIdentityPrincipalId
    principalType: 'ServicePrincipal'
    roleDefinitionId: acrPullRole
  }
}
resource fastApiAcrPull 'Microsoft.Authorization/roleAssignments@2022-04-01' = {
  name: guid(registry.id, fastApiIdentityPrincipalId, acrPullRole)
  scope: registry
  properties: {
    principalId: fastApiIdentityPrincipalId
    principalType: 'ServicePrincipal'
    roleDefinitionId: acrPullRole
  }
}

resource laravelSecretsUser 'Microsoft.Authorization/roleAssignments@2022-04-01' = {
  name: guid(vault.id, laravelIdentityPrincipalId, keyVaultSecretsUserRole)
  scope: vault
  properties: {
    principalId: laravelIdentityPrincipalId
    principalType: 'ServicePrincipal'
    roleDefinitionId: keyVaultSecretsUserRole
  }
}

resource workerSecretsUser 'Microsoft.Authorization/roleAssignments@2022-04-01' = {
  name: guid(vault.id, workerIdentityPrincipalId, keyVaultSecretsUserRole)
  scope: vault
  properties: {
    principalId: workerIdentityPrincipalId
    principalType: 'ServicePrincipal'
    roleDefinitionId: keyVaultSecretsUserRole
  }
}
