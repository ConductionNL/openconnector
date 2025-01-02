import { defineStore } from 'pinia'
import { Synchronization } from '../../entities/index.js'

export const useSynchronizationStore = defineStore('synchronization', {
	state: () => ({
		synchronizationItem: false,
		synchronizationList: [],
		synchronizationContracts: [],
		synchronizationTest: null,
		synchronizationRun: null,
		synchronizationLogs: [],
		synchronizationSourceConfigKey: null,
		synchronizationTargetConfigKey: null,
	}),
	actions: {
		setSynchronizationItem(synchronizationItem) {
			this.synchronizationItem = synchronizationItem && new Synchronization(synchronizationItem)
			console.info('Active synchronization item set to ' + synchronizationItem)
		},
		setSynchronizationList(synchronizationList) {
			this.synchronizationList = synchronizationList.map(
				(synchronizationItem) => new Synchronization(synchronizationItem),
			)
			console.info('Synchronization list set to ' + synchronizationList.length + ' items')
		},
		setSynchronizationContracts(synchronizationContracts) {
			this.synchronizationContracts = synchronizationContracts
			console.info('Synchronization contracts set to ' + synchronizationContracts?.length + ' items')
		},
		setSynchronizationLogs(synchronizationLogs) {
			this.synchronizationLogs = synchronizationLogs

			console.info('Synchronization logs set to ' + synchronizationLogs?.length + ' items')
		},
		setSynchronizationSourceConfigKey(key) {
			this.synchronizationSourceConfigKey = key
			console.info('Synchronization source config key set to ' + key)
		},
		setSynchronizationTargetConfigKey(key) {
			this.synchronizationTargetConfigKey = key
			console.info('Synchronization target config key set to ' + key)
		},

		/* istanbul ignore next */ // ignore this for Jest until moved into a service
		async refreshSynchronizationList(search = null) {
			// @todo this might belong in a service?
			let endpoint = '/index.php/apps/openconnector/api/synchronizations'
			if (search !== null && search !== '') {
				endpoint = endpoint + '?_search=' + search
			}
			return fetch(endpoint, {
				method: 'GET',
			})
				.then(
					(response) => {
						response.json().then(
							(data) => {
								this.setSynchronizationList(data.results)
							},
						)
					},
				)
				.catch(
					(err) => {
						console.error(err)
					},
				)
		},
		/* istanbul ignore next */ // ignore this for Jest until moved into a service
		async refreshSynchronizationContracts(search = null) {
			// @todo this might belong in a service?
			let endpoint = `/index.php/apps/openconnector/api/synchronizations-contracts/${this.synchronizationItem.id}`
			if (search !== null && search !== '') {
				endpoint = endpoint + '?_search=' + search
			}
			return fetch(endpoint, {
				method: 'GET',
			})
				.then(
					(response) => {
						response.json().then(
							(data) => {
								this.setSynchronizationContracts(data)
							},
						)
					},
				)
				.catch(
					(err) => {
						console.error(err)
					},
				)
		},
		/* istanbul ignore next */ // ignore this for Jest until moved into a service
		async refreshSynchronizationLogs(search = null) {
			// @todo this might belong in a service?
			let endpoint = `/index.php/apps/openconnector/api/synchronizations-logs/${this.synchronizationItem.id}`
			if (search !== null && search !== '') {
				endpoint = endpoint + '?_search=' + search
			}
			return fetch(endpoint, {
				method: 'GET',
			})
				.then(
					(response) => {
						response.json().then(
							(data) => {
								this.setSynchronizationLogs(data)
							},
						)
					},
				)
				.catch(
					(err) => {
						console.error(err)
					},
				)
		},
		// New function to get a single synchronization
		async getSynchronization(id) {
			const endpoint = `/index.php/apps/openconnector/api/synchronizations/${id}`
			try {
				const response = await fetch(endpoint, {
					method: 'GET',
				})
				const data = await response.json()
				this.setSynchronizationItem(data)
				return data
			} catch (err) {
				console.error(err)
				throw err
			}
		},
		// Delete a synchronization
		deleteSynchronization() {
			if (!this.synchronizationItem || !this.synchronizationItem.id) {
				throw new Error('No synchronization item to delete')
			}

			console.info('Deleting synchronization...')

			const endpoint = `/index.php/apps/openconnector/api/synchronizations/${this.synchronizationItem.id}`

			return fetch(endpoint, {
				method: 'DELETE',
			})
				.then((response) => {
					this.refreshSynchronizationList()
				})
				.catch((err) => {
					console.error('Error deleting synchronization:', err)
					throw err
				})
		},
		// Create or save a synchronization from store
		async saveSynchronization(synchronizationItem) {
			if (!synchronizationItem) {
				throw new Error('No synchronization item to save')
			}

			console.info('Saving synchronization...')

			const isNewSynchronization = !synchronizationItem?.id
			const endpoint = isNewSynchronization
				? '/index.php/apps/openconnector/api/synchronizations'
				: `/index.php/apps/openconnector/api/synchronizations/${synchronizationItem.id}`
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

			console.info('Synchronization saved')

			const data = await response.json()
			const entity = new Synchronization(data)

			this.setSynchronizationItem(entity)
			this.refreshSynchronizationList()

			return { response, data, entity }
		},
		// Test a synchronization
		async testSynchronization() {
			if (!this.synchronizationItem) {
				throw new Error('No synchronization item to test')
			}

			console.info('Testing synchronization...')

			const endpoint = `/index.php/apps/openconnector/api/synchronizations-test/${this.synchronizationItem.id}`

			const response = await fetch(endpoint, {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json',
				},
			})

			const data = await response.json()
			this.synchronizationTest = data

			console.info('Synchronization tested')
			this.refreshSynchronizationLogs()

			return { response, data }
		},
		// Test a synchronization
		async runSynchronization(id) {
			if (!id) {
				throw new Error('No synchronization item to run')
			}

			console.info('Testing synchronization...')

			const endpoint = `/index.php/apps/openconnector/api/synchronizations-run/${id}`

			const response = await fetch(endpoint, {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json',
				},
			})

			const data = await response.json()
			this.synchronizationRun = data

			console.info('Synchronization run')
			this.refreshSynchronizationLogs()

			return { response, data }
		},
	},
})
