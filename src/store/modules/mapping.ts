import { ref } from 'vue'
import { defineStore } from 'pinia'
import { importExportStore } from '../store.js'
import { Mapping, TMapping } from '../../entities/index.js'
import { MissingParameterError } from '../../services/errors/index.js'

const apiEndpoint = '/index.php/apps/openconnector/api/mappings'

export const useMappingStore = defineStore('mapping', () => {
	// state
	const mappingItem = ref<Mapping>(null)
	const mappingList = ref<Mapping[]>([])
	const mappingMappingKey = ref<string>(null)
	const mappingCastKey = ref<string>(null)
	const mappingUnsetKey = ref<string>(null)
	const viewMode = ref<string>('cards')

	// ################################
	// ||    Setters and Getters     ||
	// ################################

	// Mapping Item
	/**
	 * Set the active mapping item.
	 * @param item - The mapping item to set
	 */
	const setMappingItem = (item: Mapping | TMapping) => {
		mappingItem.value = item && new Mapping(item)
		console.info('Active mapping item set to ' + (item ? item.id : 'null'))
	}

	/**
	 * Get the active mapping item.
	 *
	 * @description
	 * Returns the currently active mapping item. Note that the return value is non-reactive.
	 *
	 * For reactive usage, either:
	 * 1. Reference the `mappingItem` state directly:
	 * ```js
	 * const mappingItem = useMappingStore().mappingItem // reactive state
	 * ```
	 * 2. Or wrap in a `computed` property:
	 * ```js
	 * const mappingItem = computed(() => useMappingStore().getMappingItem())
	 * ```
	 *
	 * @return {Mapping | null} The active mapping item
	 */
	const getMappingItem = (): Mapping | null => mappingItem.value as Mapping | null

	/**
	 * Set the active mapping list.
	 * @param list - The mapping list to set
	 */
	const setMappingList = (list: Mapping[] | TMapping[]) => {
		mappingList.value = list.map((item) => new Mapping(item))
		console.info('Mapping list set to ' + list.length + ' items')
	}

	/**
	 * Get the active mapping list.
	 *
	 * @description
	 * Returns the currently active mapping list. Note that the return value is non-reactive.
	 *
	 * For reactive usage, either:
	 * 1. Reference the `mappingList` state directly:
	 * ```js
	 * const mappingList = useMappingStore().mappingList // reactive state
	 * ```
	 * 2. Or wrap in a `computed` property:
	 * ```js
	 * const mappingList = computed(() => useMappingStore().getMappingList())
	 * ```
	 *
	 * @return {Mapping[]} The active mapping list
	 */
	const getMappingList = (): Mapping[] => mappingList.value as Mapping[]

	/**
	 * Set the active mapping mapping key.
	 * @param key - The mapping mapping key to set
	 */
	const setMappingMappingKey = (key: string) => {
		mappingMappingKey.value = key
		console.info('Active mapping mapping key set to ' + key)
	}

	/**
	 * Get the active mapping mapping key.
	 *
	 * @description
	 * Returns the currently active mapping mapping key. Note that the return value is non-reactive.
	 *
	 * For reactive usage, either:
	 * 1. Reference the `mappingMappingKey` state directly:
	 * ```js
	 * const mappingMappingKey = useMappingStore().mappingMappingKey // reactive state
	 * ```
	 * 2. Or wrap in a `computed` property:
	 * ```js
	 * const mappingMappingKey = computed(() => useMappingStore().getMappingMappingKey())
	 * ```
	 *
	 * @return {string | null} The active mapping mapping key
	 */
	const getMappingMappingKey = (): string | null => mappingMappingKey.value as string | null

	/**
	 * Set the active mapping cast key.
	 * @param key - The mapping cast key to set
	 */
	const setMappingCastKey = (key: string) => {
		mappingCastKey.value = key
		console.info('Active mapping cast key set to ' + key)
	}

	/**
	 * Get the active mapping cast key.
	 *
	 * @description
	 * Returns the currently active mapping cast key. Note that the return value is non-reactive.
	 *
	 * For reactive usage, either:
	 * 1. Reference the `mappingCastKey` state directly:
	 * ```js
	 * const mappingCastKey = useMappingStore().mappingCastKey // reactive state
	 * ```
	 * 2. Or wrap in a `computed` property:
	 * ```js
	 * const mappingCastKey = computed(() => useMappingStore().getMappingCastKey())
	 * ```
	 *
	 * @return {string | null} The active mapping cast key
	 */
	const getMappingCastKey = (): string | null => mappingCastKey.value as string | null

	/**
	 * Set the active mapping unset key.
	 * @param key - The mapping unset key to set
	 */
	const setMappingUnsetKey = (key: string) => {
		mappingUnsetKey.value = key
		console.info('Active mapping unset key set to ' + key)
	}

	/**
	 * Get the active mapping unset key.
	 *
	 * @description
	 * Returns the currently active mapping unset key. Note that the return value is non-reactive.
	 *
	 * For reactive usage, either:
	 * 1. Reference the `mappingUnsetKey` state directly:
	 * ```js
	 * const mappingUnsetKey = useMappingStore().mappingUnsetKey // reactive state
	 * ```
	 * 2. Or wrap in a `computed` property:
	 * ```js
	 * const mappingUnsetKey = computed(() => useMappingStore().getMappingUnsetKey())
	 * ```
	 *
	 * @return {string | null} The active mapping unset key
	 */
	const getMappingUnsetKey = (): string | null => mappingUnsetKey.value as string | null

	/**
	 * Set the view mode.
	 * @param mode - The view mode to set
	 */
	const setViewMode = (mode: string) => {
		viewMode.value = mode
		console.info('Mapping view mode set to ' + mode)
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
	 * const viewMode = useMappingStore().viewMode // reactive state
	 * ```
	 * 2. Or wrap in a `computed` property:
	 * ```js
	 * const viewMode = computed(() => useMappingStore().getViewMode())
	 * ```
	 *
	 * @return {string} The active view mode
	 */
	const getViewMode = (): string => viewMode.value as string

	// ################################
	// ||          Actions           ||
	// ################################

	// Mapping
	/**
	 * Refresh the mapping list
	 * @param search - The search string to filter the list
	 * @return {Promise<{ response: Response, data: TMapping[], entities: Mapping[] }>} The response, data, and entities
	 */
	const refreshMappingList = async (search: string = null): Promise<{ response: Response, data: TMapping[], entities: Mapping[] }> => {
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

		const data = (await response.json()).results as TMapping[]
		const entities = data.map(mappingItem => new Mapping(mappingItem))

		setMappingList(data)

		return { response, data, entities }
	}

	/**
	 * Fetch a single mapping
	 * @param id - The ID of the mapping to fetch
	 * @return {Promise<{ response: Response, data: TMapping, entity: Mapping }>} The response, data, and entity
	 */
	const fetchMapping = async (id: string): Promise<{ response: Response, data: TMapping, entity: Mapping }> => {
		if (!id) {
			throw new MissingParameterError('id')
		}

		const endpoint = `${apiEndpoint}/${id}`

		const response = await fetch(endpoint, {
			method: 'GET',
		})

		const data = await response.json() as TMapping
		const entity = new Mapping(data)

		setMappingItem(data)

		return { response, data, entity }
	}

	/**
	 * Delete a mapping
	 * @param id - The ID of the mapping to delete
	 * @return {Promise<{ response: Response }>} The response
	 */
	const deleteMapping = async (id: string): Promise<{ response: Response }> => {
		if (!id) {
			throw new MissingParameterError('id')
		}

		console.info('Deleting mapping...')

		const endpoint = `${apiEndpoint}/${id}`

		const response = await fetch(endpoint, {
			method: 'DELETE',
		})

		response.ok && setMappingItem(null)
		refreshMappingList()

		return { response }
	}

	/**
	 * Save a mapping
	 * @param mappingItem - The mapping item to save
	 * @return {Promise<{ response: Response, data: TMapping, entity: Mapping }>} The response, data, and entity
	 */
	const saveMapping = async (mappingItem: Mapping): Promise<{ response: Response, data: TMapping, entity: Mapping }> => {
		if (!mappingItem) {
			throw new MissingParameterError('mappingItem')
		}
		if (!(mappingItem instanceof Mapping)) {
			throw new Error('mappingItem is not an instance of Mapping')
		}

		// DISABLED UNTIL TIME CAN BE SPENT TO DO VALIDATION PROPERLY
		// verify data with Zod
		// const validationResult = mappingItem.validate()
		// if (!validationResult.success) {
		//  console.error(validationResult.error)
		//  console.info(mappingItem)
		//  throw new ValidationError(validationResult.error)
		// }

		// delete "version"
		const clonedMapping = mappingItem.cloneRaw()
		delete clonedMapping.version
		mappingItem = new Mapping(clonedMapping)

		console.info('Saving job...')

		const isNewMapping = !mappingItem.id
		const endpoint = isNewMapping
			? apiEndpoint
			: `${apiEndpoint}/${mappingItem.id}`
		const method = isNewMapping ? 'POST' : 'PUT'

		const response = await fetch(
			endpoint,
			{
				method,
				headers: {
					'Content-Type': 'application/json',
				},
				body: JSON.stringify(mappingItem),
			},
		)

		const data = await response.json() as TMapping
		const entity = new Mapping(data)

		setMappingItem(data)
		refreshMappingList()

		return { response, data, entity }
	}

	// test mapping
	/**
	 * Test a mapping with the provided test object.
	 *
	 * @param {object} mappingTestObject - The object containing the mapping test data.
	 * @param {object} mappingTestObject.inputObject - The input object to test the mapping with.
	 * @param {object} mappingTestObject.mapping - The mapping to be tested.
	 * @param {object} mappingTestObject.schema - (optional) The schema to be used for the test.
	 * @throws Will throw an error if mappingTestObject, inputObject, or mapping is not provided.
	 */
	const testMapping = async (mappingTestObject: { inputObject: object, mapping: object, schema?: object }): Promise<{ response: Response, data: object }> => {
		if (!mappingTestObject) {
			throw new MissingParameterError('mappingTestObject', 'mappingTestObject is required')
		}
		if (!mappingTestObject?.inputObject) {
			throw new MissingParameterError('inputObject', 'mappingTestObject.inputObject is required')
		}
		if (!mappingTestObject?.mapping) {
			throw new MissingParameterError('mapping', 'mappingTestObject.mapping is required')
		}

		// remove unrelated properties
		mappingTestObject = {
			inputObject: mappingTestObject.inputObject,
			mapping: mappingTestObject.mapping,
			schema: mappingTestObject?.schema || null,
			validation: !!mappingTestObject?.schema,
		} as typeof mappingTestObject & { validation: boolean }

		// assert that the data is an object
		if (typeof mappingTestObject.mapping !== 'object') {
			mappingTestObject.mapping = JSON.parse(mappingTestObject.mapping)
		}
		if (typeof mappingTestObject.inputObject !== 'object') {
			mappingTestObject.inputObject = JSON.parse(mappingTestObject.inputObject)
		}
		if (!!mappingTestObject.schema && typeof mappingTestObject.schema !== 'object') {
			mappingTestObject.schema = JSON.parse(mappingTestObject.schema)
		}

		console.info('Testing mapping...')

		const response = await fetch(
			'/index.php/apps/openconnector/api/mappings/test',
			{
				method: 'POST',
				headers: {
					'Content-Type': 'application/json',
				},
				body: JSON.stringify(mappingTestObject),
			},
		)

		const data = await response.json()

		return { response, data }
	}

	/**
	 * Get objects on a mapping from the endpoint.
	 *
	 * This method fetches objects related to a mapping from the specified API endpoint.
	 *
	 * @throws Will throw an error if the fetch operation fails.
	 * @return { Promise<{ response: Response, data: object }> } The response and data from the API.
	 */
	const getMappingObjects = async (): Promise<{ response: Response, data: object }> => {
		console.info('Fetching mapping objects...')

		// Fetch objects related to a mapping from the API endpoint
		const response = await fetch(
			'/index.php/apps/openconnector/api/mappings/objects',
			{
				method: 'GET',
				headers: {
					'Content-Type': 'application/json',
				},
			},
		)

		// Parse the response data as JSON
		const data = await response.json()

		// Return the response and parsed data
		return { response, data }
	}

	/**
	 * Save a mapping object to the endpoint.
	 *
	 * This method sends a mapping object to the specified API endpoint to be saved.
	 *
	 * @param { object } mappingObject - The mapping object to be saved.
	 * @return { Promise<{ response: Response, data: object }> } The response and data from the API.
	 * @throws Will throw an error if the save operation fails.
	 */
	const saveMappingObject = async (mappingObject: object): Promise<{ response: Response, data: object }> => {
		console.info('Saving mapping object...')

		// Send the mapping object to the API endpoint to be saved
		const response = await fetch(
			'/index.php/apps/openconnector/api/mappings/objects',
			{
				method: 'POST',
				headers: {
					'Content-Type': 'application/json',
				},
				body: JSON.stringify(mappingObject),
			},
		)

		// Parse the response data as JSON
		const data = await response.json()

		// Return the response and parsed data
		return { response, data }
	}

	// Export a mapping
	const exportMapping = async (id: string) => {
		if (!id) {
			throw new Error('No mapping item to export')
		}
		importExportStore.exportFile(
			id,
			'mapping',
		)
			.then(({ download }) => {
				download()
			})
			.catch((err) => {
				console.error('Error exporting mapping:', err)
				throw err
			})
	}

	return {
		// state
		mappingItem,
		mappingList,
		mappingMappingKey,
		mappingCastKey,
		mappingUnsetKey,
		viewMode,

		// setters and getters
		setMappingItem,
		getMappingItem,
		setMappingList,
		getMappingList,
		setMappingMappingKey,
		getMappingMappingKey,
		setMappingCastKey,
		getMappingCastKey,
		setMappingUnsetKey,
		getMappingUnsetKey,
		setViewMode,
		getViewMode,

		// actions
		refreshMappingList,
		fetchMapping,
		deleteMapping,
		saveMapping,
		testMapping,
		getMappingObjects,
		saveMappingObject,
		exportMapping,
	}
})
