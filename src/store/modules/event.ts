import { ref } from 'vue'
import { defineStore } from 'pinia'
import { Event, TEvent } from '../../entities/index.js'
import { MissingParameterError } from '../../services/errors/index.js'
import { useLogStore } from './log'

const apiEndpoint = '/index.php/apps/openconnector/api/events'

export const useEventStore = defineStore('event', () => {
	// state
	const eventItem = ref<Event>(null)
	const eventTest = ref<object>(null)
	const eventRun = ref<object>(null)
	const eventList = ref<Event[]>([])
	const eventLog = ref<object>(null)
	const eventLogs = ref<object[]>([])
	const eventArgumentKey = ref<string>(null)

	// ################################
	// ||    Setters and Getters     ||
	// ################################

	// Event Item
	/**
	 * Set the active event item.
	 * @param item - The event item to set
	 */
	const setEventItem = (item: Event | TEvent) => {
		eventItem.value = item && new Event(item)
		console.info('Active event item set to ' + (item ? item.id : 'null'))
	}

	/**
	 * Get the active event item.
	 *
	 * @description
	 * Returns the currently active event item. Note that the return value is non-reactive.
	 *
	 * For reactive usage, either:
	 * 1. Reference the `eventItem` state directly:
	 * ```js
	 * const eventItem = useEventStore().eventItem // reactive state
	 * ```
	 * 2. Or wrap in a `computed` property:
	 * ```js
	 * const eventItem = computed(() => useEventStore().getEventItem())
	 * ```
	 *
	 * @return {Event | null} The active event item
	 */
	const getEventItem = (): Event | null => eventItem.value as Event | null

	// Event Test
	/**
	 * Set the active event test item.
	 * @param item - The event test item to set
	 */
	const setEventTest = (item: object) => {
		eventTest.value = item
		console.info('Active event test item set to ' + item)
	}

	/**
	 * Get the active event test item.
	 *
	 * @description
	 * Returns the currently active event test item. Note that the return value is non-reactive.
	 *
	 * For reactive usage, either:
	 * 1. Reference the `eventTest` state directly:
	 * ```js
	 * const eventTest = useEventStore().eventTest // reactive state
	 * ```
	 * 2. Or wrap in a `computed` property:
	 * ```js
	 * const eventTest = computed(() => useEventStore().getEventTest())
	 * ```
	 *
	 * @return {object | null} The active event test item
	 */
	const getEventTest = (): object | null => eventTest.value

	// Event Run
	/**
	 * Set the active event run item.
	 * @param item - The event run item to set
	 */
	const setEventRun = (item: object) => {
		eventRun.value = item
		console.info('Active event run item set to ' + item)
	}

	/**
	 * Get the active event run item.
	 *
	 * @description
	 * Returns the currently active event run item. Note that the return value is non-reactive.
	 *
	 * For reactive usage, either:
	 * 1. Reference the `eventRun` state directly:
	 * ```js
	 * const eventRun = useEventStore().eventRun // reactive state
	 * ```
	 * 2. Or wrap in a `computed` property:
	 * ```js
	 * const eventRun = computed(() => useEventStore().getEventRun())
	 * ```
	 *
	 * @return {object | null} The active event run item
	 */
	const getEventRun = (): object | null => eventRun.value

	// Event List
	/**
	 * Set the active event list.
	 * @param item - The event list to set
	 */
	const setEventList = (item: Event[] | TEvent[]) => {
		eventList.value = item && item.map((event: TEvent) => new Event(event))
		console.info('Active event list set to ' + item.length + ' items')
	}

	/**
	 * Get the active event list.
	 *
	 * @description
	 * Returns the currently active event list. Note that the return value is non-reactive.
	 *
	 * For reactive usage, either:
	 * 1. Reference the `eventList` state directly:
	 * ```js
	 * const eventList = useEventStore().eventList // reactive state
	 * ```
	 * 2. Or wrap in a `computed` property:
	 * ```js
	 * const eventList = computed(() => useEventStore().getEventList())
	 * ```
	 *
	 * @return {Event[]} The active event list
	 */
	const getEventList = (): Event[] => eventList.value as Event[]

	// Event Log
	/**
	 * Set the active event log item.
	 * @param item - The event log item to set
	 */
	const setEventLog = (item: object) => {
		eventLog.value = item
		console.info('Active event log item set to ' + item)
	}

	/**
	 * Get the active event log item.
	 *
	 * @description
	 * Returns the currently active event log item. Note that the return value is non-reactive.
	 *
	 * For reactive usage, either:
	 * 1. Reference the `eventLog` state directly:
	 * ```js
	 * const eventLog = useEventStore().eventLog // reactive state
	 * ```
	 * 2. Or wrap in a `computed` property:
	 * ```js
	 * const eventLog = computed(() => useEventStore().getEventLog())
	 * ```
	 *
	 * @return {object | null} The active event log item
	 */
	const getEventLog = (): object | null => eventLog.value

	// Event Logs
	/**
	 * Set the active event logs.
	 * @param item - The event logs to set
	 */
	const setEventLogs = (item: object[]) => {
		eventLogs.value = item
		console.info('Active event logs set to ' + item.length + ' items')
	}

	/**
	 * Get the active event logs.
	 *
	 * @description
	 * Returns the currently active event logs. Note that the return value is non-reactive.
	 *
	 * For reactive usage, either:
	 * 1. Reference the `eventLogs` state directly:
	 * ```js
	 * const eventLogs = useEventStore().eventLogs // reactive state
	 * ```
	 * 2. Or wrap in a `computed` property:
	 * ```js
	 * const eventLogs = computed(() => useEventStore().getEventLogs())
	 * ```
	 *
	 * @return {object[]} The active event logs
	 */
	const getEventLogs = (): object[] => eventLogs.value

	// Event Argument Key
	/**
	 * Set the active event argument key.
	 * @param item - The event argument key to set
	 */
	const setEventArgumentKey = (item: string) => {
		eventArgumentKey.value = item
		console.info('Active event argument key set to ' + item)
	}

	/**
	 * Get the active event argument key.
	 *
	 * @description
	 * Returns the currently active event argument key. Note that the return value is non-reactive.
	 *
	 * For reactive usage, either:
	 * 1. Reference the `eventArgumentKey` state directly:
	 * ```js
	 * const eventArgumentKey = useEventStore().eventArgumentKey // reactive state
	 * ```
	 * 2. Or wrap in a `computed` property:
	 * ```js
	 * const eventArgumentKey = computed(() => useEventStore().getEventArgumentKey())
	 * ```
	 *
	 * @return {string | null} The active event argument key
	 */
	const getEventArgumentKey = (): string | null => eventArgumentKey.value

	// ################################
	// ||          Actions           ||
	// ################################

	// Event
	/**
	 * Refresh the event list
	 * @param search - The search string to filter the list
	 * @return {Promise<{ response: Response, data: TEvent[], entities: Event[] }>} The response, data, and entities
	 */
	const refreshEventList = async (search: string = null): Promise<{ response: Response, data: TEvent[], entities: Event[] }> => {
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

		const data = (await response.json()).results as TEvent[]
		const entities = data.map(eventItem => new Event(eventItem))

		setEventList(data)

		return { response, data, entities }
	}

	/**
	 * Fetch a single event
	 * @param id - The ID of the event to fetch
	 * @return {Promise<{ response: Response, data: TEvent, entity: Event }>} The response, data, and entity
	 */
	const fetchEvent = async (id: string): Promise<{ response: Response, data: TEvent, entity: Event }> => {
		if (!id) {
			throw new MissingParameterError('id')
		}

		const endpoint = `${apiEndpoint}/${id}`

		const response = await fetch(endpoint, {
			method: 'GET',
		})

		const data = await response.json() as TEvent
		const entity = new Event(data)

		setEventItem(data)

		return { response, data, entity }
	}

	/**
	 * Delete a event
	 * @param id - The ID of the event to delete
	 * @return {Promise<{ response: Response }>} The response
	 */
	const deleteEvent = async (id: string): Promise<{ response: Response }> => {
		if (!id) {
			throw new MissingParameterError('id')
		}

		console.info('Deleting event...')

		const endpoint = `${apiEndpoint}/${id}`

		const response = await fetch(endpoint, {
			method: 'DELETE',
		})

		response.ok && setEventItem(null)
		refreshEventList()

		return { response }
	}

	/**
	 * Save a event
	 * @param eventItem - The event item to save
	 * @return {Promise<{ response: Response, data: TEvent, entity: Event }>} The response, data, and entity
	 */
	const saveEvent = async (eventItem: Event): Promise<{ response: Response, data: TEvent, entity: Event }> => {
		if (!eventItem) {
			throw new MissingParameterError('eventItem')
		}
		if (!(eventItem instanceof Event)) {
			throw new Error('eventItem is not an instance of Event')
		}

		// DISABLED UNTIL TIME CAN BE SPENT TO DO VALIDATION PROPERLY
		// verify data with Zod
		// const validationResult = eventItem.validate()
		// if (!validationResult.success) {
		//  console.error(validationResult.error)
		//  console.info(eventItem)
		//  throw new ValidationError(validationResult.error)
		// }

		// delete "updated"
		const clonedEvent = eventItem.cloneRaw()
		delete clonedEvent.updated
		eventItem = new Event(clonedEvent)

		console.info('Saving consumer...')

		const isNewEvent = !eventItem.id
		const endpoint = isNewEvent
			? apiEndpoint
			: `${apiEndpoint}/${eventItem.id}`
		const method = isNewEvent ? 'POST' : 'PUT'

		const response = await fetch(
			endpoint,
			{
				method,
				headers: {
					'Content-Type': 'application/json',
				},
				body: JSON.stringify(eventItem),
			},
		)

		const data = await response.json() as TEvent
		const entity = new Event(data)

		setEventItem(data)
		refreshEventList()

		return { response, data, entity }
	}

	// event test
	/**
	 * Tests the current event
	 * @param {string} id - The ID of the event to test
	 * @return {Promise<{ response: Response, data: object }>} The response and data
	 */
	const testEvent = async (id: string): Promise<{ response: Response, data: object }> => {
		if (!id) {
			throw new MissingParameterError('id')
		}

		console.info('Testing event...')

		const endpoint = `/index.php/apps/openconnector/api/events-test/${id}`

		const response = await fetch(endpoint, {
			method: 'POST',
			headers: { 'Content-Type': 'application/json' },
			body: JSON.stringify([]),
		})

		const data = await response.json()

		setEventTest(data)
		refreshEventLogs({ event_id: id })

		return { response, data }
	}

	// event run
	/**
	 * Runs an event by ID
	 * @param {string} id - Event ID to run
	 * @return {Promise<{ response: Response, data: object }>} The response and data
	 */
	const runEvent = async (id: string): Promise<{ response: Response, data: object }> => {
		if (!id) {
			throw new MissingParameterError('id')
		}

		console.info('Running event...')
		const endpoint = `/index.php/apps/openconnector/api/events-run/${id}`

		const response = await fetch(endpoint, {
			method: 'POST',
			headers: { 'Content-Type': 'application/json' },
			body: JSON.stringify([]),
		})

		const data = await response.json()
		setEventRun(data)
		refreshEventLogs({ event_id: id })

		return { response, data }
	}

	// event logs
	/**
	 * Refresh the event logs
	 * @param filters - Optional filters to apply to the logs
	 * @return {Promise<{ response: Response, data: object[] }>} The response and data
	 */
	const refreshEventLogs = async (filters: object = {}) => {
		const logStore = useLogStore()
		logStore.setLogsLoading(true)

		try {
			// Build query parameters
			const queryParams = new URLSearchParams()
			// Only add event_id if not already present in filters
			if (!('event_id' in filters) && eventItem.value?.id) {
				queryParams.append('event_id', eventItem.value.id.toString())
			}
			// Add other filters
			Object.entries(filters).forEach(([key, value]) => {
				if (value !== null && value !== undefined && value !== '') {
					queryParams.append(key, value.toString())
				}
			})
			// Build the endpoint
			const endpoint = `/index.php/apps/openconnector/api/events/logs${queryParams.toString() ? '?' + queryParams.toString() : ''}`
			const response = await fetch(endpoint, {
				method: 'GET',
			})
			const data = await response.json()
			setEventLogs(data)
			return { response, data }
		} catch (error) {
			console.error('Error refreshing event logs:', error)
			throw error
		} finally {
			logStore.setLogsLoading(false)
		}
	}

	return {
		// state
		eventItem,
		eventTest,
		eventRun,
		eventList,
		eventLog,
		eventLogs,
		eventArgumentKey,

		// setters and getters
		setEventItem,
		getEventItem,
		setEventTest,
		getEventTest,
		setEventRun,
		getEventRun,
		setEventList,
		getEventList,
		setEventLog,
		getEventLog,
		setEventLogs,
		getEventLogs,
		setEventArgumentKey,
		getEventArgumentKey,

		// actions
		refreshEventList,
		fetchEvent,
		deleteEvent,
		saveEvent,
		testEvent,
		runEvent,
		refreshEventLogs,
	}
})
