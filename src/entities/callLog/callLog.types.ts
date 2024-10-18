/* eslint-disable @typescript-eslint/no-explicit-any */
export type TCallLog = {
    id: number
    sourceId: string
    endpoint: string
    method: 'GET' | 'POST' | 'PUT' | 'DELETE' | 'PATCH'
    statusCode: number
    requestHeaders: object
    requestBody: any
    responseHeaders: object
    responseBody: any
    duration: number
    error: string
    created: string
    updated: string
}
