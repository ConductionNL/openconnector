<script setup>
import { contractStore, synchronizationStore, navigationStore } from '../../store/store.js'
</script>

<template>
	<NcAppContent>
		<div class="container">
			<!-- Header -->
			<div class="header">
				<h1>{{ t('openconnector', 'Synchronization Contracts') }}</h1>
				<p>{{ t('openconnector', 'Manage and monitor synchronization contracts') }}</p>
			</div>

			<!-- Actions Bar -->
			<div v-if="selectedItems.length > 0" class="selection-header">
				<h3 class="selection-title">
					{{ t('openconnector', '{count} contracts selected', { count: selectedItems.length }) }}
				</h3>
			</div>

			<div class="actions-bar">
				<div class="actions">
					<NcButton
						v-if="selectedItems.length > 0"
						type="error"
						@click="bulkDelete">
						<template #icon>
							<Delete :size="20" />
						</template>
						{{ t('openconnector', 'Delete Selected') }}
					</NcButton>
					<NcButton @click="refreshItems">
						<template #icon>
							<Refresh :size="20" />
						</template>
						{{ t('openconnector', 'Refresh') }}
					</NcButton>
				</div>
			</div>

			<!-- Contracts Table -->
			<div v-if="contractStore.contractsLoading" class="loading">
				<NcLoadingIcon :size="64" />
				<p>{{ t('openconnector', 'Loading contracts...') }}</p>
			</div>

			<NcEmptyContent v-else-if="!filteredItems.length"
				:name="t('openconnector', 'No contracts found')"
				:description="t('openconnector', 'There are no contracts matching your current filters.')">
				<template #icon>
					<FileDocumentOutline />
				</template>
			</NcEmptyContent>

			<div v-else class="table-container">
				<table class="items-table">
					<thead>
						<tr>
							<th class="checkbox-column">
								<NcCheckboxRadioSwitch
									:checked="allSelected"
									:indeterminate="someSelected"
									@update:checked="toggleSelectAll" />
							</th>
							<th>{{ t('openconnector', 'Contract') }}</th>
							<th>{{ t('openconnector', 'Synchronization') }}</th>
							<th>{{ t('openconnector', 'Sync Status') }}</th>
							<th>{{ t('openconnector', 'Last Synced') }}</th>
							<th>{{ t('openconnector', 'Last Action') }}</th>
							<th>{{ t('openconnector', 'Actions') }}</th>
						</tr>
					</thead>
					<tbody>
						<tr v-for="item in paginatedItems"
							:key="item.id"
							class="item-row"
							:class="{ selected: selectedItems.includes(item.id) }">
							<td class="checkbox-column">
								<NcCheckboxRadioSwitch
									:checked="selectedItems.includes(item.id)"
									@update:checked="(checked) => toggleItemSelection(item.id, checked)" />
							</td>
							<td class="title-column">
								<div class="title-content">
									<strong>{{ getContractName(item) }}</strong>
									<span v-if="item.uuid" class="description">{{ item.uuid }}</span>
								</div>
							</td>
							<td>{{ getSynchronizationName(item.synchronizationId) }}</td>
							<td>
								<span :class="getSyncStatusType(item.getSyncStatus ? item.getSyncStatus() : 'unsynced')">
									{{ getSyncStatusLabel(item.getSyncStatus ? item.getSyncStatus() : 'unsynced') }}
								</span>
							</td>
							<td>
								<NcDateTime v-if="item.getLastSyncDate && item.getLastSyncDate()"
									:timestamp="new Date(item.getLastSyncDate())"
									:ignore-seconds="true" />
								<span v-else>{{ t('openconnector', 'Never') }}</span>
							</td>
							<td>
								<span class="action-badge">{{ getLastActionLabel(item.getLastAction ? item.getLastAction() : 'none') }}</span>
							</td>
							<td class="actions-column">
								<NcActions>
									<NcActionButton close-after-click @click="enforceContract(item)">
										<template #icon>
											<PlayCircle :size="20" />
										</template>
										{{ t('openconnector', 'Enforce Contract') }}
									</NcActionButton>
									<NcActionButton close-after-click @click="viewLogs(item)">
										<template #icon>
											<TextBoxOutline :size="20" />
										</template>
										{{ t('openconnector', 'View Logs') }}
									</NcActionButton>
									<NcActionButton close-after-click @click="deleteContract(item)">
										<template #icon>
											<Delete :size="20" />
										</template>
										{{ t('openconnector', 'Delete') }}
									</NcActionButton>
								</NcActions>
							</td>
						</tr>
					</tbody>
				</table>
			</div>

			<!-- Pagination -->
			<div v-if="totalPages > 1" class="pagination">
				<NcButton
					:disabled="currentPage === 1"
					@click="changePage(1)">
					{{ t('openconnector', 'First') }}
				</NcButton>
				<NcButton
					:disabled="currentPage === 1"
					@click="changePage(currentPage - 1)">
					{{ t('openconnector', 'Previous') }}
				</NcButton>
				<span class="page-info">
					{{ t('openconnector', 'Page {current} of {total}', { current: currentPage, total: totalPages }) }}
				</span>
				<NcButton
					:disabled="currentPage === totalPages"
					@click="changePage(currentPage + 1)">
					{{ t('openconnector', 'Next') }}
				</NcButton>
				<NcButton
					:disabled="currentPage === totalPages"
					@click="changePage(totalPages)">
					{{ t('openconnector', 'Last') }}
				</NcButton>
			</div>
		</div>
	</NcAppContent>
