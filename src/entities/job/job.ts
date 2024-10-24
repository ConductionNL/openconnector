import { SafeParseReturnType, z } from 'zod'
import { TJob } from './job.types'

export class Job implements TJob {

	public id: string
	public name: string
	public description: string | null
	public jobClass: string
	public arguments: object | null
	public interval: number
	public executionTime: number
	public timeSensitive: boolean
	public allowParallelRuns: boolean
	public isEnabled: boolean
	public singleRun: boolean
	public scheduleAfter: string | null
	public userId: string | null
	public jobListId: string | null
	public logRetention: number
	public errorRetention: number
	public lastRun: string | null
	public nextRun: string | null
	public created: string | null
	public updated: string | null
	public status: string

	constructor(job: TJob) {
		this.id = job.id || ''
		this.name = job.name || ''
		this.description = job.description || null
		this.jobClass = job.jobClass || 'OCA\\OpenConnector\\Action\\PingAction'
		this.arguments = job.arguments || null
		this.interval = job.interval || 3600
		this.executionTime = job.executionTime || 3600
		this.timeSensitive = job.timeSensitive ?? true
		this.allowParallelRuns = job.allowParallelRuns ?? false
		this.isEnabled = job.isEnabled ?? true
		this.singleRun = job.singleRun ?? false
		this.scheduleAfter = job.scheduleAfter || null
		this.userId = job.userId || null
		this.jobListId = job.jobListId || null
		this.logRetention = job.logRetention || 3600
		this.errorRetention = job.errorRetention || 86400
		this.lastRun = job.lastRun || null
		this.nextRun = job.nextRun || null
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
