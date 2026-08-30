targetScope = 'subscription'

@description('Deploy only after explicit approval and confirmation that the operator may create custom roles.')
param roleName string = 'CIVICLEAR Queue Message Visibility Writer'

var roleGuid = guid(subscription().id, roleName)

resource visibilityWriter 'Microsoft.Authorization/roleDefinitions@2022-04-01' = {
  name: roleGuid
  properties: {
    roleName: roleName
    description: 'Allows CIVICLEAR queue-length scaling reads and message visibility updates on the assigned queue.'
    type: 'CustomRole'
    assignableScopes: [subscription().id]
    permissions: [
      {
        actions: [
          'Microsoft.Storage/storageAccounts/queueServices/queues/read'
        ]
        notActions: []
        dataActions: [
          'Microsoft.Storage/storageAccounts/queueServices/queues/messages/write'
        ]
        notDataActions: []
      }
    ]
  }
}

output roleDefinitionResourceId string = visibilityWriter.id
