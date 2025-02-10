import { ref } from 'vue'
import { defineStore } from 'pinia'
import { Endpoint, TEndpoint } from '../../entities/index.js'
import { MissingParameterError } from '../../services/errors/index.js'
import { importExportStore } from '../../store/store.js'

const apiEndpoint = '/index.php/apps/openconnector/api/endpoints'

export const useEndpointStore = defineStore('endpoint', () => {
	// state
	const endpointItem = ref<Endpoint | null>(null)
	const endpointList = ref<Endpoint[]>([])

	// ################################
	// ||    Setters and Getters     ||
	// ################################

	/**
	 * Set the active endpoint item.
	 * @param item - The endpoint item to set
	 */
	const setEndpointItem = (item: Endpoint | TEndpoint) => {
		endpointItem.value = item && new Endpoint(item)
		console.info('Active endpoint item set to ' + (item ? item.id : 'null'))
	}

	/**
	 * Get the active endpoint item.
	 *
	 * @description
	 * Returns the currently active endpoint item. Note that the return value is non-reactive.
	 *
	 * For reactive usage, either:
	 * 1. Reference the `endpointItem` state directly:
	 * ```js
	 * const endpointItem = useEndpointStore().endpointItem // reactive state
	 * ```
	 * 2. Or wrap in a `computed` property:
	 * ```js
	 * const endpointItem = computed(() => useEndpointStore().getEndpointItem())
	 * ```
	 *
	 * @return {Endpoint | null} The active endpoint item
	 */
	const getEndpointItem = (): Endpoint | null => endpointItem.value as Endpoint | null

	/**
	 * Set the endpoint list
	 * @param list - The list of endpoints to set
	 */
	const setEndpointList = (list: (Endpoint | TEndpoint)[]) => {
		endpointList.value = list.map(
			(item) => new Endpoint(item),
		)
		console.info('Endpoint list set to ' + list.length + ' items')
	}

	/**
	 * Get the endpoint list
	 *
	 * @description
	 * Returns the currently active endpoint list. Note that the return value is non-reactive.
	 *
	 * For reactive usage, either:
	 * 1. Reference the `endpointList` state directly:
	 * ```js
	 * const endpointList = useEndpointStore().endpointList // reactive state
	 * ```
	 * 2. Or wrap in a `computed` property:
	 * ```js
	 * const endpointList = computed(() => useEndpointStore().getEndpointList())
	 * ```
	 *
	 * @return {Endpoint[]} The endpoint list
	 */
	const getEndpointList = (): Endpoint[] => endpointList.value as Endpoint[]

	// ################################
	// ||          Actions           ||
	// ################################

	/**
	 * Refresh the endpoint list
	 * @param search - The search string to filter the list
	 * @return {Promise<{ response: Response, data: TEndpoint[], entities: Endpoint[] }>} The response, data, and entities
	 */
	const refreshEndpointList = async (search: string = null): Promise<{ response: Response, data: TEndpoint[], entities: Endpoint[] }> => {
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

		const data = (await response.json()).results as TEndpoint[]
		const entities = data.map(endpointItem => new Endpoint(endpointItem))

		setEndpointList(data)

		return { response, data, entities }
	}

	/**
	 * Fetch a single endpoint
	 * @param id - The ID of the endpoint to fetch
	 * @return {Promise<{ response: Response, data: TEndpoint, entity: Endpoint }>} The response, data, and entity
	 */
	const fetchEndpoint = async (id: string): Promise<{ response: Response, data: TEndpoint, entity: Endpoint }> => {
		if (!id) {
			throw new MissingParameterError('id')
		}

		const endpoint = `${apiEndpoint}/${id}`

		const response = await fetch(endpoint, {
			method: 'GET',
		})

		const data = await response.json() as TEndpoint
		const entity = new Endpoint(data)

		setEndpointItem(data)

		return { response, data, entity }
	}

	/**
	 * Delete a endpoint
	 * @param id - The ID of the endpoint to delete
	 * @return {Promise<{ response: Response }>} The response
	 */
	const deleteEndpoint = async (id: string): Promise<{ response: Response }> => {
		if (!id) {
			throw new MissingParameterError('id')
		}

		console.info('Deleting consumer...')

		const endpoint = `${apiEndpoint}/${id}`

		const response = await fetch(endpoint, {
			method: 'DELETE',
		})

		response.ok && setEndpointItem(null)
		refreshEndpointList()

		return { response }
	}

	/**
	 * Save a endpoint
	 * @param endpointItem - The endpoint item to save
	 * @return {Promise<{ response: Response, data: TEndpoint, entity: Endpoint }>} The response, data, and entity
	 */
	const saveEndpoint = async (endpointItem: Endpoint): Promise<{ response: Response, data: TEndpoint, entity: Endpoint }> => {
		if (!endpointItem) {
			throw new MissingParameterError('endpointItem')
		}
		if (!(endpointItem instanceof Endpoint)) {
			throw new Error('endpointItem is not an instance of Endpoint')
		}

		// DISABLED UNTIL TIME CAN BE SPENT TO DO VALIDATION PROPERLY
		// verify data with Zod
		// const validationResult = endpointItem.validate()
		// if (!validationResult.success) {
		//  console.error(validationResult.error)
		//  console.info(endpointItem)
		//  throw new ValidationError(validationResult.error)
		// }

		// delete "updated"
		const clonedEndpoint = endpointItem.cloneRaw()
		delete clonedEndpoint.updated
		endpointItem = new Endpoint(clonedEndpoint)

		console.info('Saving consumer...')

		const isNewEndpoint = !endpointItem.id
		const endpoint = isNewEndpoint
			? apiEndpoint
			: `${apiEndpoint}/${endpointItem.id}`
		const method = isNewEndpoint ? 'POST' : 'PUT'

		const response = await fetch(
			endpoint,
			{
				method,
				headers: {
					'Content-Type': 'application/json',
				},
				body: JSON.stringify(endpointItem),
			},
		)

		const data = await response.json() as TEndpoint
		const entity = new Endpoint(data)

		setEndpointItem(data)
		refreshEndpointList()

		return { response, data, entity }
	}

	/**
	 * Export an endpoint
	 * @param id - The ID of the endpoint to export
	 */
	const exportEndpoint = (id: string) => {
		if (!id) {
			throw new MissingParameterError('id')
		}

		importExportStore.exportFile(
			id,
			'endpoint',
		)
			.then(({ download }) => {
				download()
			})
			.catch((err) => {
				console.error('Error exporting endpoint:', err)
				throw err
			})
	}

	return {
		// state
		endpointItem,
		endpointList,
		// setter / getter
		setEndpointItem,
		getEndpointItem,
		setEndpointList,
		getEndpointList,
		// actions
		refreshEndpointList,
		fetchEndpoint,
		deleteEndpoint,
		saveEndpoint,
		exportEndpoint,
	}

})
