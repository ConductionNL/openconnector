import { ref } from 'vue'
import { defineStore } from 'pinia'
import { Log, TLog } from '../../entities/index.js'
import { MissingParameterError } from '../../services/errors/index.js'

const apiEndpoint = '/index.php/apps/openconnector/api/logs'

/**
 * Log store for managing synchronization logs
 *
 * @description
 * This store manages the state and operations for synchronization logs,
 * including fetching, creating, updating, and deleting logs.
 */
export const useLogStore = defineStore('log', () => {
	// ################################
	// ||           State            ||
	// ################################

	/** @type {import('vue').Ref<Log|null>} Current log item */
	const logItem = ref<Log>(null)

	/** @type {import('vue').Ref<Log[]>} Legacy log list */
	const logList = ref<Log[]>([])

	/** @type {import('vue').Ref<Log[]>} List of logs (new naming) */
	const logsList = ref<Log[]>([])

	/** @type {import('vue').Ref<string|null>} Active log key */
	const activeLogKey = ref<string>(null)

	/** @type {import('vue').Ref<object|null>} View log item */
	const viewLogItem = ref<Record<string, unknown>>(null)

	/** @type {import('vue').Ref<boolean>} Loading state for logs */
	const logsLoading = ref<boolean>(false)

	/** @type {import('vue').Ref<object>} Pagination information */
	const logsPagination = ref<object>({
		page: 1,
		pages: 1,
		results: 0,
		total: 0,
	})

	/** @type {import('vue').Ref<object>} Current filters */
	const logsFilters = ref<object>({})

	/** @type {import('vue').Ref<object>} Statistics data */
	const logsStatistics = ref<object>({})

	// ################################
	// ||    Setters and Getters     ||
	// ################################

	/**
	 * Set the active log item
	 *
	 * @param {Log|TLog|null} item - The log item to set
	 * @return {void}
	 */
	const setLogItem = (item: Log | TLog | null): void => {
		logItem.value = item && new Log(item)
		console.info('Active log item set to ' + (item ? item.id : 'null'))
	}

	/**
	 * Get the active log item
	 *
	 * @description
	 * Returns the currently active log item. Note that the return value is non-reactive.
	 *
	 * For reactive usage, either:
	 * 1. Reference the `logItem` state directly:
	 * ```js
	 * const logItem = useLogStore().logItem // reactive state
	 * ```
	 * 2. Or wrap in a `computed` property:
	 * ```js
	 * const logItem = computed(() => useLogStore().getLogItem())
	 * ```
	 *
	 * @return {Log | null} The active log item
	 */
	const getLogItem = (): Log | null => logItem.value as Log | null

	/**
	 * Set the log list (legacy)
	 *
	 * @param {Log[]|TLog[]} list - The log list to set
	 * @return {void}
	 */
	const setLogList = (list: Log[] | TLog[]): void => {
		logList.value = list.map((item) => new Log(item))
		logsList.value = logList.value // Keep both in sync
		console.info('Log list set to ' + list.length + ' items')
	}

	/**
	 * Set the logs list (new naming)
	 *
	 * @param {Log[]|TLog[]} list - The logs list to set
	 * @return {void}
	 */
	const setLogsList = (list: Log[] | TLog[]): void => {
		logsList.value = list.map((item) => new Log(item))
		logList.value = logsList.value // Keep both in sync
		console.info('Logs list set to ' + list.length + ' items')
	}

	/**
	 * Get the log list (legacy)
	 *
	 * @description
	 * Returns the currently active log list. Note that the return value is non-reactive.
	 *
	 * For reactive usage, either:
	 * 1. Reference the `logList` state directly:
	 * ```js
	 * const logList = useLogStore().logList // reactive state
	 * ```
	 * 2. Or wrap in a `computed` property:
	 * ```js
	 * const logList = computed(() => useLogStore().getLogList())
	 * ```
	 *
	 * @return {Log[]} The log list
	 */
	const getLogList = (): Log[] => logList.value as Log[]

	/**
	 * Get the logs list (new naming)
	 *
	 * @description
	 * Returns the currently active logs list. Note that the return value is non-reactive.
	 *
	 * For reactive usage, either:
	 * 1. Reference the `logsList` state directly:
	 * ```js
	 * const logsList = useLogStore().logsList // reactive state
	 * ```
	 * 2. Or wrap in a `computed` property:
	 * ```js
	 * const logsList = computed(() => useLogStore().getLogsList())
	 * ```
	 *
	 * @return {Log[]} The logs list
	 */
	const getLogsList = (): Log[] => logsList.value as Log[]

	/**
	 * Set the active log key
	 *
	 * @param {string} key - The log key to set
	 * @return {void}
	 */
	const setActiveLogKey = (key: string): void => {
		activeLogKey.value = key
		console.info('Active log key set to ' + key)
	}

	/**
	 * Get the active log key
	 *
	 * @description
	 * Returns the currently active log key. Note that the return value is non-reactive.
	 *
	 * For reactive usage, either:
	 * 1. Reference the `activeLogKey` state directly:
	 * ```js
	 * const activeLogKey = useLogStore().activeLogKey // reactive state
	 * ```
	 * 2. Or wrap in a `computed` property:
	 * ```js
	 * const activeLogKey = computed(() => useLogStore().getActiveLogKey())
	 * ```
	 *
	 * @return {string | null} The active log key
	 */
	const getActiveLogKey = (): string | null => activeLogKey.value as string | null

	/**
	 * Set the view log item
	 *
	 * @param {object} item - The log item to set
	 * @return {void}
	 */
	const setViewLogItem = (item: Record<string, unknown>): void => {
		viewLogItem.value = item
		console.info('Active view log item set to ' + (item ? item.id : 'null'))
	}

	/**
	 * Get the view log item
	 *
	 * @description
	 * Returns the currently active view log item. Note that the return value is non-reactive.
	 *
	 * For reactive usage, either:
	 * 1. Reference the `viewLogItem` state directly:
	 * ```js
	 * const viewLogItem = useLogStore().viewLogItem // reactive state
	 * ```
	 * 2. Or wrap in a `computed` property:
	 * ```js
	 * const viewLogItem = computed(() => useLogStore().getViewLogItem())
	 * ```
	 *
	 * @return {Record<string, unknown>} The view log item
	 */
	const getViewLogItem = (): Record<string, unknown> => viewLogItem.value as Record<string, unknown>

	/**
	 * Set logs filters
	 *
	 * @param {object} filters - The filters to set
	 * @return {void}
	 */
	const setLogsFilters = (filters: object): void => {
		logsFilters.value = filters
		console.info('Logs filters set', filters)
	}

	/**
	 * Set logs loading state
	 *
	 * @param {boolean} loading - The loading state
	 * @return {void}
	 */
	const setLogsLoading = (loading: boolean): void => {
		logsLoading.value = loading
	}

	/**
	 * Set logs pagination
	 *
	 * @param {object} pagination - The pagination object
	 * @return {void}
	 */
	const setLogsPagination = (pagination: object): void => {
		logsPagination.value = pagination
	}

	// ################################
	// ||          Actions           ||
	// ################################

	/**
	 * Fetch logs from the API (new method expected by components)
	 *
	 * @param {object} options - Request options
	 * @param {number} options.page - Page number
	 * @param {object} options.filters - Filters to apply
	 * @return {Promise<{ response: Response, data: TLog[], entities: Log[] }>}
	 */
	const fetchLogs = async (options: { page?: number, filters?: object } = {}): Promise<{ response: Response, data: TLog[], entities: Log[] }> => {
		setLogsLoading(true)

		try {
			const queryParams = new URLSearchParams()

			// Add page parameter
			if (options.page) {
				queryParams.append('page', options.page.toString())
			}

			// Add filters
			if (options.filters) {
				Object.entries(options.filters).forEach(([key, value]) => {
					if (value !== null && value !== '') {
						queryParams.append(key, value.toString())
					}
				})
			}

			// Build the endpoint with query params
			let endpoint = apiEndpoint
			if (queryParams.toString()) {
				endpoint += '?' + queryParams.toString()
			}

			const response = await fetch(endpoint, {
				method: 'GET',
			})

			const responseData = await response.json()
			const data = (responseData.results || responseData) as TLog[]
			const entities = data.map(logItem => new Log(logItem))

			setLogsList(data)

			// Set pagination if available
			if (responseData.pagination) {
				setLogsPagination(responseData.pagination)
			}

			return { response, data, entities }
		} finally {
			setLogsLoading(false)
		}
	}

	/**
	 * Refresh the log list (legacy method)
	 *
	 * @param {string} search - The search string to filter the list
	 * @return {Promise<{ response: Response, data: TLog[], entities: Log[] }>} The response, data, and entities
	 */
	const refreshLogList = async (search: string = null): Promise<{ response: Response, data: TLog[], entities: Log[] }> => {
		const filters = search ? { search } : {}
		return await fetchLogs({ filters })
	}

	/**
	 * Fetch a single log
	 *
	 * @param {string} id - The ID of the log to fetch
	 * @return {Promise<{ response: Response, data: TLog, entity: Log }>} The response, data, and entity
	 */
	const fetchLog = async (id: string): Promise<{ response: Response, data: TLog, entity: Log }> => {
		if (!id) {
			throw new MissingParameterError('id')
		}

		const endpoint = `${apiEndpoint}/${id}`

		const response = await fetch(endpoint, {
			method: 'GET',
		})

		const data = await response.json() as TLog
		const entity = new Log(data)

		setLogItem(data)

		return { response, data, entity }
	}

	/**
	 * Delete a log
	 *
	 * @param {string} id - The ID of the log to delete
	 * @return {Promise<{ response: Response }>} The response
	 */
	const deleteLog = async (id: string): Promise<{ response: Response }> => {
		if (!id) {
			throw new MissingParameterError('id')
		}

		console.info('Deleting log...')

		const endpoint = `${apiEndpoint}/${id}`

		const response = await fetch(endpoint, {
			method: 'DELETE',
		})

		if (response.ok && logItem.value?.id === parseInt(id)) {
			setLogItem(null)
		}

		return { response }
	}

	/**
	 * Delete multiple logs
	 *
	 * @param {string[]} ids - Array of log IDs to delete
	 * @return {Promise<void>}
	 */
	const deleteMultiple = async (ids: string[]): Promise<void> => {
		if (!ids || ids.length === 0) return

		console.info('Deleting multiple logs...')

		// Delete logs one by one (can be optimized with bulk API later)
		await Promise.all(ids.map(id => deleteLog(id)))
	}

	/**
	 * Export logs
	 *
	 * @return {Promise<{ response: Response }>}
	 */
	const exportLogs = async (): Promise<{ response: Response }> => {
		console.info('Exporting logs...')

		const endpoint = `${apiEndpoint}/export`

		const response = await fetch(endpoint, {
			method: 'GET',
		})

		// Handle file download
		if (response.ok) {
			const blob = await response.blob()
			const url = window.URL.createObjectURL(blob)
			const a = document.createElement('a')
			a.href = url
			a.download = 'logs.csv'
			document.body.appendChild(a)
			a.click()
			window.URL.revokeObjectURL(url)
			document.body.removeChild(a)
		}

		return { response }
	}

	/**
	 * Fetch log statistics
	 *
	 * @return {Promise<{ response: Response, data: object }>}
	 */
	const fetchStatistics = async (): Promise<{ response: Response, data: object }> => {
		const endpoint = `${apiEndpoint}/statistics`

		const response = await fetch(endpoint, {
			method: 'GET',
		})

		const data = await response.json()
		logsStatistics.value = data

		return { response, data }
	}

	/**
	 * Save a log
	 *
	 * @param {Log} logItem - The log item to save
	 * @return {Promise<{ response: Response, data: TLog, entity: Log }>} The response, data, and entity
	 */
	const saveLog = async (logItem: Log): Promise<{ response: Response, data: TLog, entity: Log }> => {
		if (!logItem) {
			throw new MissingParameterError('logItem')
		}
		if (!(logItem instanceof Log)) {
			throw new Error('logItem is not an instance of Log')
		}

		console.info('Saving log...')

		const isNewLog = !logItem.id
		const endpoint = isNewLog
			? apiEndpoint
			: `${apiEndpoint}/${logItem.id}`
		const method = isNewLog ? 'POST' : 'PUT'

		const response = await fetch(
			endpoint,
			{
				method,
				headers: {
					'Content-Type': 'application/json',
				},
				body: JSON.stringify(logItem.cloneRaw()),
			},
		)

		const data = await response.json() as TLog
		const entity = new Log(data)

		setLogItem(data)

		return { response, data, entity }
	}

	return {
		// state
		logItem,
		logList,
		logsList,
		activeLogKey,
		viewLogItem,
		logsLoading,
		logsPagination,
		logsFilters,
		logsStatistics,

		// setters and getters
		setLogItem,
		getLogItem,
		setLogList,
		getLogList,
		setLogsList,
		getLogsList,
		setActiveLogKey,
		getActiveLogKey,
		setViewLogItem,
		getViewLogItem,
		setLogsFilters,
		setLogsLoading,
		setLogsPagination,

		// actions
		fetchLogs,
		refreshLogList,
		fetchLog,
		deleteLog,
		deleteMultiple,
		exportLogs,
		fetchStatistics,
		saveLog,
	}
})
