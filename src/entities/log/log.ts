/* eslint-disable @typescript-eslint/no-explicit-any */
import { SafeParseReturnType, z } from 'zod'
import { TLog } from './log.types'
import ReadonlyBaseClass from '../ReadonlyBaseClass.js'
import getValidISOstring from '../../services/getValidISOstring.js'
import _ from 'lodash'

export class Log extends ReadonlyBaseClass implements TLog {

	public id: number
	public uuid: string
	public message: string
	public synchronizationId: string
	public result: any[]
	public userId: string
	public sessionId: string
	public test: boolean
	public force: boolean
	public executionTime: number
	public created: string
	public expires: string

	constructor(log: TLog) {
		const processedLog: TLog = {
			id: log.id || null,
			uuid: log.uuid || '',
			message: log.message || '',
			synchronizationId: log.synchronizationId || (log as any).synchronization_id || '',
			result: log.result || [],
			userId: log.userId || (log as any).user_id || '',
			sessionId: log.sessionId || (log as any).session_id || '',
			test: log.test || false,
			force: log.force || false,
			executionTime: log.executionTime || (log as any).execution_time || 0,
			created: getValidISOstring(log.created) ?? '',
			expires: getValidISOstring(log.expires) ?? '',
		}

		super(processedLog)
	}

	public cloneRaw(): TLog {
		return _.cloneDeep(this)
	}

	public validate(): SafeParseReturnType<TLog, unknown> {
		const schema = z.object({
			id: z.number().nullable(),
			uuid: z.string().optional(),
			message: z.string().optional(),
			synchronizationId: z.string().optional(),
			result: z.array(z.unknown()).optional(),
			userId: z.string().optional(),
			sessionId: z.string().optional(),
			test: z.boolean().optional(),
			force: z.boolean().optional(),
			executionTime: z.number().optional(),
			created: z.string().optional(),
			expires: z.string().optional(),
		})

		return schema.safeParse({ ...this })
	}

	/**
	 * Generate a human-readable log description
	 *
	 * @return {string} Generated log description
	 */
	public getDisplayName(): string {
		if (this.message) {
			// Truncate long messages
			return this.message.length > 50 ? this.message.substring(0, 50) + '...' : this.message
		} else if (this.uuid) {
			return `Log ${this.uuid.substring(0, 8)}`
		} else if (this.id) {
			return `Log ${this.id}`
		}
		return 'Log Entry'
	}

	/**
	 * Get log level based on result content or test flags
	 *
	 * @return {string} Log level (error, warning, info, success)
	 */
	public getLevel(): string {
		// Check if this is a test run
		if (this.test) {
			return 'info'
		}

		// Check if there are errors in the result
		if (this.result && Array.isArray(this.result)) {
			const hasErrors = this.result.some((item: any) =>
				item?.error || item?.status === 'error' || (item?.success === false),
			)
			if (hasErrors) {
				return 'error'
			}
		}

		// Check message content for error keywords
		if (this.message) {
			const lowerMessage = this.message.toLowerCase()
			if (lowerMessage.includes('error') || lowerMessage.includes('failed') || lowerMessage.includes('exception')) {
				return 'error'
			}
			if (lowerMessage.includes('warning') || lowerMessage.includes('warn')) {
				return 'warning'
			}
		}

		// Default to success for completed logs
		return 'success'
	}

	/**
	 * Get execution duration in human readable format
	 *
	 * @return {string} Formatted duration
	 */
	public getFormattedDuration(): string {
		if (!this.executionTime) {
			return '-'
		}

		if (this.executionTime < 1000) {
			return `${this.executionTime}ms`
		} else if (this.executionTime < 60000) {
			return `${(this.executionTime / 1000).toFixed(1)}s`
		} else {
			const minutes = Math.floor(this.executionTime / 60000)
			const seconds = Math.floor((this.executionTime % 60000) / 1000)
			return `${minutes}m ${seconds}s`
		}
	}

	/**
	 * Check if this is an error log
	 *
	 * @return {boolean} Whether this is an error log
	 */
	public isError(): boolean {
		return this.getLevel() === 'error'
	}

	/**
	 * Check if this is a test log
	 *
	 * @return {boolean} Whether this is a test log
	 */
	public isTest(): boolean {
		return this.test === true
	}

}
