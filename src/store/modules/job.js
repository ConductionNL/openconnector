/* eslint-disable no-console */
import { defineStore } from 'pinia'
import { Job } from '../../entities/index.js'

export const useJobStore = defineStore(
	'job', {
		state: () => ({
			jobItem: false,
			jobList: [],
		}),
		actions: {
			setJobItem(jobItem) {
				this.jobItem = jobItem && new Job(jobItem)
				console.log('Active job item set to ' + jobItem)
			},
			setJobList(jobList) {
				this.jobList = jobList.map(
					(jobItem) => new Job(jobItem),
				)
				console.log('Job list set to ' + jobList.length + ' items')
			},
			/* istanbul ignore next */ // ignore this for Jest until moved into a service
			async refreshJobList(search = null) {
				// @todo this might belong in a service?
				let endpoint = '/index.php/apps/openconnector/api/jobs'
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
									this.setJobList(data.results)
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
			// New function to get a single job
			async getJob(id) {
				const endpoint = `/index.php/apps/openconnector/api/jobs/${id}`
				try {
					const response = await fetch(endpoint, {
						method: 'GET',
					})
					const data = await response.json()
					this.setJobItem(data)
					return data
				} catch (err) {
					console.error(err)
					throw err
				}
			},
			// Delete a job
			deleteJob() {
				if (!this.jobItem || !this.jobItem.id) {
					throw new Error('No job item to delete')
				}

				console.log('Deleting job...')

				const endpoint = `/index.php/apps/openconnector/api/jobs/${this.jobItem.id}`

				return fetch(endpoint, {
					method: 'DELETE',
				})
					.then((response) => {
						this.refreshJobList()
					})
					.catch((err) => {
						console.error('Error deleting job:', err)
						throw err
					})
			},
			// Create or save a job from store
			saveJob() {
				if (!this.jobItem) {
					throw new Error('No job item to save')
				}

				console.log('Saving job...')

				const isNewJob = !this.jobItem.id
				const endpoint = isNewJob
					? '/index.php/apps/openconnector/api/jobs'
					: `/index.php/apps/openconnector/api/jobs/${this.jobItem.id}`
				const method = isNewJob ? 'POST' : 'PUT'

				// Create a copy of the job item and remove empty properties
				const jobToSave = { ...this.jobItem }
				Object.keys(jobToSave).forEach(key => {
					if (jobToSave[key] === '' || (Array.isArray(jobToSave[key]) && jobToSave[key].length === 0)) {
						delete jobToSave[key]
					}
				})

				return fetch(
					endpoint,
					{
						method,
						headers: {
							'Content-Type': 'application/json',
						},
						body: JSON.stringify(jobToSave),
					},
				)
					.then((response) => response.json())
					.then((data) => {
						this.setJobItem(data)
						console.log('Job saved')
						// Refresh the job list
						return this.refreshJobList()
					})
					.catch((err) => {
						console.error('Error saving job:', err)
						throw err
					})
			},
		},
	},
)
