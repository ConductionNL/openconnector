import { SafeParseReturnType, z } from 'zod'
import { TRule } from './rule.types'
import ReadonlyBaseClass from '../ReadonlyBaseClass.js'
import getValidISOstring from '../../services/getValidISOstring.js'
import _ from 'lodash'

/**
 * Rule entity class that represents a system rule
 * Implements TRule interface and extends ReadonlyBaseClass
 *
 * @class Rule
 * @augments {ReadonlyBaseClass}
 * @implements {TRule}
 */
export class Rule extends ReadonlyBaseClass implements TRule {

	public readonly id: string
	public readonly uuid: string
	public readonly name: string
	public readonly description: string
	public readonly action: 'create' | 'read' | 'update' | 'delete'
	public readonly timing: 'before' | 'after'
	public readonly conditions: object[] // JSON Logic format conditions
	public readonly type: 'mapping' | 'error' | 'script' | 'synchronization' | 'authentication' | 'download' | 'upload' | 'locking' | 'extend_input' | 'extend_external_input' | 'fetch_file' | 'write_file' | 'fileparts_create' | 'filepart_upload' | 'save_object' | 'javascript'
	public readonly configuration: object // Type-specific configuration
	public readonly order: number // Order in which the rule should be applied
	public readonly created: string
	public readonly updated: string

	/**
	 * Creates a new Rule instance
	 * @param {TRule} rule - Rule data to initialize with
	 */
	constructor(rule: TRule) {
		// Process and set default values for all rule properties
		const processedRule: TRule = {
			id: rule.id || '',
			uuid: rule.uuid || '',
			name: rule.name || '',
			description: rule.description || '',
			action: rule.action || 'create',
			timing: rule.timing || 'before',
			conditions: rule.conditions || [],
			type: rule.type || 'mapping',
			configuration: rule.configuration || {},
			order: rule.order || 0,
			created: getValidISOstring(rule.created) ?? '',
			updated: getValidISOstring(rule.updated) ?? '',
		}

		super(processedRule)
	}

	public cloneRaw(): TRule {
		return _.cloneDeep(this)
	}

	/**
	 * Validates the rule object against a schema
	 * @return {SafeParseReturnType<TRule, unknown>} Validation result
	 */
	public validate(): SafeParseReturnType<TRule, unknown> {
		const schema = z.object({
			id: z.string(),
			uuid: z.string().uuid(),
			name: z.string().max(255),
			description: z.string().nullable(),
			action: z.enum(['create', 'read', 'update', 'delete']),
			timing: z.enum(['before', 'after']),
			conditions: z.array(z.record(z.unknown())), // JSON Logic format
			type: z.enum(['mapping', 'error', 'script', 'synchronization', 'authentication', 'download', 'upload', 'locking', 'extend_input', 'extend_external_input', 'fetch_file', 'write_file', 'fileparts_create', 'filepart_upload', 'save_object', 'javascript']),
			configuration: z.record(z.unknown()),
			order: z.number().int().min(0),
			created: z.string(),
			updated: z.string(),
		})

		return schema.safeParse({ ...this })
	}

}
