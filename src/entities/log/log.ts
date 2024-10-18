import { SafeParseReturnType, z } from 'zod'
import { TLog } from './log.types'
import ReadonlyBaseClass from '../ReadonlyBaseClass.js'
import getValidISOstring from '../../services/getValidISOstring.js'

export class Log extends ReadonlyBaseClass implements TLog {

	public readonly id: string
	public readonly type: 'in' | 'out'
	public readonly callId: string
	public readonly requestMethod: string
	public readonly requestHeaders: object[]
	public readonly requestQuery: object[]
	public readonly requestPathInfo: string
	public readonly requestLanguages: string[]
	public readonly requestServer: object
	public readonly requestContent: string
	public readonly responseStatus: string
	public readonly responseStatusCode: number
	public readonly responseHeaders: object[]
	public readonly responseContent: string
	public readonly userId: string
	public readonly session: string
	public readonly sessionValues: object
	public readonly responseTime: number
	public readonly routeName: string
	public readonly routeParameters: object
	public readonly entity: object
	public readonly endpoint: object
	public readonly gateway: object
	public readonly handler: object
	public readonly objectId: string
	public readonly dateCreated: string
	public readonly dateModified: string

	constructor(log: TLog) {
		const processedLog: TLog = {
			id: log.id || '',
			type: log.type || 'in',
			callId: log.callId || '',
			requestMethod: log.requestMethod || '',
			requestHeaders: log.requestHeaders || [],
			requestQuery: log.requestQuery || [],
			requestPathInfo: log.requestPathInfo || '',
			requestLanguages: log.requestLanguages || [],
			requestServer: log.requestServer || {},
			requestContent: log.requestContent || '',
			responseStatus: log.responseStatus || '',
			responseStatusCode: log.responseStatusCode || 0,
			responseHeaders: log.responseHeaders || [],
			responseContent: log.responseContent || '',
			userId: log.userId || '',
			session: log.session || '',
			sessionValues: log.sessionValues || {},
			responseTime: log.responseTime || 0,
			routeName: log.routeName || '',
			routeParameters: log.routeParameters || {},
			entity: log.entity || {},
			endpoint: log.endpoint || {},
			gateway: log.gateway || {},
			handler: log.handler || {},
			objectId: log.objectId || '',
			dateCreated: getValidISOstring(log.dateCreated) ?? '',
			dateModified: getValidISOstring(log.dateModified) ?? '',
		}

		super(processedLog)
	}

	public validate(): SafeParseReturnType<TLog, unknown> {
		const schema = z.object({
			id: z.string().nullable(),
			type: z.enum(['in', 'out']),
			callId: z.string().uuid(),
			requestMethod: z.string().max(255),
			requestHeaders: z.array(z.record(z.string(), z.string())),
			requestQuery: z.array(z.record(z.string(), z.string())),
			requestPathInfo: z.string().max(255),
			requestLanguages: z.array(z.string()),
			requestServer: z.record(z.string(), z.unknown()),
			requestContent: z.string(),
			responseStatus: z.string(),
			responseStatusCode: z.number().int(),
			responseHeaders: z.array(z.record(z.string(), z.string())),
			responseContent: z.string(),
			userId: z.string(),
			session: z.string().max(255),
			sessionValues: z.record(z.string(), z.unknown()),
			responseTime: z.number().int(),
			routeName: z.string(),
			routeParameters: z.record(z.string(), z.unknown()),
			entity: z.record(z.string(), z.unknown()),
			endpoint: z.record(z.string(), z.unknown()),
			gateway: z.record(z.string(), z.unknown()),
			handler: z.record(z.string(), z.unknown()),
			objectId: z.string(),
			dateCreated: z.string().datetime().nullable(),
			dateModified: z.string().datetime().nullable(),
		})

		return schema.safeParse({ ...this })
	}

}
