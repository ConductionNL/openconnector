export type TJobLog = {
    id: string
    uuid: string
    jobId: string
    jobListId: string
    jobClass: string
    arguments: object
    executionTime: number
    userId: string
    lastRun: string
    nextRun: string
    created: string
    level: string
    message: string
    sessionId: string
    stackTrace: object[]
    expires: string
}
