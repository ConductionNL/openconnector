export type TContract = {
    id: number
    uuid: string
    version: string
    synchronizationId?: string
    synchronization_id?: string // API snake_case version
    originId?: string
    origin_id?: string // API snake_case version
    originHash?: string
    origin_hash?: string // API snake_case version
    sourceLastChanged?: string
    source_last_changed?: string // API snake_case version
    sourceLastChecked?: string
    source_last_checked?: string // API snake_case version
    sourceLastSynced?: string
    source_last_synced?: string // API snake_case version
    targetId?: string
    target_id?: string // API snake_case version
    targetHash?: string
    target_hash?: string // API snake_case version
    targetLastChanged?: string
    target_last_changed?: string // API snake_case version
    targetLastChecked?: string
    target_last_checked?: string // API snake_case version
    targetLastSynced?: string
    target_last_synced?: string // API snake_case version
    targetLastAction?: string
    target_last_action?: string // API snake_case version
    created?: string
    updated?: string
}
