import { SafeParseReturnType, z } from 'zod'
import { TEndpoint } from './endpoint.types'
import getValidISOstring from '../../services/getValidISOstring'
import ReadonlyBaseClass from '../ReadonlyBaseClass.js'

export class Endpoint extends ReadonlyBaseClass implements TEndpoint {

	public readonly id: number
	public readonly uuid: string
	public readonly name: string
	public readonly description: string
	public readonly reference: string
	public readonly version: string
	public readonly endpoint: string
	public readonly endpointArray: string[]
	public readonly endpointRegex: string
	public readonly method: 'GET' | 'POST' | 'PUT' | 'DELETE' | 'PATCH'
	public readonly targetType: string
	public readonly targetId: string
	public readonly created: string
	public readonly updated: string

	constructor(endpoint: TEndpoint) {
		const processedEndpoint: TEndpoint = {
			id: endpoint.id || null,
			uuid: endpoint.uuid || '',
			name: endpoint.name || '',
			description: endpoint.description || '',
			reference: endpoint.reference || '',
			version: endpoint.version || '0.0.0',
			endpoint: endpoint.endpoint || '',
			endpointArray: endpoint.endpointArray ?? [],
			endpointRegex: endpoint.endpointRegex || '',
			method: endpoint.method || 'GET',
			targetType: endpoint.targetType || '',
			targetId: endpoint.targetId || '',
			created: getValidISOstring(endpoint.created) ?? '',
			updated: getValidISOstring(endpoint.updated) ?? '',
		}

		super(processedEndpoint)
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
