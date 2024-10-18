/* eslint-disable @typescript-eslint/no-explicit-any */
import { SafeParseReturnType, z } from 'zod'
import { TCallLog } from './callLog.types'
import getValidISOstring from '../../services/getValidISOstring.js'
import ReadonlyBaseClass from '../ReadonlyBaseClass.js'

export class CallLog extends ReadonlyBaseClass implements TCallLog {

	public readonly id: number
	public readonly sourceId: string
	public readonly endpoint: string
	public readonly method: 'GET' | 'POST' | 'PUT' | 'DELETE' | 'PATCH'
	public readonly statusCode: number
	public readonly requestHeaders: object
	public readonly requestBody: any
	public readonly responseHeaders: object
	public readonly responseBody: any
	public readonly duration: number
	public readonly error: string | null
	public readonly created: string
	public readonly updated: string | null

	constructor(callLog: TCallLog) {
		const processedCallLog = {
			id: callLog.id || '',
			sourceId: callLog.sourceId || '',
			endpoint: callLog.endpoint || '',
			method: callLog.method || 'GET',
			statusCode: callLog.statusCode || 0,
			requestHeaders: callLog.requestHeaders || {},
			requestBody: callLog.requestBody || {},
			responseHeaders: callLog.responseHeaders || {},
			responseBody: callLog.responseBody || {},
			duration: callLog.duration || 0,
			error: callLog.error || '',
			created: getValidISOstring(callLog.created) ?? '',
			updated: getValidISOstring(callLog.updated) ?? '',
		}

		super(processedCallLog)
	}

	public validate(): SafeParseReturnType<TCallLog, unknown> {
		const schema = z.object({
			id: z.number().nullable(),
			sourceId: z.string(),
			endpoint: z.string(),
			method: z.enum(['GET', 'POST', 'PUT', 'DELETE', 'PATCH']),
			statusCode: z.number().int().positive(),
			requestHeaders: z.record(z.any()),
			requestBody: z.any(),
			responseHeaders: z.record(z.any()),
			responseBody: z.any(),
			duration: z.number().positive(),
			error: z.string(),
			created: z.string().datetime().or(z.literal('')),
			updated: z.string().datetime().or(z.literal('')),
		})

		return schema.safeParse({ ...this })
	}

}
