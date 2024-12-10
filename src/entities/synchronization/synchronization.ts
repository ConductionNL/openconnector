import { SafeParseReturnType, z } from 'zod'
import { TSynchronization } from './synchronization.types'
import getValidISOstring from '../../services/getValidISOstring.js'
import ReadonlyBaseClass from '../ReadonlyBaseClass.js'

export class Synchronization extends ReadonlyBaseClass implements TSynchronization {

	public id: number
	public name: string
	public description: string
	public conditions: string
	public sourceId: string
	public sourceType: string
	public sourceHash: string
	public sourceTargetMapping: string
	public sourceConfig: Record<string, string>
	public sourceLastChanged: string
	public sourceLastChecked: string
	public sourceLastSynced: string
	public targetId: string
	public targetType: string
	public targetHash: string
	public targetSourceMapping: string
	public targetConfig: Record<string, string>
	public targetLastChanged: string
	public targetLastChecked: string
	public targetLastSynced: string
	public created: string
	public updated: string

	constructor(synchronization: TSynchronization) {
		const processedSynchronization: TSynchronization = {
			id: synchronization.id || null,
			name: synchronization.name || '',
			description: synchronization.description || '',
			conditions: synchronization.conditions || '',
			sourceId: synchronization.sourceId || '',
			sourceType: synchronization.sourceType || '',
			sourceHash: synchronization.sourceHash || '',
			sourceTargetMapping: synchronization.sourceTargetMapping || '',
			sourceConfig: synchronization.sourceConfig || {},
			sourceLastChanged: synchronization.sourceLastChanged || '',
			sourceLastChecked: synchronization.sourceLastChecked || '',
			sourceLastSynced: synchronization.sourceLastSynced || '',
			targetId: synchronization.targetId || '',
			targetType: synchronization.targetType || '',
			targetHash: synchronization.targetHash || '',
			targetSourceMapping: synchronization.targetSourceMapping || '',
			targetConfig: synchronization.targetConfig || {},
			targetLastChanged: synchronization.targetLastChanged || '',
			targetLastChecked: synchronization.targetLastChecked || '',
			targetLastSynced: synchronization.targetLastSynced || '',
			created: getValidISOstring(synchronization.created) ?? '',
			updated: getValidISOstring(synchronization.updated) ?? '',
		}

		super(processedSynchronization)
	}

	public validate(): SafeParseReturnType<TSynchronization, unknown> {
		const schema = z.object({
			id: z.number().nullable(),
			name: z.string(),
			description: z.string(),
			conditions: z.string(),
			sourceId: z.string(),
			sourceType: z.string(),
			sourceHash: z.string(),
			sourceTargetMapping: z.string(),
			sourceConfig: z.record(z.string(), z.string()),
			sourceLastChanged: z.string(),
			sourceLastChecked: z.string(),
			sourceLastSynced: z.string(),
			targetId: z.string(),
			targetType: z.string(),
			targetHash: z.string(),
			targetSourceMapping: z.string(),
			targetConfig: z.record(z.string(), z.string()),
			targetLastChanged: z.string(),
			targetLastChecked: z.string(),
			targetLastSynced: z.string(),
			created: z.string(),
			updated: z.string(),
		})

		return schema.safeParse({ ...this })
	}

}
