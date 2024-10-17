/* eslint-disable @typescript-eslint/no-explicit-any */
export type TMapping = {
    id: number
    uuid: string
    reference: string
    version: string
    name: string
    description: string
    mapping: any[]
    unset: any[]
    cast: any[]
    passThrough: boolean
    dateCreated: string
    dateModified: string
}
