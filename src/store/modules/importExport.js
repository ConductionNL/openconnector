import { defineStore } from 'pinia'

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

		},
	},
)
