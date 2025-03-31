import { SafeParseReturnType, z } from 'zod'
import { TConsumer } from './consumer.types'
import getValidISOstring from '../../services/getValidISOstring'
import ReadonlyBaseClass from '../ReadonlyBaseClass.js'
import _ from 'lodash'

export class Consumer extends ReadonlyBaseClass implements TConsumer {

	public readonly id: number
	public readonly uuid: string
	public readonly name: string
	public readonly description: string
	public readonly domains: string[]
	public readonly ips: string[]
	public readonly authorizationType: 'none' | 'basic' | 'bearer' | 'apiKey' | 'oauth2' | 'jwt'
	public readonly authorizationConfiguration: string[][]
	public readonly created: string
	public readonly updated: string

	constructor(consumer: TConsumer) {
		const processedConsumer: TConsumer = {
			id: consumer.id || null,
			uuid: consumer.uuid || '',
			name: consumer.name || '',
			description: consumer.description || '',
			domains: consumer.domains || [],
			ips: consumer.ips || [],
			authorizationType: consumer.authorizationType || 'basic',
			authorizationConfiguration: consumer.authorizationConfiguration || null,
			created: getValidISOstring(consumer.created) ?? '',
			updated: getValidISOstring(consumer.updated) ?? '',
		}

		super(processedConsumer)
	}

	public cloneRaw(): TConsumer {
		return _.cloneDeep(this)
	}

	public validate(): SafeParseReturnType<TConsumer, unknown> {
		const schema = z.object({
			id: z.number().or(z.null()),
			uuid: z.string().uuid().or(z.literal('')).optional(),
			name: z.string().max(255),
			description: z.string(),
			domains: z.array(z.string()),
			ips: z.array(z.string()),
			authorizationType: z.enum(['none', 'basic', 'bearer', 'apiKey', 'oauth2', 'jwt']),
			authorizationConfiguration: z.array(z.array(z.string())),
			created: z.string().datetime().or(z.literal('')).optional(),
			updated: z.string().datetime().or(z.literal('')).optional(),
		})

		return schema.safeParse({ ...this })
	}

}
