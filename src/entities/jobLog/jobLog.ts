import { SafeParseReturnType, z } from 'zod'
import { TJobLog } from './jobLog.types'
import getValidISOstring from '../../services/getValidISOstring.js'
import ReadonlyBaseClass from '../ReadonlyBaseClass.js'

export class JobLog extends ReadonlyBaseClass implements TJobLog {

	public readonly id: string
	public readonly uuid: string
	public readonly level: string
	public readonly message: string
	public readonly jobId: string
	public readonly jobListId: string
	public readonly jobClass: string
	public readonly arguments: object
	public readonly executionTime: number
	public readonly userId: string
	public readonly sessionId: string
	public readonly stackTrace: object[]
	public readonly expires: string
	public readonly lastRun: string
	public readonly nextRun: string
	public readonly created: string

	constructor(jobLog: TJobLog) {
		const processedJobLog: TJobLog = {
			id: jobLog.id || null,
			uuid: jobLog.uuid || '',
			level: jobLog.level || '',
			message: jobLog.message || '',
			jobId: jobLog.jobId || '',
			jobListId: jobLog.jobListId || '',
			jobClass: jobLog.jobClass || '',
			arguments: jobLog.arguments || {},
			executionTime: jobLog.executionTime || 0,
			userId: jobLog.userId || '',
			sessionId: jobLog.sessionId || '',
			stackTrace: jobLog.stackTrace || [],
			expires: jobLog.expires || '',
			lastRun: getValidISOstring(jobLog.lastRun) || '',
			nextRun: getValidISOstring(jobLog.nextRun) || '',
			created: getValidISOstring(jobLog.created) || '',
		}

		super(processedJobLog)
	}

	public validate(): SafeParseReturnType<TJobLog, unknown> {
		const schema = z.object({
			id: z.number().nullable(),
			uuid: z.string().uuid(),
			level: z.string(),
			message: z.string(),
			jobId: z.string(),
			jobListId: z.string(),
			jobClass: z.string(),
			arguments: z.record(z.any()),
			executionTime: z.number(),
			userId: z.string(),
			sessionId: z.string(),
			stackTrace: z.array(z.any()),
			expires: z.string(),
			lastRun: z.string(),
			nextRun: z.string(),
			created: z.string(),
		})

		return schema.safeParse({ ...this })
	}

}
