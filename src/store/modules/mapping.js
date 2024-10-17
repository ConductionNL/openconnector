/* eslint-disable no-console */
import { defineStore } from 'pinia'
import { Mapping } from '../../entities/index.js'

export const useMappingStore = defineStore(
	'mapping', {
		state: () => ({
			mappingItem: false,
			mappingList: [],
			mappingMappingKey: null,
			mappingCastKey: null,
		}),
		actions: {
			setMappingItem(mappingItem) {
				this.mappingItem = mappingItem && new Mapping(mappingItem)
				console.log('Active mapping item set to ' + mappingItem)
			},
			setMappingList(mappingList) {
				this.mappingList = mappingList.map(
					(mappingItem) => new Mapping(mappingItem),
				)
				console.log('Mapping list set to ' + mappingList.length + ' items')
			},
			setMappingMappingKey(mappingMappingKey) {
				this.mappingMappingKey = mappingMappingKey
				console.log('Active mapping mapping key set to ' + mappingMappingKey)
			},
			setMappingCastKey(mappingCastKey) {
				this.mappingCastKey = mappingCastKey
				console.log('Active mapping cast key set to ' + mappingCastKey)
			},
			/* istanbul ignore next */ // ignore this for Jest until moved into a service
			async refreshMappingList(search = null) {
				// @todo this might belong in a service?
				let endpoint = '/index.php/apps/openconnector/api/mappings'
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
									this.setMappingList(data.results)
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
			// New function to get a single mapping
			async getMapping(id) {
				const endpoint = `/index.php/apps/openconnector/api/mappings/${id}`
				try {
					const response = await fetch(endpoint, {
						method: 'GET',
					})
					const data = await response.json()
					this.setMappingItem(data)
					return data
				} catch (err) {
					console.error(err)
					throw err
				}
			},
			// Delete a mapping
			deleteMapping() {
				if (!this.mappingItem || !this.mappingItem.id) {
					throw new Error('No mapping item to delete')
				}

				console.log('Deleting mapping...')

				const endpoint = `/index.php/apps/openconnector/api/mappings/${this.mappingItem.id}`

				return fetch(endpoint, {
					method: 'DELETE',
				})
					.then((response) => {
						this.refreshMappingList()
					})
					.catch((err) => {
						console.error('Error deleting mapping:', err)
						throw err
					})
			},
			// Create or save a mapping from store
			async saveMapping(mappingItem) {
				if (!mappingItem) {
					throw new Error('No mapping item to save')
				}

				console.log('Saving mapping...')

				const isNewMapping = !mappingItem.id
				const endpoint = isNewMapping
					? '/index.php/apps/openconnector/api/mappings'
					: `/index.php/apps/openconnector/api/mappings/${mappingItem.id}`
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

				const data = await response.json()
				const entity = new Mapping(data)

				this.setMappingItem(entity)
				this.refreshMappingList()

				return { response, data, entity }
			},
		},
	},
)