</template>

<script>
import {
	NcAppContent,
	NcEmptyContent,
	NcButton,
	NcLoadingIcon,
	NcCheckboxRadioSwitch,
	NcActions,
	NcActionButton,
	NcDateTime,
} from '@nextcloud/vue'
import FileDocumentOutline from 'vue-material-design-icons/FileDocumentOutline.vue'
import Refresh from 'vue-material-design-icons/Refresh.vue'
import PlayCircle from 'vue-material-design-icons/PlayCircle.vue'
import TextBoxOutline from 'vue-material-design-icons/TextBoxOutline.vue'
import Delete from 'vue-material-design-icons/Delete.vue'

export default {
	name: 'ContractsIndex',
	components: {
		NcAppContent,
		NcEmptyContent,
		NcButton,
		NcLoadingIcon,
		NcCheckboxRadioSwitch,
		NcActions,
		NcActionButton,
		NcDateTime,
		FileDocumentOutline,
		Refresh,
		PlayCircle,
		TextBoxOutline,
		Delete,
	},
	data() {
		return {
			selectedItems: [],
		}
	},
	computed: {
		/**
		 * Get filtered contracts from store
		 * @return {Array} Array of filtered contracts
		 */
		filteredItems() {
			return contractStore.contractsList || []
		},
		/**
		 * Get paginated contracts from store
		 * @return {Array} Array of paginated contracts
		 */
		paginatedItems() {
			return this.filteredItems
		},
		/**
		 * Get total pages from store
		 * @return {number} Total number of pages
		 */
		totalPages() {
			return contractStore.contractsPagination.pages || 1
		},
		/**
		 * Get current page from store
		 * @return {number} Current page number
		 */
		currentPage() {
			return contractStore.contractsPagination.page || 1
		},
		/**
		 * Check if all items are selected
		 * @return {boolean} Whether all items are selected
		 */
		allSelected() {
			return this.paginatedItems.length > 0 && this.paginatedItems.every(item => this.selectedItems.includes(item.id))
		},
		/**
		 * Check if some items are selected
		 * @return {boolean} Whether some items are selected
		 */
		someSelected() {
			return this.selectedItems.length > 0 && !this.allSelected
		},
	},
	watch: {
		selectedItems() {
			this.updateCounts()
		},
		filteredItems() {
			this.updateCounts()
		},
	},
	async mounted() {
		// Load initial data
		await this.loadItems()

		// Update counts
		this.updateCounts()

		// Listen for filter changes from sidebar
		this.$root.$on('contracts-filters-changed', this.handleFiltersChanged)
		this.$root.$on('contracts-bulk-delete', this.bulkDelete)
		this.$root.$on('contracts-export-filtered', this.exportFiltered)
	},
	beforeDestroy() {
		this.$root.$off('contracts-filters-changed')
		this.$root.$off('contracts-bulk-delete')
		this.$root.$off('contracts-export-filtered')
	},
	methods: {
		/**
		 * Load contracts from store
		 * @return {Promise<void>}
		 */
		async loadItems() {
			try {
				await contractStore.fetchContracts()

				// Load synchronization data if not already loaded
				if (!synchronizationStore.synchronizationList.length) {
					await synchronizationStore.refreshSynchronizationList()
				}
			} catch (error) {
				console.error('Error loading contracts:', error)
			}
		},
		/**
		 * Handle filter changes from sidebar
		 * @param {object} filters - Filter object from sidebar
		 * @return {void}
		 */
		async handleFiltersChanged(filters) {
			contractStore.setContractsFilters(filters)

			// Reset pagination and fetch new data
			try {
				await contractStore.fetchContracts({
					page: 1,
					filters,
				})

				// Clear selection when filters change
				this.selectedItems = []
			} catch (error) {
				console.error('Error applying filters:', error)
			}
		},
		/**
		 * Get contract name
		 * @param {object} contract - The contract object
		 * @return {string} The contract name
		 */
		getContractName(contract) {
			return contract.getDisplayName ? contract.getDisplayName() : `Contract ${contract.id}`
		},
		/**
		 * Get synchronization name by ID
		 * @param {string|number} synchronizationId - The synchronization ID
		 * @return {string} The synchronization name
		 */
		getSynchronizationName(synchronizationId) {
			if (!synchronizationId) return t('openconnector', 'Unknown Synchronization')

			const synchronization = synchronizationStore.synchronizationList.find(s => s.id === parseInt(synchronizationId))
			return synchronization?.name || `Synchronization ${synchronizationId}`
		},
		/**
		 * Get sync status type
		 * @param {string} status - Sync status
		 * @return {string} Sync status type
		 */
		getSyncStatusType(status) {
			switch (status) {
			case 'synced':
				return 'success'
			case 'stale':
				return 'warning'
			case 'unsynced':
				return 'secondary'
			case 'error':
				return 'error'
			default:
				return 'secondary'
			}
		},
		/**
		 * Get sync status label
		 * @param {string} status - Sync status
		 * @return {string} Sync status label
		 */
		getSyncStatusLabel(status) {
			switch (status) {
			case 'synced':
				return t('openconnector', 'Synced')
			case 'stale':
				return t('openconnector', 'Stale')
			case 'unsynced':
				return t('openconnector', 'Unsynced')
			case 'error':
				return t('openconnector', 'Error')
			default:
				return t('openconnector', 'Unknown')
			}
		},
		/**
		 * Get last action label
		 * @param {string} action - Last action
		 * @return {string} Last action label
		 */
		getLastActionLabel(action) {
			switch (action) {
			case 'create':
			case 'created':
				return t('openconnector', 'Created')
			case 'update':
			case 'updated':
				return t('openconnector', 'Updated')
			case 'delete':
			case 'deleted':
				return t('openconnector', 'Deleted')
			case 'insert':
				return t('openconnector', 'Inserted')
			default:
				return t('openconnector', 'None')
			}
		},
		/**
		 * Toggle selection for all items on current page
		 * @param {boolean} checked - Whether to select or deselect all
		 * @return {void}
		 */
		toggleSelectAll(checked) {
			if (checked) {
				this.paginatedItems.forEach(item => {
					if (!this.selectedItems.includes(item.id)) {
						this.selectedItems.push(item.id)
					}
				})
			} else {
				this.paginatedItems.forEach(item => {
					const index = this.selectedItems.indexOf(item.id)
					if (index > -1) {
						this.selectedItems.splice(index, 1)
					}
				})
			}
		},
		/**
		 * Toggle selection for individual item
		 * @param {string} itemId - ID of the item to toggle
		 * @param {boolean} checked - Whether to select or deselect
		 * @return {void}
		 */
		toggleItemSelection(itemId, checked) {
			if (checked) {
				if (!this.selectedItems.includes(itemId)) {
					this.selectedItems.push(itemId)
				}
			} else {
				const index = this.selectedItems.indexOf(itemId)
				if (index > -1) {
					this.selectedItems.splice(index, 1)
				}
			}
		},
		/**
		 * Bulk delete selected contracts
		 * @return {Promise<void>}
		 */
		async bulkDelete() {
			if (this.selectedItems.length === 0) return

			try {
				await contractStore.deleteMultiple(this.selectedItems)
				this.selectedItems = []
				// Refresh the list
				await this.loadItems()
			} catch (error) {
				console.error('Error deleting contracts:', error)
			}
		},
		/**
		 * Enforce contract (equivalent to executing/running it)
		 * @param {object} contract - Contract to enforce
		 * @return {Promise<void>}
		 */
		async enforceContract(contract) {
			try {
				await contractStore.enforceContract(contract.id)
				// Refresh the list
				await this.loadItems()
			} catch (error) {
				console.error('Error enforcing contract:', error)
			}
		},
		/**
		 * Delete contract
		 * @param {object} contract - Contract to delete
		 * @return {Promise<void>}
		 */
		async deleteContract(contract) {
			try {
				await contractStore.deleteContract(contract.id)
				// Refresh the list
				await this.loadItems()
			} catch (error) {
				console.error('Error deleting contract:', error)
			}
		},
		/**
		 * View logs for contract
		 * @param {object} contract - Contract to view logs for
		 * @return {void}
		 */
		viewLogs(contract) {
			// Navigate to logs view with contract filter
			navigationStore.setSelected('logs')
			this.$root.$emit('logs-filter-by-contract', contract.id)
		},
		/**
		 * Change page
		 * @param {number} page - The page number to change to
		 * @return {Promise<void>}
		 */
		async changePage(page) {
			try {
				await contractStore.fetchContracts({ page })
				// Clear selection when page changes
				this.selectedItems = []
			} catch (error) {
				// Handle error silently
			}
		},
		/**
		 * Refresh contracts list
		 * @return {Promise<void>}
		 */
		async refreshItems() {
			await this.loadItems()
			this.selectedItems = []
		},
		/**
		 * Export filtered contracts
		 * @return {Promise<void>}
		 */
		async exportFiltered() {
			try {
				await contractStore.exportFiltered()
			} catch (error) {
				console.error('Error exporting contracts:', error)
			}
		},
		/**
		 * Update counts for sidebar
		 * @return {void}
		 */
		updateCounts() {
			this.$root.$emit('contracts-selection-count', this.selectedItems.length)
			this.$root.$emit('contracts-filtered-count', this.filteredItems.length)
		},
	},
}
</script>

