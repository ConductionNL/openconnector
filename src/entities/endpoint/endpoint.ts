import { SafeParseReturnType, z } from 'zod'
import { TEndpoint } from './endpoint.types'
import getValidISOstring from '../../services/getValidISOstring'

export class Endpoint implements TEndpoint {

	public id: number
	public uuid: string
	public name: string
	public description: string
	public reference: string
	public version: string
	public endpoint: string
	public endpointArray: string[]
	public endpointRegex: string
	public method: string
	public targetType: string
	public targetId: string
	public created: string
	public updated: string

	constructor(endpoint: TEndpoint) {
		this.id = endpoint.id || null
		this.uuid = endpoint.uuid || ''
		this.name = endpoint.name || ''
		this.description = endpoint.description || ''
		this.reference = endpoint.reference || ''
		this.version = endpoint.version || '0.0.0'
		this.endpoint = endpoint.endpoint || ''
		this.endpointArray = endpoint.endpointArray ?? []
		this.endpointRegex = endpoint.endpointRegex || ''
		this.method = endpoint.method || 'GET'
		this.targetType = endpoint.targetType || ''
		this.targetId = endpoint.targetId || ''

		// Convert the received ISO string to a valid ISO string using a Date object if necessary
		this.created = getValidISOstring(endpoint.created)
		this.updated = getValidISOstring(endpoint.updated)
	}

	// validate data before posting
	// id's are optional, meaning that the id property is not required to exist on the posted content, NOT that it can be empty / '0'
	public validate(): SafeParseReturnType<TEndpoint, unknown> {
		const schema = z.object({
			id: z.number().or(z.null()),
			uuid: z.string().uuid().or(z.literal('')).optional(),
			name: z.string().max(255),
			description: z.string(),
			reference: z.string(),
			version: z.string(),
			endpoint: z.string(),
			endpointArray: z.string().array(),
			endpointRegex: z.string(),
			method: z.enum(['GET', 'POST', 'PUT', 'DELETE', 'PATCH']),
			targetType: z.string(),
			created: z.string().datetime().or(z.literal('')).optional(),
			updated: z.string().datetime().or(z.literal('')).optional(),
		})

		return schema.safeParse({ ...this })
	}

}
