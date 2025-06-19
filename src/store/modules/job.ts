import { defineStore } from 'pinia'
import { Job, TJob } from '../../entities/index.js'
import { importExportStore } from '../store.js'
import { ref } from 'vue'
import { MissingParameterError } from '../../services/errors/index.js'
import { useLogStore } from './log'

const apiEndpoint = '/index.php/apps/openconnector/api/jobs'

export const useJobStore = defineStore('job', () => {
	// state
	const jobItem = ref<Job>(null)
	const jobTest = ref<object>(null)
	const jobRun = ref<object>(null)
	const jobList = ref<Job[]>([])
	const jobLog = ref<object>(null)
	const jobLogs = ref<object[]>([])
	const jobArgumentKey = ref<string>(null)
	const viewMode = ref<string>('cards')

	// ################################
	// ||    Setters and Getters     ||
	// ################################

	// Job Item
	/**
	 * Set the active job item.
	 * @param item - The job item to set
	 */
	const setJobItem = (item: Job | TJob) => {
		jobItem.value = item && new Job(item)
		console.info('Active job item set to ' + (item ? item.id : 'null'))
	}

	/**
	 * Get the active job item.
	 *
	 * @description
	 * Returns the currently active job item. Note that the return value is non-reactive.
	 *
	 * For reactive usage, either:
	 * 1. Reference the `jobItem` state directly:
	 * ```js
	 * const jobItem = useJobStore().jobItem // reactive state
	 * ```
	 * 2. Or wrap in a `computed` property:
	 * ```js
	 * const jobItem = computed(() => useJobStore().getJobItem())
	 * ```
	 *
	 * @return {Job | null} The active job item
	 */
	const getJobItem = (): Job | null => jobItem.value as Job | null

	// Job Test
	/**
	 * Set the active job test item.
	 * @param item - The job test item to set
	 */
	const setJobTest = (item: object) => {
		jobTest.value = item
		console.info('Active job test item set to ' + item)
	}

	/**
	 * Get the active job test item.
	 *
	 * @description
	 * Returns the currently active job test item. Note that the return value is non-reactive.
	 *
	 * For reactive usage, either:
	 * 1. Reference the `jobTest` state directly:
	 * ```js
	 * const jobTest = useJobStore().jobTest // reactive state
	 * ```
	 * 2. Or wrap in a `computed` property:
	 * ```js
	 * const jobTest = computed(() => useJobStore().getJobTest())
	 * ```
	 *
	 * @return {object | null} The active job test item
	 */
	const getJobTest = (): object | null => jobTest.value

	// Job Run
	/**
	 * Set the active job run item.
	 * @param item - The job run item to set
	 */
	const setJobRun = (item: object) => {
		jobRun.value = item
		console.info('Active job run item set to ' + item)
	}

	/**
	 * Get the active job run item.
	 *
	 * @description
	 * Returns the currently active job run item. Note that the return value is non-reactive.
	 *
	 * For reactive usage, either:
	 * 1. Reference the `jobRun` state directly:
	 * ```js
	 * const jobRun = useJobStore().jobRun // reactive state
	 * ```
	 * 2. Or wrap in a `computed` property:
	 * ```js
	 * const jobRun = computed(() => useJobStore().getJobRun())
	 * ```
	 *
	 * @return {object | null} The active job run item
	 */
	const getJobRun = (): object | null => jobRun.value

	// Job List
	/**
	 * Set the active job list.
	 * @param item - The job list to set
	 */
	const setJobList = (item: Job[] | TJob[]) => {
		jobList.value = item && item.map((job: TJob) => new Job(job))
		console.info('Active job list set to ' + item.length + ' items')
	}

	/**
	 * Get the active job list.
	 *
	 * @description
	 * Returns the currently active job list. Note that the return value is non-reactive.
	 *
	 * For reactive usage, either:
	 * 1. Reference the `jobList` state directly:
	 * ```js
	 * const jobList = useJobStore().jobList // reactive state
	 * ```
	 * 2. Or wrap in a `computed` property:
	 * ```js
	 * const jobList = computed(() => useJobStore().getJobList())
	 * ```
	 *
	 * @return {Job[]} The active job list
	 */
	const getJobList = (): Job[] => jobList.value as Job[]

	// Job Log
	/**
	 * Set the active job log item.
	 * @param item - The job log item to set
	 */
	const setJobLog = (item: object) => {
		jobLog.value = item
		console.info('Active job log item set to ' + item)
	}

	/**
	 * Get the active job log item.
	 *
	 * @description
	 * Returns the currently active job log item. Note that the return value is non-reactive.
	 *
	 * For reactive usage, either:
	 * 1. Reference the `jobLog` state directly:
	 * ```js
	 * const jobLog = useJobStore().jobLog // reactive state
	 * ```
	 * 2. Or wrap in a `computed` property:
	 * ```js
	 * const jobLog = computed(() => useJobStore().getJobLog())
	 * ```
	 *
	 * @return {object | null} The active job log item
	 */
	const getJobLog = (): object | null => jobLog.value

	// Job Logs
	/**
	 * Set the active job logs.
	 * @param item - The job logs to set
	 */
	const setJobLogs = (item: object[]) => {
		jobLogs.value = item
		console.info('Active job logs set to ' + item.length + ' items')
	}

	/**
	 * Get the active job logs.
	 *
	 * @description
	 * Returns the currently active job logs. Note that the return value is non-reactive.
	 *
	 * For reactive usage, either:
	 * 1. Reference the `jobLogs` state directly:
	 * ```js
	 * const jobLogs = useJobStore().jobLogs // reactive state
	 * ```
	 * 2. Or wrap in a `computed` property:
	 * ```js
	 * const jobLogs = computed(() => useJobStore().getJobLogs())
	 * ```
	 *
	 * @return {object[]} The active job logs
	 */
	const getJobLogs = (): object[] => jobLogs.value

	// Job Argument Key
	/**
	 * Set the active job argument key.
	 * @param item - The job argument key to set
	 */
	const setJobArgumentKey = (item: string) => {
		jobArgumentKey.value = item
		console.info('Active job argument key set to ' + item)
	}

	/**
	 * Get the active job argument key.
	 *
	 * @description
	 * Returns the currently active job argument key. Note that the return value is non-reactive.
	 *
	 * For reactive usage, either:
	 * 1. Reference the `jobArgumentKey` state directly:
	 * ```js
	 * const jobArgumentKey = useJobStore().jobArgumentKey // reactive state
	 * ```
	 * 2. Or wrap in a `computed` property:
	 * ```js
	 * const jobArgumentKey = computed(() => useJobStore().getJobArgumentKey())
	 * ```
	 *
	 * @return {string | null} The active job argument key
	 */
	const getJobArgumentKey = (): string | null => jobArgumentKey.value

	/**
	 * Set the view mode.
	 * @param mode - The view mode to set
	 */
	const setViewMode = (mode: string) => {
		viewMode.value = mode
		console.info('Job view mode set to ' + mode)
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
	 * const viewMode = useJobStore().viewMode // reactive state
	 * ```
	 * 2. Or wrap in a `computed` property:
	 * ```js
	 * const viewMode = computed(() => useJobStore().getViewMode())
	 * ```
	 *
	 * @return {string} The active view mode
	 */
	const getViewMode = (): string => viewMode.value as string

	// ################################
	// ||          Actions           ||
	// ################################

	// Job
	/**
	 * Refresh the job list
	 * @param search - The search string to filter the list
	 * @return {Promise<{ response: Response, data: TJob[], entities: Job[] }>} The response, data, and entities
	 */
	const refreshJobList = async (search: string = null): Promise<{ response: Response, data: TJob[], entities: Job[] }> => {
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

		const data = (await response.json()).results as TJob[]
		const entities = data.map(jobItem => new Job(jobItem))

		setJobList(data)

		return { response, data, entities }
	}

	/**
	 * Fetch a single job
	 * @param id - The ID of the job to fetch
	 * @return {Promise<{ response: Response, data: TJob, entity: Job }>} The response, data, and entity
	 */
	const fetchJob = async (id: string): Promise<{ response: Response, data: TJob, entity: Job }> => {
		if (!id) {
			throw new MissingParameterError('id')
		}

		const endpoint = `${apiEndpoint}/${id}`

		const response = await fetch(endpoint, {
			method: 'GET',
		})

		const data = await response.json() as TJob
		const entity = new Job(data)

		setJobItem(data)

		return { response, data, entity }
	}

	/**
	 * Delete a job
	 * @param id - The ID of the job to delete
	 * @return {Promise<{ response: Response }>} The response
	 */
	const deleteJob = async (id: string): Promise<{ response: Response }> => {
		if (!id) {
			throw new MissingParameterError('id')
		}

		console.info('Deleting job...')

		const endpoint = `${apiEndpoint}/${id}`

		const response = await fetch(endpoint, {
			method: 'DELETE',
		})

		response.ok && setJobItem(null)
		refreshJobList()

		return { response }
	}

	/**
	 * Save a job
	 * @param jobItem - The job item to save
	 * @return {Promise<{ response: Response, data: TJob, entity: Job }>} The response, data, and entity
	 */
	const saveJob = async (jobItem: Job): Promise<{ response: Response, data: TJob, entity: Job }> => {
		if (!jobItem) {
			throw new MissingParameterError('jobItem')
		}
		if (!(jobItem instanceof Job)) {
			throw new Error('jobItem is not an instance of Job')
		}

		// DISABLED UNTIL TIME CAN BE SPENT TO DO VALIDATION PROPERLY
		// verify data with Zod
		// const validationResult = jobItem.validate()
		// if (!validationResult.success) {
		//  console.error(validationResult.error)
		//  console.info(jobItem)
		//  throw new ValidationError(validationResult.error)
		// }

		// delete "updated"
		const clonedJob = jobItem.cloneRaw()
		delete clonedJob.updated
		jobItem = new Job(clonedJob)

		console.info('Saving job...')

		const isNewJob = !jobItem.id
		const endpoint = isNewJob
			? apiEndpoint
			: `${apiEndpoint}/${jobItem.id}`
		const method = isNewJob ? 'POST' : 'PUT'

		const response = await fetch(
			endpoint,
			{
				method,
				headers: {
					'Content-Type': 'application/json',
				},
				body: JSON.stringify(jobItem),
			},
		)

		const data = await response.json() as TJob
		const entity = new Job(data)

		setJobItem(data)
		refreshJobList()

		return { response, data, entity }
	}

	// job test
	/**
	 * Tests the current job
	 * @param {string} id - The ID of the job to test
	 * @return {Promise<{ response: Response, data: object }>} The response and data
	 */
	const testJob = async (id: string): Promise<{ response: Response, data: object }> => {
		if (!id) {
			throw new MissingParameterError('id')
		}

		console.info('Testing event...')

		const endpoint = `/index.php/apps/openconnector/api/jobs/run/${id}?test=true`

		const response = await fetch(endpoint, {
			method: 'POST',
			headers: { 'Content-Type': 'application/json' },
			body: JSON.stringify([]),
		})

		const data = await response.json()

		setJobTest(data)
		refreshJobLogs(id)

		return { response, data }
	}

	// job run
	/**
	 * Runs a job by ID
	 * @param {string} id - Job ID to run
	 * @return {Promise<{ response: Response, data: object }>} The response and data
	 */
	const runJob = async (id: string): Promise<{ response: Response, data: object }> => {
		if (!id) {
			throw new MissingParameterError('id')
		}

		console.info('Running job...')
		const endpoint = `/index.php/apps/openconnector/api/jobs/run/${id}`

		const response = await fetch(endpoint, {
			method: 'POST',
			headers: { 'Content-Type': 'application/json' },
			body: JSON.stringify([]),
		})

		const data = await response.json()
		setJobRun(data)
		refreshJobLogs(id)

		return { response, data }
	}

	// job logs
	/**
	 * Refreshes job logs for the current job
	 * @param id - The ID of the job to refresh logs for
	 * @param filters - Optional filters object
	 * @return {Promise<{ response: Response, data: object }>} The response and data
	 */
	const refreshJobLogs = async (id: string = null, filters: object = {}): Promise<{ response: Response, data: object }> => {
		// Set loading state
		const logStore = useLogStore()
		logStore.setLogsLoading(true)

		try {
			// Convert all filter values to strings for URLSearchParams
			const stringFilters = Object.fromEntries(
				Object.entries(filters).map(([k, v]) => [k, v != null ? String(v) : '']),
			)
			const params = new URLSearchParams(stringFilters)
			if (id) {
				params.set('job_id', id)
			}
			const endpoint = `/index.php/apps/openconnector/api/jobs/logs${params.toString() ? '?' + params.toString() : ''}`
			const response = await fetch(endpoint)
			const data = await response.json()
			setJobLogs(data)
			return { response, data }
		} catch (error) {
			console.error('Error refreshing job logs:', error)
			throw error
		} finally {
			// Reset loading state
			logStore.setLogsLoading(false)
		}
	}

	// Export a job
	const exportJob = (id: string) => {
		if (!id) {
			throw new MissingParameterError('id')
		}

		importExportStore.exportFile(id, 'job')
			.then(({ download }) => {
				download()
			})
			.catch((err) => {
				console.error('Error exporting job:', err)
				throw err
			})
	}

	return {
		// state
		jobItem,
		jobTest,
		jobRun,
		jobList,
		jobLog,
		jobLogs,
		jobArgumentKey,
		viewMode,

		// setters and getters
		setJobItem,
		getJobItem,
		setJobTest,
		getJobTest,
		setJobRun,
		getJobRun,
		setJobList,
		getJobList,
		setJobLog,
		getJobLog,
		setJobLogs,
		getJobLogs,
		setJobArgumentKey,
		getJobArgumentKey,
		setViewMode,
		getViewMode,

		// actions
		refreshJobList,
		fetchJob,
		deleteJob,
		saveJob,
		testJob,
		runJob,
		refreshJobLogs,
		exportJob,
	}
})
