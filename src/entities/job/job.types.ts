export type TJob = {
    id: string
    name: string
    description: string
    jobClass: string
    arguments: object
    interval: number
    executionTime: number
    timeSensitive: boolean
    allowParallelRuns: boolean
    isEnabled: boolean
    singleRun: boolean
    scheduleAfter: string
    userId: string
    jobListId: string
    logRetention: number
    errorRetention: number
    lastRun: string
    nextRun: string
    created: string
    updated: string
}
