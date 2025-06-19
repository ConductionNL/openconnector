import { ref } from 'vue'
import { defineStore } from 'pinia'
import { Consumer, TConsumer } from '../../entities/index.js'
import { MissingParameterError } from '../../services/errors/index.js'

export const apiEndpoint = '/index.php/apps/openconnector/api/consumers'

export const useConsumerStore = defineStore('consumer', () => {
	// state
	const consumerItem = ref<Consumer | null>(null)
	const consumerList = ref<Consumer[]>([])
	const viewMode = ref<string>('cards')

	// ################################
	// ||    Setters and Getters     ||
	// ################################

	/**
	 * Set the active consumer item
	 * @param item - The consumer item to set
	 */
	const setConsumerItem = (item: Consumer | TConsumer) => {
		consumerItem.value = item && new Consumer(item)
		console.info('Active consumer item set to ' + (item ? item.id : 'null'))
	}

	/**
	 * Get the active consumer item.
	 *
	 * @description
	 * Returns the currently active consumer item. Note that the return value is non-reactive.
	 *
	 * For reactive usage, either:
	 * 1. Reference the `consumerItem` state directly:
	 * ```js
	 * const consumerItem = useConsumerStore().consumerItem // reactive state
	 * ```
	 * 2. Or wrap in a `computed` property:
	 * ```js
	 * const consumerItem = computed(() => useConsumerStore().getConsumerItem())
	 * ```
	 *
	 * @return {Consumer | null} The active consumer item
	 */
	const getConsumerItem = (): Consumer | null => consumerItem.value as Consumer | null

	/**
	 * Set the consumer list
	 * @param list - The list of consumers to set
	 */
	const setConsumerList = (list: (Consumer | TConsumer)[]) => {
		consumerList.value = list.map(
			(item) => new Consumer(item),
		)
		console.info('Consumer list set to ' + list.length + ' items')
	}

	/**
	 * Get the consumer list
	 *
	 * @description
	 * Returns the currently active consumer list. Note that the return value is non-reactive.
	 *
	 * For reactive usage, either:
	 * 1. Reference the `consumerList` state directly:
	 * ```js
	 * const consumerList = useConsumerStore().consumerList // reactive state
	 * ```
	 * 2. Or wrap in a `computed` property:
	 * ```js
	 * const consumerList = computed(() => useConsumerStore().getConsumerList())
	 * ```
	 *
	 * @return {Consumer[]} The consumer list
	 */
	const getConsumerList = (): Consumer[] => consumerList.value as Consumer[]

	/**
	 * Set the view mode.
	 * @param mode - The view mode to set
	 */
	const setViewMode = (mode: string) => {
		viewMode.value = mode
		console.info('Consumer view mode set to ' + mode)
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
	 * const viewMode = useConsumerStore().viewMode // reactive state
	 * ```
	 * 2. Or wrap in a `computed` property:
	 * ```js
	 * const viewMode = computed(() => useConsumerStore().getViewMode())
	 * ```
	 *
	 * @return {string} The active view mode
	 */
	const getViewMode = (): string => viewMode.value as string

	// ################################
	// ||          Actions           ||
	// ################################

	/**
	 * Refresh the consumer list
	 * @param search - The search string to filter the list
	 * @return {Promise<{ response: Response, data: TConsumer[], entities: Consumer[] }>} The response, data, and entities
	 */
	const refreshConsumerList = async (search: string = null): Promise<{ response: Response, data: TConsumer[], entities: Consumer[] }> => {
		const queryParams = new URLSearchParams()

		if (search && search !== null && search !== '') {
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

		const data = (await response.json()).results as TConsumer[]
		const entities = data.map((item: TConsumer) => new Consumer(item))

		setConsumerList(data)

		return { response, data, entities }
	}

	/**
	 * Get a single consumer
	 * @param id - The ID of the consumer to get
	 * @return {Promise<{ response: Response, data: TConsumer, entity: Consumer }>} The response, data, and entity
	 */
	const fetchConsumer = async (id: string): Promise<{ response: Response, data: TConsumer, entity: Consumer }> => {
		if (!id) {
			throw new MissingParameterError('id')
		}

		const endpoint = `${apiEndpoint}/${id}`

		const response = await fetch(endpoint, {
			method: 'GET',
		})

		const data = await response.json() as TConsumer
		const entity = new Consumer(data)

		setConsumerItem(data)

		return { response, data, entity }
	}

	/**
	 * Delete a consumer
	 * @param id - The ID of the consumer to delete
	 * @return {Promise<{ response: Response }>} The response
	 */
	const deleteConsumer = async (id: string): Promise<{ response: Response }> => {
		if (!id) {
			throw new MissingParameterError('id')
		}

		console.info('Deleting consumer...')

		const endpoint = `${apiEndpoint}/${id}`

		const response = await fetch(endpoint, {
			method: 'DELETE',
		})

		response.ok && setConsumerItem(null)
		refreshConsumerList()

		return { response }
	}

	/**
	 * Save a consumer
	 * @param consumerItem - The consumer item to save
	 * @return {Promise<{ response: Response, data: TConsumer, entity: Consumer }>} The response, data, and entity
	 */
	const saveConsumer = async (consumerItem: Consumer): Promise<{ response: Response, data: TConsumer, entity: Consumer }> => {
		if (!consumerItem) {
			throw new MissingParameterError('consumerItem')
		}
		if (!(consumerItem instanceof Consumer)) {
			throw new Error('consumerItem is not an instance of Consumer')
		}

		// DISABLED UNTIL TIME CAN BE SPENT TO DO VALIDATION PROPERLY
		// verify data with Zod
		// const validationResult = consumerItem.validate()
		// if (!validationResult.success) {
		//  console.error(validationResult.error)
		//  console.info(consumerItem)
		//  throw new ValidationError(validationResult.error)
		// }

		// delete "updated"
		const clonedConsumer = consumerItem.cloneRaw()
		delete clonedConsumer.updated
		consumerItem = new Consumer(clonedConsumer)

		console.info('Saving consumer...')

		const isNewConsumer = !consumerItem.id
		const endpoint = isNewConsumer
			? apiEndpoint
			: `${apiEndpoint}/${consumerItem.id}`
		const method = isNewConsumer ? 'POST' : 'PUT'

		const response = await fetch(
			endpoint,
			{
				method,
				headers: {
					'Content-Type': 'application/json',
				},
				body: JSON.stringify(consumerItem),
			},
		)

		const data = await response.json() as TConsumer
		const entity = new Consumer(data)

		setConsumerItem(data)
		refreshConsumerList()

		return { response, data, entity }
	}

	return {
		// state
		consumerItem,
		consumerList,
		viewMode,
		// setter / getter
		setConsumerItem,
		getConsumerItem,
		setConsumerList,
		getConsumerList,
		setViewMode,
		getViewMode,
		// actions
		refreshConsumerList,
		fetchConsumer,
		deleteConsumer,
		saveConsumer,
	}
})
