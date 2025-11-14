/* eslint-disable @typescript-eslint/no-explicit-any */
import { SafeParseReturnType, z } from 'zod'
import { TSource } from './source.types'
import getValidISOstring from '../../services/getValidISOstring.js'
import ReadonlyBaseClass from '../ReadonlyBaseClass.js'
import _ from 'lodash'

export class Source extends ReadonlyBaseClass implements TSource {

	public readonly id: string
	public readonly uuid: string
	public readonly name: string
	public readonly description: string
	public readonly reference: string
	public readonly version: string
	public readonly location: string
	public readonly isEnabled: boolean
	public readonly type: 'json' | 'xml' | 'soap' | 'ftp' | 'sftp'
	public readonly authorizationHeader: string
	public readonly auth: 'apikey' | 'jwt' | 'username-password' | 'none' | 'jwt-HS256' | 'vrijbrp-jwt' | 'pink-jwt' | 'oauth'
	public readonly authenticationConfig: object
	public readonly authorizationPassthroughMethod: 'header' | 'query' | 'form_params' | 'json' | 'base_auth'
	public readonly locale: string
	public readonly accept: string
	public readonly jwt: string
	public readonly jwtId: string
	public readonly secret: string
	public readonly username: string
	public readonly password: string
	public readonly apikey: string
	public readonly documentation: string
	public readonly loggingConfig: object
	public readonly oas: any[]
	public readonly paths: any[]
	public readonly headers: any[]
	public readonly translationConfig: any[]
	public readonly configuration: object
	public readonly endpointsConfig: object
	public readonly status: string
	public readonly logRetention: number
	public readonly errorRetention: number
	public readonly lastCall: string
	public readonly lastSync: string
	public readonly objectCount: number
	public readonly dateCreated: string
	public readonly dateModified: string
	public readonly test: boolean

	constructor(source: TSource) {
		const processedSource: TSource = {
			id: source.id || null,
			uuid: source.uuid || '',
			name: source.name || '',
			description: source.description || '',
			reference: source.reference || '',
			version: source.version || '',
			location: source.location || '',
			isEnabled: source.isEnabled ?? true,
			type: source.type || 'json',
			authorizationHeader: source.authorizationHeader || '',
			auth: source.auth || 'none',
			authenticationConfig: source.authenticationConfig || {},
			authorizationPassthroughMethod: source.authorizationPassthroughMethod || 'header',
			locale: source.locale || '',
			accept: source.accept || '',
			jwt: source.jwt || '',
			jwtId: source.jwtId || '',
			secret: source.secret || '',
			username: source.username || '',
			password: source.password || '',
			apikey: source.apikey || '',
			documentation: source.documentation || '',
			loggingConfig: source.loggingConfig || {},
			oas: source.oas || [],
			paths: source.paths || [],
			headers: source.headers || [],
			translationConfig: source.translationConfig || [],
			configuration: source.configuration || {},
			endpointsConfig: source.endpointsConfig || {},
			status: source.status || '',
			logRetention: source.logRetention || 3600,
			errorRetention: source.errorRetention || 86400,
			lastCall: source.lastCall || '',
			lastSync: source.lastSync || '',
			objectCount: source.objectCount || 0,
			dateCreated: getValidISOstring(source.dateCreated) ?? null,
			dateModified: getValidISOstring(source.dateModified) ?? null,
			test: source.test || false,
		}

		super(processedSource)
	}

	public cloneRaw(): TSource {
		return _.cloneDeep(this)
	}

	public validate(): SafeParseReturnType<TSource, unknown> {
		const schema = z.object({
			id: z.union([z.string(), z.number()]).nullable(),
			uuid: z.string(),
			name: z.string().max(255),
			description: z.string(),
			reference: z.string(),
			version: z.string(),
			location: z.string().max(255),
			isEnabled: z.boolean(),
			type: z.enum(['json', 'xml', 'soap', 'ftp', 'sftp', 'api', 'database', 'file']),
			authorizationHeader: z.string(),
			auth: z.enum(['apikey', 'jwt', 'username-password', 'none', 'jwt-HS256', 'vrijbrp-jwt', 'pink-jwt', 'oauth']),
			authenticationConfig: z.object({}),
			authorizationPassthroughMethod: z.enum(['header', 'query', 'form_params', 'json', 'base_auth']),
			locale: z.string(),
			accept: z.string(),
			jwt: z.string(),
			jwtId: z.string(),
			secret: z.string(),
			username: z.string(),
			password: z.string(),
			apikey: z.string(),
			documentation: z.string(),
			loggingConfig: z.object({}),
			oas: z.array(z.any()),
			paths: z.array(z.any()),
			headers: z.array(z.any()),
			translationConfig: z.array(z.any()),
			configuration: z.object({}),
			endpointsConfig: z.object({}),
			status: z.string(),
			logRetention: z.number(),
			errorRetention: z.number(),
			lastCall: z.string(),
			lastSync: z.string(),
			objectCount: z.number(),
			dateCreated: z.string().datetime().nullable(),
			dateModified: z.string().datetime().nullable(),
			test: z.boolean(),
		})

		return schema.safeParse({ ...this })
	}

}
