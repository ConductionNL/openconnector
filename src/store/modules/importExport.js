import { defineStore } from 'pinia'
import { sourceStore, endpointStore, jobStore, mappingStore, synchronizationStore, ruleStore } from '../../store/store.js'
import axios from 'axios'

export const useImportExportStore = defineStore(
	'importExport', {
		state: () => ({
			exportSource: '',
			exportSourceResults: '',
			exportSourceError: '',
		}),
		actions: {
			setExportSource(exportSource) {
				this.exportSource = exportSource
				console.info('Active exportSource set to ' + exportSource)
			},
			async exportFile(id, type) {
				const apiEndpoint = `/index.php/apps/openconnector/api/export/${type}/${id}`

				if (!id) {
					throw Error('Passed id is falsy')
				}
				const response = await fetch(
					apiEndpoint,
					{
						method: 'GET',
						headers: {
							Accept: 'application/json',
						},
					},
				)
				const filename = response.headers.get('Content-Disposition').split('filename=')[1].replace(/['"]/g, '')

				const blob = await response.blob()

				const download = () => {
					const url = window.URL.createObjectURL(new Blob([blob]))
					const link = document.createElement('a')
					link.href = url

					link.setAttribute('download', `${filename}`)
					document.body.appendChild(link)
					link.click()
				}

				return { response, blob, download }
			},

			importFile(files, reset) {
				if (!files) {
					throw Error('No files to import')
				}
				if (!reset) {
					throw Error('No reset function to call')
				}

				return axios.post('/index.php/apps/openconnector/api/import', {
					file: files.value ? files.value[0] : '',
				}, {
					headers: {
						'Content-Type': 'multipart/form-data',
					},
				})
					.then((response) => {

						console.info('Importing file:', response.data)

						const setItem = () => {
							switch (response.data.object['@type']) {
							case 'source':
								return (
									sourceStore.refreshSourceList().then(() => {
										const source = sourceStore.sourceList.find(source => source.id === response.data.object.id)
										sourceStore.setSourceItem(source)
									})
								)
							case 'endpoint':
								return (
									endpointStore.refreshEndpointList().then(() => {
										const endpoint = endpointStore.endpointList.find(endpoint => endpoint.id === response.data.object.id)
										endpointStore.setEndpointItem(endpoint)
									})
								)
							case 'job':
								return (
									jobStore.refreshJobList().then(() => {
										const job = jobStore.jobList.find(job => job.id === response.data.object.id)
										jobStore.setJobItem(job)
									})
								)
							case 'mapping':
								return (
									mappingStore.refreshMappingList().then(() => {
										const mapping = mappingStore.mappingList.find(mapping => mapping.id === response.data.object.id)
										mappingStore.setMappingItem(mapping)
									})
								)
							case 'rule':
								return (
									ruleStore.refreshRuleList().then(() => {
										const rule = ruleStore.ruleList.find(rule => rule.id === response.data.object.id)
										ruleStore.setRuleItem(rule)
									})
								)
							case 'synchronization':
								return (
									synchronizationStore.refreshSynchronizationList().then(() => {
										const synchronization = synchronizationStore.synchronizationList.find(synchronization => synchronization.id === response.data.object.id)
										synchronizationStore.setSynchronizationItem(synchronization)
									})
								)
							}
						}
						return setItem()
					// Wait for the user to read the feedback then close the model
					})
					.catch((err) => {
						console.error('Error importing file:', err)
						throw err
					})

			},
		},
	},
)
