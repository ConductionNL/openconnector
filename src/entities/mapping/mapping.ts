/* eslint-disable @typescript-eslint/no-explicit-any */
import { SafeParseReturnType, z } from 'zod'
import { TMapping } from './mapping.types'

export class Mapping implements TMapping {

	public id: number
	public uuid: string
	public reference: string
	public version: string
	public name: string
	public description: string
	public mapping: any[]
	public unset: any[]
	public cast: any[]
	public passThrough: boolean
	public dateCreated: string
	public dateModified: string

	constructor(mapping: TMapping) {
		this.id = mapping.id || null
		this.uuid = mapping.uuid || null
		this.reference = mapping.reference || ''
		this.version = mapping.version || '0.0.0'
		this.name = mapping.name || ''
		this.description = mapping.description || ''
		this.mapping = mapping.mapping || []
		this.unset = mapping.unset || []
		this.cast = mapping.cast || []
		this.passThrough = mapping.passThrough ?? false
		this.dateCreated = mapping.dateCreated || ''
		this.dateModified = mapping.dateModified || ''
	}

	public validate(): SafeParseReturnType<TMapping, unknown> {
		const schema = z.object({
			id: z.number().or(z.null()),
			uuid: z.string().uuid().max(36).or(z.null()),
			reference: z.string().max(255),
			version: z.string().max(255),
			name: z.string().max(255),
			description: z.string(),
			mapping: z.array(z.any()),
			unset: z.array(z.any()),
			cast: z.array(z.any()),
			passThrough: z.boolean(),
			dateCreated: z.string().or(z.literal('')),
			dateModified: z.string().or(z.literal('')),
		})

		return schema.safeParse({ ...this })
	}

}
