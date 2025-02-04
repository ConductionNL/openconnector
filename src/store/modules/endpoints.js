import { defineStore } from 'pinia'
import { Endpoint } from '../../entities/index.js'
import { MissingParameterError, ValidationError } from '../../services/errors/index.js'
import { importExportStore } from '../../store/store.js'
import _ from 'lodash'

export const useEndpointStore = defineStore('endpoint', {
	state: () => ({
		endpointItem: false,
		endpointList: [],
	}),
	actions: {
		setEndpointItem(endpointItem) {
			this.endpointItem = endpointItem && new Endpoint(endpointItem)
			console.info('Active endpoint item set to ' + endpointItem)
		},
		setEndpointList(endpointList) {
			this.endpointList = endpointList.map(
				(endpointItem) => new Endpoint(endpointItem),
			)
			console.info('Endpoint list set to ' + endpointList.length + ' items')
		},
		/* istanbul ignore next */ // ignore this for Jest until moved into a service
		async refreshEndpointList(search = null) {
			// @todo this might belong in a service?
			let endpoint = '/index.php/apps/openconnector/api/endpoints'
			if (search !== null && search !== '') {
				endpoint = endpoint + '?_search=' + search
			}

			const response = await fetch(endpoint, {
				method: 'GET',
			})

			const data = (await response.json()).results
			const entities = data.map(endpointItem => new Endpoint(endpointItem))

			this.setEndpointList(data)

			return { response, data, entities }
		},
		// New function to get a single endpoint
		async getEndpoint(id) {
			const endpoint = `/index.php/apps/openconnector/api/endpoints/${id}`

			const response = await fetch(endpoint, {
				method: 'GET',
			})

			const data = await response.json()
			const entity = new Endpoint(data)

			this.setEndpointItem(data)

			return { response, data, entity }
		},
		// Delete an endpoint
		async deleteEndpoint(endpointItem) {
			if (!endpointItem) {
				throw new MissingParameterError('endpointItem')
			}

			console.info('Deleting endpoint...')

			const endpoint = `/index.php/apps/openconnector/api/endpoints/${endpointItem.id}`

			const response = await fetch(endpoint, {
				method: 'DELETE',
			})

			response.ok && this.setEndpointItem(null)
			this.refreshEndpointList()

			return { response }
		},
		// Create or save an endpoint from store
		async saveEndpoint(endpointItem) {
			if (!endpointItem) {
				throw new MissingParameterError('endpointItem')
			}

			// convert to an entity
			endpointItem = new Endpoint(endpointItem)

			// verify data with Zod
			const validationResult = endpointItem.validate()
			if (!validationResult.success) {
				console.error(validationResult.error)
				throw new ValidationError(validationResult.error)
			}

			console.info('Saving endpoint...')

			const isNewEndpoint = !endpointItem.id
			const endpoint = isNewEndpoint
				? '/index.php/apps/openconnector/api/endpoints'
				: `/index.php/apps/openconnector/api/endpoints/${endpointItem.id}`
			const method = isNewEndpoint ? 'POST' : 'PUT'

			// Create a copy of the endpoint item and remove empty properties
			const endpointToSave = _.cloneDeep(endpointItem)

			// Remove the version field
			delete endpointToSave.version

			const response = await fetch(
				endpoint,
				{
					method,
					headers: {
						'Content-Type': 'application/json',
					},
					body: JSON.stringify(endpointToSave),
				},
			)

			const data = await response.json()
			const entity = new Endpoint(data)

			this.setEndpointItem(data)
			this.refreshEndpointList()

			return { response, data, entity }
		},
		// Export an endpoint
		exportEndpoint(endpointItem) {
			if (!endpointItem) {
				throw new Error('No endpoint item to export')
			}
			importExportStore.exportFile(
				endpointItem.id,
				'endpoint',
			)
				.then(({ download }) => {
					download()
				})
				.catch((err) => {
					console.error('Error exporting endpoint:', err)
					throw err
				})
		},
	},
})
