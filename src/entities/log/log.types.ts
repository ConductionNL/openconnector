/* eslint-disable @typescript-eslint/no-explicit-any */
export type TLog = {
    id: number
    uuid?: string
    message?: string
    synchronizationId?: string
    synchronization_id?: string // API snake_case version
    result?: any[]
    userId?: string
    user_id?: string // API snake_case version
    sessionId?: string
    session_id?: string // API snake_case version
    test?: boolean
    force?: boolean
    executionTime?: number
    execution_time?: number // API snake_case version
    created?: string
    expires?: string
}
