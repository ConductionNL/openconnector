export type TSynchronization = {
    id: number
    name: string
    description: string
    sourceId: string
    sourceType: string
    sourceHash: string
    sourceTargetMapping: string
    sourceConfig: object
    sourceLastChanged: string
    sourceLastChecked: string
    sourceLastSynced: string
    targetId: string
    targetType: string
    targetHash: string
    targetSourceMapping: string
    targetConfig: object
    targetLastChanged: string
    targetLastChecked: string
    targetLastSynced: string
    created: string
    updated: string
}
