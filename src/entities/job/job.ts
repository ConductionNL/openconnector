import { SafeParseReturnType, z } from 'zod'
import { TJob } from './job.types'
import ReadonlyBaseClass from '../ReadonlyBaseClass.js'
import getValidISOstring from '../../services/getValidISOstring.js'

export class Job extends ReadonlyBaseClass implements TJob {

	public readonly id: string
	public readonly name: string
	public readonly description: string
	public readonly jobClass: string
	public readonly arguments: object
	public readonly interval: number
	public readonly executionTime: number
	public readonly timeSensitive: boolean
	public readonly allowParallelRuns: boolean
	public readonly isEnabled: boolean
	public readonly singleRun: boolean
	public readonly scheduleAfter: string
	public readonly userId: string
	public readonly jobListId: string
	public readonly logRetention: number
	public readonly errorRetention: number
	public readonly lastRun: string
	public readonly nextRun: string
	public readonly created: string
	public readonly updated: string

	constructor(job: TJob) {
		const processedJob: TJob = {
			id: job.id || '',
			name: job.name || '',
			description: job.description || '',
			jobClass: job.jobClass || 'OCA\\OpenConnector\\Action\\PingAction',
			arguments: job.arguments || {},
			interval: job.interval || 3600,
			executionTime: job.executionTime || 3600,
			timeSensitive: job.timeSensitive ?? true,
			allowParallelRuns: job.allowParallelRuns ?? false,
			isEnabled: job.isEnabled ?? true,
			singleRun: job.singleRun ?? false,
			scheduleAfter: job.scheduleAfter || '',
			userId: job.userId || '',
			jobListId: job.jobListId || '',
			logRetention: job.logRetention || 3600,
			errorRetention: job.errorRetention || 86400,
			lastRun: job.lastRun || '',
			nextRun: job.nextRun || '',
			created: getValidISOstring(job.created) ?? '',
			updated: getValidISOstring(job.updated) ?? '',
		}

		super(processedJob)
	}

	public validate(): SafeParseReturnType<TJob, unknown> {
		const schema = z.object({
			id: z.string().uuid(),
			name: z.string().max(255),
			description: z.string().nullable(),
			jobClass: z.string(),
			arguments: z.record(z.unknown()).nullable(),
			interval: z.number().int().positive(),
			executionTime: z.number().int().positive(),
			timeSensitive: z.boolean(),
			allowParallelRuns: z.boolean(),
			isEnabled: z.boolean(),
			singleRun: z.boolean(),
			scheduleAfter: z.string().nullable(),
			userId: z.string().nullable(),
			jobListId: z.string().nullable(),
			logRetention: z.number().int().positive(),
			errorRetention: z.number().int().positive(),
			lastRun: z.string().nullable(),
			nextRun: z.string().nullable(),
		})

		return schema.safeParse({ ...this })
	}

}