<style scoped>
.container {
	padding: 20px;
	max-width: 100%;
}

.header {
	margin-bottom: 30px;
}

.header h1 {
	margin: 0 0 10px 0;
	font-size: 2rem;
	font-weight: 300;
}

.header p {
	color: var(--color-text-maxcontrast);
	margin: 0;
}

.selection-header {
	margin-bottom: 20px;
	padding: 10px;
	background: var(--color-background-hover);
	border-radius: var(--border-radius);
}

.selection-title {
	margin: 0;
	font-weight: 500;
	color: var(--color-text-maxcontrast);
}

.actions-bar {
	display: flex;
	align-items: center;
	margin-bottom: 20px;
	padding: 10px;
	background: var(--color-background-hover);
	border-radius: var(--border-radius);
}

.actions {
	display: flex;
	align-items: center;
	gap: 15px;
	margin-left: auto;
}

.loading {
	text-align: center;
	padding: 50px;
}

.loading p {
	margin-top: 20px;
	color: var(--color-text-maxcontrast);
}

.table-container {
	background: var(--color-main-background);
	border-radius: var(--border-radius);
	overflow: hidden;
	box-shadow: 0 2px 4px var(--color-box-shadow);
}

.items-table {
	width: 100%;
	border-collapse: collapse;
}

