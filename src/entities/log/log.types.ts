export type TLog = {
    id: string
    type: 'in' | 'out'
    callId: string
    requestMethod: string
    requestHeaders: object[]
    requestQuery: object[]
    requestPathInfo: string
    requestLanguages: string[]
    requestServer: object
    requestContent: string
    responseStatus: string
    responseStatusCode: number
    responseHeaders: object[]
    responseContent: string
    userId: string
    session: string
    sessionValues: object
    responseTime: number
    routeName: string
    routeParameters: object
    entity: object
    endpoint: object
    gateway: object
    handler: object
    objectId: string
    dateCreated: string
    dateModified: string
}
