import { ref } from 'vue'
import { defineStore } from 'pinia'
import { Webhook, TWebhook } from '../../entities/index.js'
import { MissingParameterError } from '../../services/errors/index.js'

const apiEndpoint = '/index.php/apps/openconnector/api/webhooks'

export const useWebhookStore = defineStore('webhook', () => {
	// state
	const webhookItem = ref<Webhook>(null)
	const webhookList = ref<Webhook[]>([])
	const viewMode = ref<string>('cards')

	// ################################
	// ||    Setters and Getters     ||
	// ################################

	/**
	 * Set the active webhook item.
	 * @param item - The webhook item to set
	 */
	const setWebhookItem = (item: Webhook | TWebhook) => {
		webhookItem.value = item && new Webhook(item)
		console.info('Active webhook item set to ' + (item ? item.id : 'null'))
	}

	/**
	 * Get the active webhook item.
	 *
	 * @description
	 * Returns the currently active webhook item. Note that the return value is non-reactive.
	 *
	 * For reactive usage, either:
	 * 1. Reference the `webhookItem` state directly:
	 * ```js
	 * const webhookItem = useWebhookStore().webhookItem // reactive state
	 * ```
	 * 2. Or wrap in a `computed` property:
	 * ```js
	 * const webhookItem = computed(() => useWebhookStore().getWebhookItem())
	 * ```
	 *
	 * @return {Webhook | null} The active webhook item
	 */
	const getWebhookItem = (): Webhook | null => webhookItem.value as Webhook | null

	/**
	 * Set the active webhook list.
	 * @param list - The webhook list to set
	 */
	const setWebhookList = (list: Webhook[] | TWebhook[]) => {
		webhookList.value = list.map((item) => new Webhook(item))
		console.info('Webhook list set to ' + list.length + ' items')
	}

	/**
	 * Get the active webhook list.
	 *
	 * @description
	 * Returns the currently active webhook list. Note that the return value is non-reactive.
	 *
	 * For reactive usage, either:
	 * 1. Reference the `webhookList` state directly:
	 * ```js
	 * const webhookList = useWebhookStore().webhookList // reactive state
	 * ```
	 * 2. Or wrap in a `computed` property:
	 * ```js
	 * const webhookList = computed(() => useWebhookStore().getWebhookList())
	 * ```
	 *
	 * @return {Webhook[]} The active webhook list
	 */
	const getWebhookList = (): Webhook[] => webhookList.value as Webhook[]

	/**
	 * Set the view mode.
	 * @param mode - The view mode to set
	 */
	const setViewMode = (mode: string) => {
		viewMode.value = mode
		console.info('Webhook view mode set to ' + mode)
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
	 * const viewMode = useWebhookStore().viewMode // reactive state
	 * ```
	 * 2. Or wrap in a `computed` property:
	 * ```js
	 * const viewMode = computed(() => useWebhookStore().getViewMode())
	 * ```
	 *
	 * @return {string} The active view mode
	 */
	const getViewMode = (): string => viewMode.value as string

	// ################################
	// ||          Actions           ||
	// ################################

	/**
	 * Refresh the webhook list
	 * @param search - The search string to filter the list
	 * @return {Promise<{ response: Response, data: TWebhook[], entities: Webhook[] }>} The response, data, and entities
	 */
	const refreshWebhookList = async (search: string = null): Promise<{ response: Response, data: TWebhook[], entities: Webhook[] }> => {
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

		const data = (await response.json()).results as TWebhook[]
		const entities = data.map(webhookItem => new Webhook(webhookItem))

		setWebhookList(data)

		return { response, data, entities }
	}

	/**
	 * Fetch a single webhook
	 * @param id - The ID of the webhook to fetch
	 * @return {Promise<{ response: Response, data: TWebhook, entity: Webhook }>} The response, data, and entity
	 */
	const fetchWebhook = async (id: string): Promise<{ response: Response, data: TWebhook, entity: Webhook }> => {
		if (!id) {
			throw new MissingParameterError('id')
		}

		const endpoint = `${apiEndpoint}/${id}`

		const response = await fetch(endpoint, {
			method: 'GET',
		})

		const data = await response.json() as TWebhook
		const entity = new Webhook(data)

		setWebhookItem(data)

		return { response, data, entity }
	}

	/**
	 * Delete a webhook
	 * @param id - The ID of the webhook to delete
	 * @return {Promise<{ response: Response }>} The response
	 */
	const deleteWebhook = async (id: string): Promise<{ response: Response }> => {
		if (!id) {
			throw new MissingParameterError('id')
		}

		console.info('Deleting webhook...')

		const endpoint = `${apiEndpoint}/${id}`

		const response = await fetch(endpoint, {
			method: 'DELETE',
		})

		response.ok && setWebhookItem(null)
		refreshWebhookList()

		return { response }
	}

	/**
	 * Save a webhook
	 * @param webhookItem - The webhook item to save
	 * @return {Promise<{ response: Response, data: TWebhook, entity: Webhook }>} The response, data, and entity
	 */
	const saveWebhook = async (webhookItem: Webhook): Promise<{ response: Response, data: TWebhook, entity: Webhook }> => {
		if (!webhookItem) {
			throw new MissingParameterError('webhookItem')
		}
		if (!(webhookItem instanceof Webhook)) {
			throw new Error('webhookItem is not an instance of Webhook')
		}

		// DISABLED UNTIL TIME CAN BE SPENT TO DO VALIDATION PROPERLY
		// verify data with Zod
		// const validationResult = webhookItem.validate()
		// if (!validationResult.success) {
		//  console.error(validationResult.error)
		//  console.info(webhookItem)
		//  throw new ValidationError(validationResult.error)
		// }

		console.info('Saving webhook...')

		const isNewWebhook = !webhookItem.id
		const endpoint = isNewWebhook
			? apiEndpoint
			: `${apiEndpoint}/${webhookItem.id}`
		const method = isNewWebhook ? 'POST' : 'PUT'

		const response = await fetch(
			endpoint,
			{
				method,
				headers: {
					'Content-Type': 'application/json',
				},
				body: JSON.stringify(webhookItem),
			},
		)

		const data = await response.json() as TWebhook
		const entity = new Webhook(data)

		setWebhookItem(data)
		refreshWebhookList()

		return { response, data, entity }
	}

	return {
		// state
		webhookItem,
		webhookList,
		viewMode,

		// setters and getters
		setWebhookItem,
		getWebhookItem,
		setWebhookList,
		getWebhookList,
		setViewMode,
		getViewMode,

		// actions
		refreshWebhookList,
		fetchWebhook,
		deleteWebhook,
		saveWebhook,
	}
})
