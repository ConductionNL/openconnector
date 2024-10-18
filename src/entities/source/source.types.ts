/* eslint-disable @typescript-eslint/no-explicit-any */
export type TSource = {
    id: string
    name: string
    description: string
    reference: string
    version: string
    location: string
    isEnabled: boolean
    type: 'json' | 'xml' | 'soap' | 'ftp' | 'sftp'
    authorizationHeader: string
    auth: 'apikey' | 'jwt' | 'username-password' | 'none' | 'jwt-HS256' | 'vrijbrp-jwt' | 'pink-jwt' | 'oauth'
    authenticationConfig: object
    authorizationPassthroughMethod: 'header' | 'query' | 'form_params' | 'json' | 'base_auth'
    locale: string
    accept: string
    jwt: string
    jwtId: string
    secret: string
    username: string
    password: string
    apikey: string
    documentation: string
    loggingConfig: object
    oas: any[]
    paths: any[]
    headers: any[]
    translationConfig: any[]
    configuration: object
    endpointsConfig: object
    status: string
    logRetention: number
    errorRetention: number
    lastCall: string
    lastSync: string
    objectCount: number
    dateCreated: string
    dateModified: string
    test: boolean
}
