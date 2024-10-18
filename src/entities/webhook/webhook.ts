import { SafeParseReturnType, z } from 'zod'
import { TWebhook } from './webhook.types'
import getValidISOstring from '../../services/getValidISOstring.js'
import ReadonlyBaseClass from '../ReadonlyBaseClass.js'

export class Webhook extends ReadonlyBaseClass implements TWebhook {

	public readonly id: number
	public readonly name: string
	public readonly description: string
	public readonly version: string
	public readonly url: string
	public readonly isEnabled: boolean
	public readonly dateCreated: string
	public readonly dateModified: string
	public readonly headers: object
	public readonly events: string[]
	public readonly retryPolicy: object
	public readonly timeout: number
	public readonly lastTriggered: string
	public readonly lastResponse: {
		status: number,
		body: string
	}

	public readonly secretKey: string
	public readonly payloadFormat: 'json' | 'xml' | 'form-data'
	public readonly active: boolean

	constructor(webhook: TWebhook) {
		const processedWebhook: TWebhook = {
			id: webhook.id || null,
			name: webhook.name || '',
			description: webhook.description || '',
			version: webhook.version || '',
			url: webhook.url || '',
			isEnabled: webhook.isEnabled ?? false,
			dateCreated: getValidISOstring(webhook.dateCreated) ?? '',
			dateModified: getValidISOstring(webhook.dateModified) ?? '',
			headers: webhook.headers || {},
			events: webhook.events || [],
			retryPolicy: webhook.retryPolicy || {},
			timeout: webhook.timeout || 0,
			lastTriggered: getValidISOstring(webhook.lastTriggered) ?? '',
			lastResponse: webhook.lastResponse || { status: 0, body: '' },
			secretKey: webhook.secretKey || '',
			payloadFormat: webhook.payloadFormat || 'json',
			active: webhook.active ?? false,
		}

		super(processedWebhook)
	}

	public validate(): SafeParseReturnType<TWebhook, unknown> {
		const schema = z.object({
			id: z.number(),
			name: z.string().max(255),
			description: z.string(),
			version: z.string(),
			url: z.string().url(),
			isEnabled: z.boolean(),
			dateCreated: z.string().datetime(),
			dateModified: z.string().datetime(),
			headers: z.record(z.string()),
			events: z.array(z.string()),
			retryPolicy: z.object({}),
			timeout: z.number().positive(),
			lastTriggered: z.string().datetime().optional(),
			lastResponse: z.object({
				status: z.number(),
				body: z.string(),
			}),
			secretKey: z.string(),
			payloadFormat: z.enum(['json', 'xml', 'form-data']),
			active: z.boolean(),
		})

		return schema.safeParse({ ...this })
	}

}
