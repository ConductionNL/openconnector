export type TWebhook = {
    id: number
    name: string
    description: string
    version: string
    url: string
    isEnabled: boolean
    dateCreated: string
    dateModified: string
    headers: object
    events: string[]
    retryPolicy: object
    timeout: number
    lastTriggered: string
    lastResponse: {
        status: number,
        body: string
    }
    secretKey: string
    payloadFormat: 'json' | 'xml' | 'form-data'
    active: boolean
}
