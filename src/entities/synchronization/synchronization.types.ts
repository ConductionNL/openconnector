export type TSynchronization = {
    id: number
    name: string
    description: string
    conditions: string
    sourceId: string
    sourceType: string
    sourceHash: string
    sourceHashMapping: string
    sourceTargetMapping: string
    sourceConfig: Record<string, string>
    sourceLastChanged: string
    sourceLastChecked: string
    sourceLastSynced: string
    targetId: string
    targetType: string
    targetHash: string
    targetSourceMapping: string
    targetConfig: Record<string, string>
    targetLastChanged: string
    targetLastChecked: string
    targetLastSynced: string
    created: string
    updated: string
}
