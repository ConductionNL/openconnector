import { ref } from 'vue'
import { defineStore } from 'pinia'
import { Synchronization, TSynchronization } from '../../entities/index.js'
import { importExportStore } from '../store.js'
import { MissingParameterError } from '../../services/errors/index.js'

const apiEndpoint = '/index.php/apps/openconnector/api/synchronizations'

export const useSynchronizationStore = defineStore('synchronization', () => {
	// state
	const synchronizationItem = ref<Synchronization>(null)
	const synchronizationList = ref<Synchronization[]>([])
	const synchronizationContracts = ref<Synchronization[]>([])
	const synchronizationTest = ref<object>(null)
	const synchronizationRun = ref<object>(null)
	const synchronizationLogs = ref<object[]>([])
	const synchronizationSourceConfigKey = ref<string | null>(null)
	const synchronizationTargetConfigKey = ref<string | null>(null)

	// ################################
	// ||    Setters and Getters     ||
	// ################################

	/**
	 * Set the active synchronization item.
	 * @param item - The synchronization item to set
	 */
	const setSynchronizationItem = (item: Synchronization | TSynchronization) => {
		synchronizationItem.value = item && new Synchronization(item)
		console.info('Active synchronization item set to ' + (item ? item.id : 'null'))
	}

	/**
	 * Get the active synchronization item.
	 *
	 * @description
	 * Returns the currently active synchronization item. Note that the return value is non-reactive.
	 *
	 * For reactive usage, either:
	 * 1. Reference the `synchronizationItem` state directly:
	 * ```js
	 * const synchronizationItem = useSynchronizationStore().synchronizationItem // reactive state
	 * ```
	 * 2. Or wrap in a `computed` property:
	 * ```js
	 * const synchronizationItem = computed(() => useSynchronizationStore().getSynchronizationItem())
	 * ```
	 *
	 * @return {Synchronization | null} The active synchronization item
	 */
	const getSynchronizationItem = (): Synchronization | null => synchronizationItem.value as Synchronization | null

	/**
	 * Set the active synchronization list.
	 * @param list - The synchronization list to set
	 */
	const setSynchronizationList = (list: Synchronization[] | TSynchronization[]) => {
		synchronizationList.value = list.map((item) => new Synchronization(item))
		console.info('Synchronization list set to ' + list.length + ' items')
	}

	/**
	 * Get the active synchronization list.
	 *
	 * @description
	 * Returns the currently active synchronization list. Note that the return value is non-reactive.
	 *
	 * For reactive usage, either:
	 * 1. Reference the `synchronizationList` state directly:
	 * ```js
	 * const synchronizationList = useSynchronizationStore().synchronizationList // reactive state
	 * ```
	 * 2. Or wrap in a `computed` property:
	 * ```js
	 * const synchronizationList = computed(() => useSynchronizationStore().getSynchronizationList())
	 * ```
	 *
	 * @return {Synchronization[]} The active synchronization list
	 */
	const getSynchronizationList = (): Synchronization[] => synchronizationList.value as Synchronization[]

	/**
	 * Set the active synchronization contracts.
	 * @param item - The synchronization contracts to set
	 */
	const setSynchronizationContracts = (item: Synchronization[] | TSynchronization[]) => {
		synchronizationContracts.value = item.map((item) => new Synchronization(item))
		console.info('Synchronization contracts set to ' + item.length + ' items')
	}

	/**
	 * Get the active synchronization contracts.
	 *
	 * @description
	 * Returns the currently active synchronization contracts. Note that the return value is non-reactive.
	 *
	 * For reactive usage, either:
	 * 1. Reference the `synchronizationContracts` state directly:
	 * ```js
	 * const synchronizationContracts = useSynchronizationStore().synchronizationContracts // reactive state
	 * ```
	 * 2. Or wrap in a `computed` property:
	 * ```js
	 * const synchronizationContracts = computed(() => useSynchronizationStore().getSynchronizationContracts())
	 * ```
	 *
	 * @return {Synchronization[]} The active synchronization contracts
	 */
	const getSynchronizationContracts = (): Synchronization[] => synchronizationContracts.value as Synchronization[]

	/**
	 * Set the active synchronization test.
	 * @param item - The synchronization test to set
	 */
	const setSynchronizationTest = (item: object) => {
		synchronizationTest.value = item
		console.info('Synchronization test set to ' + item)
	}

	/**
	 * Get the active synchronization test.
	 *
	 * @description
	 * Returns the currently active synchronization test. Note that the return value is non-reactive.
	 *
	 * For reactive usage, either:
	 * 1. Reference the `synchronizationTest` state directly:
	 * ```js
	 * const synchronizationTest = useSynchronizationStore().synchronizationTest // reactive state
	 * ```
	 * 2. Or wrap in a `computed` property:
	 * ```js
	 * const synchronizationTest = computed(() => useSynchronizationStore().getSynchronizationTest())
	 * ```
	 *
	 * @return {object} The active synchronization test
	 */
	const getSynchronizationTest = (): object => synchronizationTest.value as object

	/**
	 * Set the active synchronization run.
	 * @param item - The synchronization run to set
	 */
	const setSynchronizationRun = (item: object) => {
		synchronizationRun.value = item
		console.info('Synchronization run set to ' + item)
	}

	/**
	 * Get the active synchronization run.
	 *
	 * @description
	 * Returns the currently active synchronization run. Note that the return value is non-reactive.
	 *
	 * For reactive usage, either:
	 * 1. Reference the `synchronizationRun` state directly:
	 * ```js
	 * const synchronizationRun = useSynchronizationStore().synchronizationRun // reactive state
	 * ```
	 * 2. Or wrap in a `computed` property:
	 * ```js
	 * const synchronizationRun = computed(() => useSynchronizationStore().getSynchronizationRun())
	 * ```
	 *
	 * @return {object} The active synchronization run
	 */
	const getSynchronizationRun = (): object => synchronizationRun.value as object

	/**
	 * Set the active synchronization logs.
	 * @param item - The synchronization logs to set
	 */
	const setSynchronizationLogs = (item: object[]) => {
		synchronizationLogs.value = item
		console.info('Synchronization logs set to ' + item.length + ' items')
	}

	/**
	 * Get the active synchronization logs.
	 *
	 * @description
	 * Returns the currently active synchronization logs. Note that the return value is non-reactive.
	 *
	 * For reactive usage, either:
	 * 1. Reference the `synchronizationLogs` state directly:
	 * ```js
	 * const synchronizationLogs = useSynchronizationStore().synchronizationLogs // reactive state
	 * ```
	 * 2. Or wrap in a `computed` property:
	 * ```js
	 * const synchronizationLogs = computed(() => useSynchronizationStore().getSynchronizationLogs())
	 * ```
	 *
	 * @return {object[]} The active synchronization logs
	 */
	const getSynchronizationLogs = (): object[] => synchronizationLogs.value as object[]

	/**
	 * Set the active synchronization source config key.
	 * @param key - The synchronization source config key to set
	 */
	const setSynchronizationSourceConfigKey = (key: string) => {
		synchronizationSourceConfigKey.value = key
		console.info('Synchronization source config key set to ' + key)
	}

	/**
	 * Get the active synchronization source config key.
	 *
	 * @description
	 * Returns the currently active synchronization source config key. Note that the return value is non-reactive.
	 *
	 * For reactive usage, either:
	 * 1. Reference the `synchronizationSourceConfigKey` state directly:
	 * ```js
	 * const synchronizationSourceConfigKey = useSynchronizationStore().synchronizationSourceConfigKey // reactive state
	 * ```
	 * 2. Or wrap in a `computed` property:
	 * ```js
	 * const synchronizationSourceConfigKey = computed(() => useSynchronizationStore().getSynchronizationSourceConfigKey())
	 * ```
	 *
	 * @return {string} The active synchronization source config key
	 */
	const getSynchronizationSourceConfigKey = (): string => synchronizationSourceConfigKey.value as string

	/**
	 * Set the active synchronization target config key.
	 * @param key - The synchronization target config key to set
	 */
	const setSynchronizationTargetConfigKey = (key: string) => {
		synchronizationTargetConfigKey.value = key
		console.info('Synchronization target config key set to ' + key)
	}

	/**
	 * Get the active synchronization target config key.
	 *
	 * @description
	 * Returns the currently active synchronization target config key. Note that the return value is non-reactive.
	 *
	 * For reactive usage, either:
	 * 1. Reference the `synchronizationTargetConfigKey` state directly:
	 * ```js
	 * const synchronizationTargetConfigKey = useSynchronizationStore().synchronizationTargetConfigKey // reactive state
	 * ```
	 * 2. Or wrap in a `computed` property:
	 * ```js
	 * const synchronizationTargetConfigKey = computed(() => useSynchronizationStore().getSynchronizationTargetConfigKey())
	 * ```
	 *
	 * @return {Synchronization | null} The active synchronization item
	 */
	const getSynchronizationTargetConfigKey = (): string => synchronizationTargetConfigKey.value as string

	// ################################
	// ||          Actions           ||
	// ################################

	/**
	 * Refresh the synchronization list
	 * @param search - The search string to filter the list
	 * @return {Promise<{ response: Response, data: TSynchronization[], entities: Synchronization[] }>} The response, data, and entities
	 */
	const refreshSynchronizationList = async (search: string = null): Promise<{ response: Response, data: TSynchronization[], entities: Synchronization[] }> => {
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

		const data = (await response.json()).results as TSynchronization[]
		const entities = data.map(synchronizationItem => new Synchronization(synchronizationItem))

		setSynchronizationList(data)

		return { response, data, entities }
	}

	/**
	 * Fetch a single synchronization
	 * @param id - The ID of the synchronization to fetch
	 * @return {Promise<{ response: Response, data: TSynchronization, entity: Synchronization }>} The response, data, and entity
	 */
	const fetchSynchronization = async (id: string): Promise<{ response: Response, data: TSynchronization, entity: Synchronization }> => {
		if (!id) {
			throw new MissingParameterError('id')
		}

		const endpoint = `${apiEndpoint}/${id}`

		const response = await fetch(endpoint, {
			method: 'GET',
		})

		const data = await response.json() as TSynchronization
		const entity = new Synchronization(data)

		setSynchronizationItem(data)

		return { response, data, entity }
	}

	/**
	 * Delete a synchronization
	 * @param id - The ID of the synchronization to delete
	 * @return {Promise<{ response: Response }>} The response
	 */
	const deleteSynchronization = async (id: string): Promise<{ response: Response }> => {
		if (!id) {
			throw new MissingParameterError('id')
		}

		console.info('Deleting synchronization...')

		const endpoint = `${apiEndpoint}/${id}`

		const response = await fetch(endpoint, {
			method: 'DELETE',
		})

		response.ok && setSynchronizationItem(null)
		refreshSynchronizationList()

		return { response }
	}

	/**
	 * Save a synchronization
	 * @param synchronizationItem - The synchronization item to save
	 * @return {Promise<{ response: Response, data: TSynchronization, entity: Synchronization }>} The response, data, and entity
	 */
	const saveSynchronization = async (synchronizationItem: Synchronization): Promise<{ response: Response, data: TSynchronization, entity: Synchronization }> => {
		if (!synchronizationItem) {
			throw new MissingParameterError('synchronizationItem')
		}
		if (!(synchronizationItem instanceof Synchronization)) {
			throw new Error('synchronizationItem is not an instance of Synchronization')
		}

		// DISABLED UNTIL TIME CAN BE SPENT TO DO VALIDATION PROPERLY
		// verify data with Zod
		// const validationResult = synchronizationItem.validate()
		// if (!validationResult.success) {
		//  console.error(validationResult.error)
		//  console.info(synchronizationItem)
		//  throw new ValidationError(validationResult.error)
		// }

		// delete "updated" and "version"
		const clonedSynchronization = synchronizationItem.cloneRaw()
		delete clonedSynchronization.updated
		delete clonedSynchronization.version
		synchronizationItem = new Synchronization(clonedSynchronization)

		console.info('Saving synchronization...')

		const isNewSynchronization = !synchronizationItem.id
		const endpoint = isNewSynchronization
			? apiEndpoint
			: `${apiEndpoint}/${synchronizationItem.id}`
		const method = isNewSynchronization ? 'POST' : 'PUT'

		const response = await fetch(
			endpoint,
			{
				method,
				headers: {
					'Content-Type': 'application/json',
				},
				body: JSON.stringify(synchronizationItem),
			},
		)

		const data = await response.json() as TSynchronization
		const entity = new Synchronization(data)

		setSynchronizationItem(data)
		refreshSynchronizationList()

		return { response, data, entity }
	}

	// contracts
	const refreshSynchronizationContracts = async (id: string, search?: string) => {
		let endpoint = `/index.php/apps/openconnector/api/synchronizations-contracts/${id}`

		if (search && search !== '') {
			endpoint = endpoint + '?_search=' + search
		}

		const response = await fetch(endpoint, {
			method: 'GET',
		})

		const data = await response.json() as TSynchronization[]

		setSynchronizationContracts(data)

		return { response, data }
	}

	// logs
	const refreshSynchronizationLogs = async (id: number, search?: string) => {
		let endpoint = `/index.php/apps/openconnector/api/synchronizations-logs/${id}`

		if (search && search !== '') {
			endpoint = endpoint + '?_search=' + search
		}

		const response = await fetch(endpoint, {
			method: 'GET',
		})

		const data = await response.json() as TSynchronization[]

		setSynchronizationLogs(data)

		return { response, data }
	}

	// synchronization actions
	const testSynchronization = async () => {
		if (!synchronizationItem.value) {
			throw new Error('No synchronization item to test')
		}

		console.info('Testing synchronization...')

		const endpoint = `/index.php/apps/openconnector/api/synchronizations-test/${synchronizationItem.value.id}`

		const response = await fetch(endpoint, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
			},
		})

		const data = await response.json()
		setSynchronizationTest(data)

		console.info('Synchronization tested')
		refreshSynchronizationLogs(synchronizationItem.value.id)

		return { response, data }
	}

	const runSynchronization = async (id: string, test: boolean = false, force: boolean = false) => {
		if (!id) {
			throw new Error('No synchronization item to run')
		}

		console.info('Testing synchronization...')

		const endpoint = `/index.php/apps/openconnector/api/synchronizations-run/${id}?test=${test}&force=${force}`

		const response = await fetch(endpoint, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
			},
		})

		const data = await response.json()
		setSynchronizationRun(data)

		console.info('Synchronization run')
		refreshSynchronizationLogs(synchronizationItem.value.id)

		return { response, data }
	}

	// export
	const exportSynchronization = async (id: string) => {
		if (!id) {
			throw new Error('No synchronization item to export')
		}
		importExportStore.exportFile(
			id,
			'synchronization',
		)
			.then(({ download }) => {
				download()
			})
			.catch((err) => {
				console.error('Error exporting synchronization:', err)
				throw err
			})
	}

	return {
		// state
		synchronizationItem,
		synchronizationList,
		synchronizationContracts,
		synchronizationTest,
		synchronizationRun,
		synchronizationLogs,
		synchronizationSourceConfigKey,
		synchronizationTargetConfigKey,

		// setters and getters
		setSynchronizationItem,
		getSynchronizationItem,
		setSynchronizationList,
		getSynchronizationList,
		setSynchronizationContracts,
		getSynchronizationContracts,
		setSynchronizationTest,
		getSynchronizationTest,
		setSynchronizationRun,
		getSynchronizationRun,
		setSynchronizationLogs,
		getSynchronizationLogs,
		setSynchronizationSourceConfigKey,
		getSynchronizationSourceConfigKey,
		setSynchronizationTargetConfigKey,
		getSynchronizationTargetConfigKey,

		// actions
		refreshSynchronizationList,
		fetchSynchronization,
		deleteSynchronization,
		saveSynchronization,
		refreshSynchronizationContracts,
		refreshSynchronizationLogs,
		testSynchronization,
		runSynchronization,
		exportSynchronization,
	}
})
