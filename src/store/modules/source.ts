import { ref } from 'vue'
import { defineStore } from 'pinia'
import { importExportStore } from '../store.js'
import { Source, TSource } from '../../entities/index.js'
import { MissingParameterError } from '../../services/errors/index.js'
import { useLogStore } from './log'

const apiEndpoint = '/index.php/apps/openconnector/api/sources'

export const useSourceStore = defineStore('source', () => {
	// state
	const sourceItem = ref<Source>(null)
	const sourceList = ref<Source[]>([])
	const sourceTest = ref<object>(null)
	const sourceLog = ref<object>(null)
	const sourceLogs = ref<object[]>([])
	const sourceConfigurationKey = ref<string | null>(null)
	const viewMode = ref<string>('cards')

	// ################################
	// ||    Setters and Getters     ||
	// ################################

	/**
	 * Set the active source item and fetch its logs.
	 *
	 * @param item - The source item to set
	 * @throws {Error} If there's an error fetching the logs
	 * @return {void}
	 */
	const setSourceItem = async (item: Source | TSource) => {
		// Set the source item
		sourceItem.value = item && new Source(item)
		console.info('Active source item set to ' + (item ? item.id : 'null'))

		// If we have a valid source item, fetch its logs
		if (item?.id) {
			try {
				await refreshSourceLogs()
				console.info('Source logs fetched for source ' + item.id)
			} catch (error) {
				console.error('Error fetching source logs:', error)
				throw error
			}
		} else {
			// Clear the logs if no source item is set
			setSourceLogs([])
		}
	}

	/**
	 * Get the active source item.
	 *
	 * @description
	 * Returns the currently active source item. Note that the return value is non-reactive.
	 *
	 * For reactive usage, either:
	 * 1. Reference the `sourceItem` state directly:
	 * ```js
	 * const sourceItem = useSourceStore().sourceItem // reactive state
	 * ```
	 * 2. Or wrap in a `computed` property:
	 * ```js
	 * const sourceItem = computed(() => useSourceStore().getSourceItem())
	 * ```
	 *
	 * @return {Source | null} The active source item
	 */
	const getSourceItem = (): Source | null => sourceItem.value as Source | null

	/**
	 * Set the active source list.
	 * @param list - The source list to set
	 */
	const setSourceList = (list: Source[] | TSource[]) => {
		sourceList.value = list.map((item) => new Source(item))
		console.info('Source list set to ' + list.length + ' items')
	}

	/**
	 * Get the active source list.
	 *
	 * @description
	 * Returns the currently active source list. Note that the return value is non-reactive.
	 *
	 * For reactive usage, either:
	 * 1. Reference the `sourceList` state directly:
	 * ```js
	 * const sourceList = useSourceStore().sourceList // reactive state
	 * ```
	 * 2. Or wrap in a `computed` property:
	 * ```js
	 * const sourceList = computed(() => useSourceStore().getSourceList())
	 * ```
	 *
	 * @return {Source[]} The active source list
	 */
	const getSourceList = (): Source[] => sourceList.value as Source[]

	/**
	 * Set the active source test results.
	 * @param item - The source test results to set
	 */
	const setSourceTest = (item: object) => {
		sourceTest.value = item
		console.info('Source test set to ' + item)
	}

	/**
	 * Get the active source test results.
	 *
	 * @description
	 * Returns the currently active source test results. Note that the return value is non-reactive.
	 *
	 * For reactive usage, either:
	 * 1. Reference the `sourceTest` state directly:
	 * ```js
	 * const sourceTest = useSourceStore().sourceTest // reactive state
	 * ```
	 * 2. Or wrap in a `computed` property:
	 * ```js
	 * const sourceTest = computed(() => useSourceStore().getSourceTest())
	 * ```
	 *
	 * @return {object} The active source test results
	 */
	const getSourceTest = (): object => sourceTest.value as object

	/**
	 * Set the active source log.
	 * @param item - The source log to set
	 */
	const setSourceLog = (item: object) => {
		sourceLog.value = item
		console.info('Source log set to ' + item)
	}

	/**
	 * Get the active source log.
	 *
	 * @description
	 * Returns the currently active source log. Note that the return value is non-reactive.
	 *
	 * For reactive usage, either:
	 * 1. Reference the `sourceLog` state directly:
	 * ```js
	 * const sourceLog = useSourceStore().sourceLog // reactive state
	 * ```
	 * 2. Or wrap in a `computed` property:
	 * ```js
	 * const sourceLog = computed(() => useSourceStore().getSourceLog())
	 * ```
	 *
	 * @return {object} The active source log
	 */
	const getSourceLog = (): object => sourceLog.value as object

	/**
	 * Set the active source logs.
	 * @param item - The source logs to set
	 */
	const setSourceLogs = (item: object[]) => {
		sourceLogs.value = item
		console.info('Source logs set to ' + item.length + ' items')
	}

	/**
	 * Get the active source logs.
	 *
	 * @description
	 * Returns the currently active source logs. Note that the return value is non-reactive.
	 *
	 * For reactive usage, either:
	 * 1. Reference the `sourceLogs` state directly:
	 * ```js
	 * const sourceLogs = useSourceStore().sourceLogs // reactive state
	 * ```
	 * 2. Or wrap in a `computed` property:
	 * ```js
	 * const sourceLogs = computed(() => useSourceStore().getSourceLogs())
	 * ```
	 *
	 * @return {object[]} The active source logs
	 */
	const getSourceLogs = (): object[] => sourceLogs.value as object[]

	/**
	 * Set the active source configuration key.
	 * @param item - The source configuration key to set
	 */
	const setSourceConfigurationKey = (item: string) => {
		sourceConfigurationKey.value = item
		console.info('Source configuration key set to ' + item)
	}

	/**
	 * Get the active source configuration key.
	 *
	 * @description
	 * Returns the currently active source configuration key. Note that the return value is non-reactive.
	 *
	 * For reactive usage, either:
	 * 1. Reference the `sourceConfigurationKey` state directly:
	 * ```js
	 * const sourceConfigurationKey = useSourceStore().sourceConfigurationKey // reactive state
	 * ```
	 * 2. Or wrap in a `computed` property:
	 * ```js
	 * const sourceConfigurationKey = computed(() => useSourceStore().getSourceConfigurationKey())
	 * ```
	 *
	 * @return {string} The active source configuration key
	 */
	const getSourceConfigurationKey = (): string => sourceConfigurationKey.value as string

	/**
	 * Set the view mode.
	 * @param mode - The view mode to set ('cards' or 'table')
	 */
	const setViewMode = (mode: string) => {
		viewMode.value = mode
		console.info('Source view mode set to ' + mode)
	}

	/**
	 * Get the view mode.
	 *
	 * @description
	 * Returns the currently active view mode. Note that the return value is non-reactive.
	 *
	 * For reactive usage, either:
	 * 1. Reference the `viewMode` state directly:
	 * ```js
	 * const viewMode = useSourceStore().viewMode // reactive state
	 * ```
	 * 2. Or wrap in a `computed` property:
	 * ```js
	 * const viewMode = computed(() => useSourceStore().getViewMode())
	 * ```
	 *
	 * @return {string} The active view mode
	 */
	const getViewMode = (): string => viewMode.value as string

	// ################################
	// ||          Actions           ||
	// ################################

	/**
	 * Refresh the source list
	 * @param search - The search string to filter the list
	 * @return {Promise<{ response: Response, data: TSource[], entities: Source[] }>} The response, data, and entities
	 */
	const refreshSourceList = async (search: string = null): Promise<{ response: Response, data: TSource[], entities: Source[] }> => {
		const queryParams = new URLSearchParams()

		if (search && search !== '') {
			queryParams.append('_search', search)
		}

		// Build the endpoint with query params if they exist
		let endpoint = apiEndpoint
		if (queryParams.toString()) {
			endpoint += '?' + queryParams.toString()
		}

		const response = await fetch(endpoint, {
			method: 'GET',
		})

		const data = (await response.json()).results as TSource[]
		const entities = data.map(sourceItem => new Source(sourceItem))

		setSourceList(data)

		return { response, data, entities }
	}

	/**
	 * Fetch a single source
	 * @param id - The ID of the source to fetch
	 * @return {Promise<{ response: Response, data: TSource, entity: Source }>} The response, data, and entity
	 */
	const fetchSource = async (id: string): Promise<{ response: Response, data: TSource, entity: Source }> => {
		if (!id) {
			throw new MissingParameterError('id')
		}

		const endpoint = `${apiEndpoint}/${id}`

		const response = await fetch(endpoint, {
			method: 'GET',
		})

		const data = await response.json() as TSource
		const entity = new Source(data)

		setSourceItem(data)

		return { response, data, entity }
	}

	/**
	 * Delete a source
	 * @param id - The ID of the source to delete
	 * @return {Promise<{ response: Response }>} The response
	 */
	const deleteSource = async (id: string): Promise<{ response: Response }> => {
		if (!id) {
			throw new MissingParameterError('id')
		}

		console.info('Deleting source...')

		const endpoint = `${apiEndpoint}/${id}`

		const response = await fetch(endpoint, {
			method: 'DELETE',
		})

		response.ok && setSourceItem(null)
		refreshSourceList()

		return { response }
	}

	/**
	 * Save a source
	 * @param sourceItem - The source item to save
	 * @return {Promise<{ response: Response, data: TSource, entity: Source }>} The response, data, and entity
	 */
	const saveSource = async (sourceItem: Source): Promise<{ response: Response, data: TSource, entity: Source }> => {
		if (!sourceItem) {
			throw new MissingParameterError('sourceItem')
		}
		if (!(sourceItem instanceof Source)) {
			throw new Error('sourceItem is not an instance of Source')
		}

		// DISABLED UNTIL TIME CAN BE SPENT TO DO VALIDATION PROPERLY
		// verify data with Zod
		// const validationResult = sourceItem.validate()
		// if (!validationResult.success) {
		//  console.error(validationResult.error)
		//  console.info(sourceItem)
		//  throw new ValidationError(validationResult.error)
		// }

		console.info('Saving source...')

		const isNewSource = !sourceItem.id
		const endpoint = isNewSource
			? apiEndpoint
			: `${apiEndpoint}/${sourceItem.id}`
		const method = isNewSource ? 'POST' : 'PUT'

		const response = await fetch(
			endpoint,
			{
				method,
				headers: {
					'Content-Type': 'application/json',
				},
				body: JSON.stringify(sourceItem),
			},
		)

		const data = await response.json() as TSource
		const entity = new Source(data)

		setSourceItem(data)
		refreshSourceList()

		return { response, data, entity }
	}

	/**
	 * Refresh the source logs
	 * @param filters - Optional filters to apply to the logs
	 * @return {Promise<{ response: Response, data: object[] }>} The response and data
	 */
	const refreshSourceLogs = async (filters: object = {}) => {
		const logStore = useLogStore()
		logStore.setLogsLoading(true)

		try {
			// Build query parameters
			const queryParams = new URLSearchParams()
			// Only add source_id if not already present in filters
			if (!('source_id' in filters) && sourceItem.value?.id) {
				queryParams.append('source_id', sourceItem.value.id.toString())
			}

            // Ensure _sort[created]=desc if not set
            const sortKey = '_sort[created]'
            if (!(sortKey in filters)) {
                queryParams.append(sortKey, 'desc')
            }   

			// Add other filters
			Object.entries(filters).forEach(([key, value]) => {
				if (value !== null && value !== undefined && value !== '') {
					queryParams.append(key, value.toString())
				}
			})

			// Build the endpoint
			const endpoint = `/index.php/apps/openconnector/api/sources/logs${queryParams.toString() ? '?' + queryParams.toString() : ''}`
			const response = await fetch(endpoint, {
				method: 'GET',
			})

			const data = await response.json()
			setSourceLogs(data)
			return { response, data }
		} catch (error) {
			console.error('Error refreshing source logs:', error)
			throw error
		} finally {
			logStore.setLogsLoading(false)
		}
	}

	/**
	 * Test a source
	 * @param testSourceItem - The test source item to test
	 * @return {Promise<{ response: Response, data: object }>} The response and data
	 */
	const testSource = async (testSourceItem: object): Promise<{ response: Response, data: object }> => {
		if (!sourceItem.value) {
			throw new Error('No source item to test')
		}
		if (!testSourceItem) {
			throw new Error('No testobject to test')
		}

		console.info('Testing source...')

		const endpoint = `/index.php/apps/openconnector/api/sources/test/${sourceItem.value.id}`

		const response = await fetch(endpoint, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
			},
			body: JSON.stringify(testSourceItem),
		})

		const data = await response.json()

		setSourceTest(data)
		console.info('Source tested')
		// Refresh the source list
		refreshSourceLogs()

		return { response, data }
	}

	/**
	 * Export a source
	 * @param id - The ID of the source to export
	 */
	const exportSource = async (id: string) => {
		if (!id) {
			throw new Error('No source item to export')
		}
		importExportStore.exportFile(
			id,
			'source',
		)
			.then(({ download }) => {
				download()
			})
			.catch((err) => {
				console.error('Error exporting source:', err)
				throw err
			})
	}

	return {
		// state
		sourceItem,
		sourceList,
		sourceTest,
		sourceLog,
		sourceLogs,
		sourceConfigurationKey,
		viewMode,

		// setters and getters
		setSourceItem,
		getSourceItem,
		setSourceList,
		getSourceList,
		setSourceTest,
		getSourceTest,
		setSourceLog,
		getSourceLog,
		setSourceLogs,
		getSourceLogs,
		setSourceConfigurationKey,
		getSourceConfigurationKey,
		setViewMode,
		getViewMode,

		// actions
		refreshSourceList,
		fetchSource,
		deleteSource,
		saveSource,
		refreshSourceLogs,
		testSource,
		exportSource,
	}
})