.items-table th,
.items-table td {
	padding: 12px;
	text-align: left;
	border-bottom: 1px solid var(--color-border);
}

.items-table th {
	background: var(--color-background-hover);
	font-weight: 500;
	color: var(--color-text-maxcontrast);
}

.checkbox-column {
	width: 50px;
	text-align: center;
}

.title-column {
	min-width: 200px;
	max-width: 250px;
}

.title-content {
	display: flex;
	flex-direction: column;
	gap: 4px;
}

.description {
	font-size: 0.9em;
	color: var(--color-text-maxcontrast);
}

.actions-column {
	width: 120px;
	text-align: center;
}

.item-row:hover {
	background: var(--color-background-hover);
}

.item-row.selected {
	background: var(--color-primary-light);
}

.success-rate {
	display: flex;
	flex-direction: column;
	align-items: center;
	gap: 4px;
}

.rate-bar {
	width: 60px;
	height: 8px;
	background: var(--color-background-dark);
	border-radius: 4px;
	overflow: hidden;
}

.rate-fill {
	height: 100%;
	background: var(--color-success);
	transition: width 0.3s ease;
}

.pagination {
	display: flex;
	justify-content: center;
	align-items: center;
	gap: 20px;
	margin-top: 30px;
	padding: 20px;
}

.page-info {
	color: var(--color-text-maxcontrast);
	font-size: 0.9rem;
}

/* Responsive table adjustments */
@media (max-width: 1200px) {
	.title-column {
		min-width: 150px;
		max-width: 200px;
	}
}

/* Status badge styles */
.success {
	display: inline-block;
	padding: 4px 8px;
	background: var(--color-success);
	color: white;
	border-radius: 12px;
	font-size: 0.75rem;
	font-weight: 500;
}

.secondary {
	display: inline-block;
	padding: 4px 8px;
	background: var(--color-background-dark);
	color: var(--color-text-maxcontrast);
	border-radius: 12px;
	font-size: 0.75rem;
	font-weight: 500;
}

.error {
	display: inline-block;
	padding: 4px 8px;
	background: var(--color-error);
	color: white;
	border-radius: 12px;
	font-size: 0.75rem;
	font-weight: 500;
}

.action-badge {
	display: inline-block;
	padding: 4px 8px;
	background: var(--color-background-dark);
	color: var(--color-text-maxcontrast);
	border-radius: 12px;
	font-size: 0.75rem;
	font-weight: 500;
}
</style>
