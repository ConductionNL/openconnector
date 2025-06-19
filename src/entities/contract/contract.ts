/* eslint-disable @typescript-eslint/no-explicit-any */
import { SafeParseReturnType, z } from 'zod'
import { TContract } from './contract.types'
import getValidISOstring from '../../services/getValidISOstring.js'
import ReadonlyBaseClass from '../ReadonlyBaseClass.js'
import _ from 'lodash'

export class Contract extends ReadonlyBaseClass implements TContract {

	public id: number
	public uuid: string
	public version: string
	public synchronizationId: string
	public originId: string
	public originHash: string
	public sourceLastChanged: string
	public sourceLastChecked: string
	public sourceLastSynced: string
	public targetId: string
	public targetHash: string
	public targetLastChanged: string
	public targetLastChecked: string
	public targetLastSynced: string
	public targetLastAction: string
	public created: string
	public updated: string

	constructor(contract: TContract) {
		const processedContract: TContract = {
			id: contract.id || null,
			uuid: contract.uuid || '',
			version: contract.version || '0.0.1',
			synchronizationId: contract.synchronizationId || (contract as any).synchronization_id || '',
			originId: contract.originId || (contract as any).origin_id || '',
			originHash: contract.originHash || (contract as any).origin_hash || '',
			sourceLastChanged: contract.sourceLastChanged || (contract as any).source_last_changed || '',
			sourceLastChecked: contract.sourceLastChecked || (contract as any).source_last_checked || '',
			sourceLastSynced: contract.sourceLastSynced || (contract as any).source_last_synced || '',
			targetId: contract.targetId || (contract as any).target_id || '',
			targetHash: contract.targetHash || (contract as any).target_hash || '',
			targetLastChanged: contract.targetLastChanged || (contract as any).target_last_changed || '',
			targetLastChecked: contract.targetLastChecked || (contract as any).target_last_checked || '',
			targetLastSynced: contract.targetLastSynced || (contract as any).target_last_synced || '',
			targetLastAction: contract.targetLastAction || (contract as any).target_last_action || '',
			created: getValidISOstring(contract.created) ?? '',
			updated: getValidISOstring(contract.updated) ?? '',
		}

		super(processedContract)
	}

	public cloneRaw(): TContract {
		return _.cloneDeep(this)
	}

	public validate(): SafeParseReturnType<TContract, unknown> {
		const schema = z.object({
			id: z.number().nullable(),
			uuid: z.string().optional(),
			version: z.string().optional(),
			synchronizationId: z.string().optional(),
			originId: z.string().optional(),
			originHash: z.string().optional(),
			sourceLastChanged: z.string().optional(),
			sourceLastChecked: z.string().optional(),
			sourceLastSynced: z.string().optional(),
			targetId: z.string().optional(),
			targetHash: z.string().optional(),
			targetLastChanged: z.string().optional(),
			targetLastChecked: z.string().optional(),
			targetLastSynced: z.string().optional(),
			targetLastAction: z.string().optional(),
			created: z.string().optional(),
			updated: z.string().optional(),
		})

		return schema.safeParse({ ...this })
	}

	/**
	 * Generate a human-readable contract display name
	 *
	 * @return {string} Generated contract display name
	 */
	public getDisplayName(): string {
		return `Contract: ${this.id}`
	}

	/**
	 * Get the contract sync status (for display purposes only)
	 * Note: Contracts cannot be "activated" or "deactivated" - they exist or don't exist
	 *
	 * @return {string} Sync status indicator
	 */
	public getSyncStatus(): string {
		// Check if contract has recent sync activity
		const hasRecentSourceSync = this.sourceLastSynced
			&& new Date(this.sourceLastSynced).getTime() > (Date.now() - 24 * 60 * 60 * 1000) // 24 hours
		const hasRecentTargetSync = this.targetLastSynced
			&& new Date(this.targetLastSynced).getTime() > (Date.now() - 24 * 60 * 60 * 1000) // 24 hours

		if (hasRecentSourceSync || hasRecentTargetSync) {
			return 'synced'
		}

		if (this.sourceLastSynced || this.targetLastSynced) {
			return 'stale'
		}

		return 'unsynced'
	}

	/**
	 * Get the most recent sync date from either source or target
	 *
	 * @return {string|null} Most recent sync date
	 */
	public getLastSyncDate(): string | null {
		const sourceTimestamp = this.sourceLastSynced ? new Date(this.sourceLastSynced).getTime() : 0
		const targetTimestamp = this.targetLastSynced ? new Date(this.targetLastSynced).getTime() : 0

		if (sourceTimestamp === 0 && targetTimestamp === 0) {
			return null
		}

		const mostRecent = Math.max(sourceTimestamp, targetTimestamp)
		return new Date(mostRecent).toISOString()
	}

	/**
	 * Check if hashes match between source and target
	 *
	 * @return {boolean} Whether hashes are synchronized
	 */
	public isHashSynchronized(): boolean {
		return !!(this.originHash && this.targetHash && this.originHash === this.targetHash)
	}

	/**
	 * Get the synchronization progress indicator
	 *
	 * @return {string} Progress status
	 */
	public getSyncProgress(): string {
		if (!this.originId) {
			return 'no-source'
		}
		if (!this.targetId) {
			return 'no-target'
		}
		if (this.isHashSynchronized()) {
			return 'synchronized'
		}
		return 'out-of-sync'
	}

	/**
	 * Get a short description of the last action
	 *
	 * @return {string} Last action description
	 */
	public getLastAction(): string {
		return this.targetLastAction || 'none'
	}

}
