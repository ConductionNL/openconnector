/* eslint-disable @typescript-eslint/no-explicit-any */
import { ref } from 'vue'
import { defineStore } from 'pinia'
import { Contract, TContract } from '../../entities/index.js'

const apiEndpoint = '/index.php/apps/openconnector/api/synchronization-contracts'

/**
 * Contract store for managing synchronization contracts
 *
 * @description
 * This store manages the state and operations for synchronization contracts,
 * including fetching, creating, updating, and deleting contracts.
 */
export const useContractStore = defineStore('contract', () => {
	// ################################
	// ||           State            ||
	// ################################

	/** @type {import('vue').Ref<Contract|null>} Current contract item */
	const contractItem = ref<Contract>(null)

	/** @type {import('vue').Ref<Contract[]>} List of contracts */
	const contractsList = ref<Contract[]>([])

	/** @type {import('vue').Ref<boolean>} Loading state for contracts */
	const contractsLoading = ref<boolean>(false)

	/** @type {import('vue').Ref<object>} Pagination information */
	const contractsPagination = ref<object>({
		page: 1,
		pages: 1,
		results: 0,
		total: 0,
	})

	/** @type {import('vue').Ref<object>} Current filters */
	const contractsFilters = ref<object>({})

	/** @type {import('vue').Ref<object>} Statistics data */
	const contractsStatistics = ref<object>({})

	/** @type {import('vue').Ref<object>} Performance data */
	const contractsPerformance = ref<object>({})

	// ################################
	// ||    Setters and Getters     ||
	// ################################

	/**
	 * Set the active contract item
	 *
	 * @param {Contract|TContract|null} item - The contract item to set
	 * @return {void}
	 */
	const setContractItem = (item: Contract | TContract | null): void => {
		contractItem.value = item && new Contract(item)
		console.info('Active contract item set to ' + (item ? (item as any).id : 'null'))
	}

	/**
	 * Get the active contract item
	 *
	 * @description
	 * Returns the currently active contract item. Note that the return value is non-reactive.
	 *
	 * For reactive usage, either:
	 * 1. Reference the `contractItem` state directly:
	 * ```js
	 * const contractItem = useContractStore().contractItem // reactive state
	 * ```
	 * 2. Or wrap in a `computed` property:
	 * ```js
	 * const contractItem = computed(() => useContractStore().getContractItem())
	 * ```
	 *
	 * @return {Contract | null} The active contract item
	 */
	const getContractItem = (): Contract | null => contractItem.value as Contract | null

	/**
	 * Set the contract list
	 *
	 * @param {Contract[]|TContract[]} list - The contract list to set
	 * @return {void}
	 */
	const setContractsList = (list: Contract[] | TContract[]): void => {
		contractsList.value = list.map((item) => new Contract(item))
		console.info('Contract list set to ' + list.length + ' items')
	}

	/**
	 * Get the contract list
	 *
	 * @description
	 * Returns the currently active contract list. Note that the return value is non-reactive.
	 *
	 * For reactive usage, either:
	 * 1. Reference the `contractsList` state directly:
	 * ```js
	 * const contractsList = useContractStore().contractsList // reactive state
	 * ```
	 * 2. Or wrap in a `computed` property:
	 * ```js
	 * const contractsList = computed(() => useContractStore().getContractsList())
	 * ```
	 *
	 * @return {Contract[]} The contract list
	 */
	const getContractsList = (): Contract[] => contractsList.value as Contract[]

	/**
	 * Set contracts filters
	 *
	 * @param {object} filters - The filters to set
	 * @return {void}
	 */
	const setContractsFilters = (filters: object): void => {
		contractsFilters.value = filters
		console.info('Contracts filters set', filters)
	}

	/**
	 * Set contracts loading state
	 *
	 * @param {boolean} loading - The loading state
	 * @return {void}
	 */
	const setContractsLoading = (loading: boolean): void => {
		contractsLoading.value = loading
	}

	/**
	 * Set contracts pagination
	 *
	 * @param {object} pagination - The pagination object
	 * @return {void}
	 */
	const setContractsPagination = (pagination: object): void => {
		contractsPagination.value = pagination
	}

	// ################################
	// ||          Actions           ||
	// ################################

	/**
	 * Fetch contracts from the API
	 *
	 * @param {object} options - Request options
	 * @param {number} options.page - Page number
	 * @param {object} options.filters - Filters to apply
	 * @return {Promise<{ response: Response, data: TContract[], entities: Contract[] }>}
	 */
	const fetchContracts = async (options: { page?: number, filters?: object } = {}): Promise<{ response: Response, data: TContract[], entities: Contract[] }> => {
		setContractsLoading(true)

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
			const data = (responseData.results || responseData) as TContract[]
			const entities = data.map(contractItem => new Contract(contractItem))

			setContractsList(data)

			// Set pagination if available
			if (responseData.pagination) {
				setContractsPagination(responseData.pagination)
			}

			return { response, data, entities }
		} finally {
			setContractsLoading(false)
		}
	}

	/**
	 * Fetch a single contract
	 *
	 * @param {string} id - The ID of the contract to fetch
	 * @return {Promise<{ response: Response, data: TContract, entity: Contract }>}
	 */
	const fetchContract = async (id: string): Promise<{ response: Response, data: TContract, entity: Contract }> => {
		if (!id) {
			throw new Error('Contract ID is required')
		}

		const endpoint = `${apiEndpoint}/${id}`

		const response = await fetch(endpoint, {
			method: 'GET',
		})

		const data = await response.json() as TContract
		const entity = new Contract(data)

		setContractItem(data)

		return { response, data, entity }
	}

	/**
	 * Delete a contract
	 *
	 * @param {string} id - The ID of the contract to delete
	 * @return {Promise<{ response: Response }>}
	 */
	const deleteContract = async (id: string): Promise<{ response: Response }> => {
		if (!id) {
			throw new Error('Contract ID is required')
		}

		console.info('Deleting contract...')

		const endpoint = `${apiEndpoint}/${id}`

		const response = await fetch(endpoint, {
			method: 'DELETE',
		})

		if (response.ok && (contractItem.value as any)?.id === id) {
			setContractItem(null)
		}

		return { response }
	}

	/**
	 * Delete multiple contracts
	 *
	 * @param {string[]} ids - Array of contract IDs to delete
	 * @return {Promise<void>}
	 */
	const deleteMultiple = async (ids: string[]): Promise<void> => {
		if (!ids || ids.length === 0) return

		console.info('Deleting multiple contracts...')

		// Delete contracts one by one (can be optimized with bulk API later)
		await Promise.all(ids.map(id => deleteContract(id)))
	}

	/**
	 * Enforce a contract (equivalent to executing/running it)
	 *
	 * @param {string} id - The ID of the contract to enforce
	 * @return {Promise<{ response: Response }>}
	 */
	const enforceContract = async (id: string): Promise<{ response: Response }> => {
		if (!id) {
			throw new Error('Contract ID is required')
		}

		console.info('Enforcing contract...')

		const endpoint = `${apiEndpoint}/${id}/enforce`

		const response = await fetch(endpoint, {
			method: 'POST',
		})

		return { response }
	}

	/**
	 * Fetch contract statistics
	 *
	 * @return {Promise<{ response: Response, data: object }>}
	 */
	const fetchStatistics = async (): Promise<{ response: Response, data: object }> => {
		const endpoint = `${apiEndpoint}/statistics`

		const response = await fetch(endpoint, {
			method: 'GET',
		})

		const data = await response.json()
		contractsStatistics.value = data

		return { response, data }
	}

	/**
	 * Fetch contract performance data
	 *
	 * @return {Promise<{ response: Response, data: object }>}
	 */
	const fetchPerformance = async (): Promise<{ response: Response, data: object }> => {
		const endpoint = `${apiEndpoint}/performance`

		const response = await fetch(endpoint, {
			method: 'GET',
		})

		const data = await response.json()
		contractsPerformance.value = data

		return { response, data }
	}

	/**
	 * Export filtered contracts
	 *
	 * @return {Promise<{ response: Response }>}
	 */
	const exportFiltered = async (): Promise<{ response: Response }> => {
		console.info('Exporting filtered contracts...')

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
			a.download = 'contracts.csv'
			document.body.appendChild(a)
			a.click()
			window.URL.revokeObjectURL(url)
			document.body.removeChild(a)
		}

		return { response }
	}

	/**
	 * Save a contract
	 *
	 * @param {Contract} contractItem - The contract item to save
	 * @return {Promise<{ response: Response, data: TContract, entity: Contract }>}
	 */
	const saveContract = async (contractItem: Contract): Promise<{ response: Response, data: TContract, entity: Contract }> => {
		if (!contractItem) {
			throw new Error('Contract item is required')
		}
		if (!(contractItem instanceof Contract)) {
			throw new Error('contractItem is not an instance of Contract')
		}

		console.info('Saving contract...')

		const isNewContract = !(contractItem as any).id
		const endpoint = isNewContract
			? apiEndpoint
			: `${apiEndpoint}/${(contractItem as any).id}`
		const method = isNewContract ? 'POST' : 'PUT'

		const response = await fetch(
			endpoint,
			{
				method,
				headers: {
					'Content-Type': 'application/json',
				},
				body: JSON.stringify(contractItem.cloneRaw()),
			},
		)

		const data = await response.json() as TContract
		const entity = new Contract(data)

		setContractItem(data)

		return { response, data, entity }
	}

	return {
		// state
		contractItem,
		contractsList,
		contractsLoading,
		contractsPagination,
		contractsFilters,
		contractsStatistics,
		contractsPerformance,

		// setters and getters
		setContractItem,
		getContractItem,
		setContractsList,
		getContractsList,
		setContractsFilters,
		setContractsLoading,
		setContractsPagination,

		// actions
		fetchContracts,
		fetchContract,
		deleteContract,
		deleteMultiple,
		enforceContract,
		fetchStatistics,
		fetchPerformance,
		exportFiltered,
		saveContract,
	}
})
