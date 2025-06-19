<script setup>
import { logStore, contractStore, synchronizationStore, navigationStore } from '../../store/store.js'
</script>

<template>
	<NcAppContent>
		<div class="container">
			<!-- Header -->
			<div class="header">
				<h1>{{ t('openconnector', 'Synchronization Logs') }}</h1>
				<p>{{ t('openconnector', 'Monitor and analyze synchronization execution logs') }}</p>
			</div>

			<!-- Actions Bar -->
			<div v-if="selectedItems.length > 0" class="selection-header">
				<h3 class="selection-title">
					{{ t('openconnector', '{count} logs selected', { count: selectedItems.length }) }}
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
					<NcButton
						:disabled="filteredItems.length === 0"
						@click="exportLogs">
						<template #icon>
							<Download :size="20" />
						</template>
						{{ t('openconnector', 'Export Logs') }}
					</NcButton>
					<NcButton @click="refreshItems">
						<template #icon>
							<Refresh :size="20" />
						</template>
						{{ t('openconnector', 'Refresh') }}
					</NcButton>
				</div>
			</div>

			<!-- Logs Table -->
			<div v-if="logStore.logsLoading" class="loading">
				<NcLoadingIcon :size="64" />
				<p>{{ t('openconnector', 'Loading logs...') }}</p>
			</div>

			<NcEmptyContent v-else-if="!filteredItems.length"
				:name="t('openconnector', 'No logs found')"
				:description="t('openconnector', 'There are no logs matching your current filters.')">
				<template #icon>
					<TextBoxOutline />
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
							<th>{{ t('openconnector', 'Timestamp') }}</th>
							<th>{{ t('openconnector', 'Level') }}</th>
							<th>{{ t('openconnector', 'Contract') }}</th>
							<th>{{ t('openconnector', 'Synchronization') }}</th>
							<th>{{ t('openconnector', 'Message') }}</th>
							<th>{{ t('openconnector', 'Duration') }}</th>
							<th>{{ t('openconnector', 'Actions') }}</th>
						</tr>
					</thead>
					<tbody>
						<tr v-for="item in paginatedItems"
							:key="item.id"
							class="item-row"
							:class="{
								selected: selectedItems.includes(item.id),
								'log-error': item.getLevel && item.getLevel() === 'error',
								'log-warning': item.getLevel && item.getLevel() === 'warning',
								'log-success': item.getLevel && (item.getLevel() === 'success' || item.getLevel() === 'info')
							}">
							<td class="checkbox-column">
								<NcCheckboxRadioSwitch
									:checked="selectedItems.includes(item.id)"
									@update:checked="(checked) => toggleItemSelection(item.id, checked)" />
							</td>
							<td class="timestamp-column">
								<NcDateTime :timestamp="new Date(item.created)" :ignore-seconds="false" />
							</td>
							<td>
								<span :class="'badge-' + getLevelType(item.getLevel ? item.getLevel() : 'unknown')">
									{{ getLevelLabel(item.getLevel ? item.getLevel() : 'unknown') }}
								</span>
							</td>
							<td>{{ getContractName(null) }}</td>
							<td>{{ getSynchronizationName(item.synchronizationId) }}</td>
							<td class="message-column">
								<div class="message-content">
									<span class="message-text">{{ item.message }}</span>
									<NcButton v-if="item.result && item.result.length > 0"
										type="tertiary"
										:aria-expanded="expandedItems.includes(item.id)"
										@click="toggleDetails(item.id)">
										<template #icon>
											<ChevronDown v-if="!expandedItems.includes(item.id)" :size="16" />
											<ChevronUp v-else :size="16" />
										</template>
										{{ expandedItems.includes(item.id) ? t('openconnector', 'Less') : t('openconnector', 'More') }}
									</NcButton>
								</div>
								<div v-if="expandedItems.includes(item.id) && item.result" class="details-content">
									<pre>{{ formatDetails(item.result) }}</pre>
								</div>
							</td>
							<td>
								<span v-if="item.getFormattedDuration">{{ item.getFormattedDuration() }}</span>
								<span v-else-if="item.executionTime">{{ formatDuration(item.executionTime) }}</span>
								<span v-else>-</span>
							</td>
							<td class="actions-column">
								<NcActions>
									<NcActionButton close-after-click @click="viewFullLog(item)">
										<template #icon>
											<OpenInNew :size="20" />
										</template>
										{{ t('openconnector', 'View Full Log') }}
									</NcActionButton>
									<NcActionButton v-if="item.synchronizationId" close-after-click @click="viewSynchronization(item)">
										<template #icon>
											<FileDocumentOutline :size="20" />
										</template>
										{{ t('openconnector', 'View Synchronization') }}
									</NcActionButton>
									<NcActionButton close-after-click @click="deleteLog(item)">
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
import TextBoxOutline from 'vue-material-design-icons/TextBoxOutline.vue'
import Delete from 'vue-material-design-icons/Delete.vue'
import Download from 'vue-material-design-icons/Download.vue'
import Refresh from 'vue-material-design-icons/Refresh.vue'
import OpenInNew from 'vue-material-design-icons/OpenInNew.vue'
import FileDocumentOutline from 'vue-material-design-icons/FileDocumentOutline.vue'
import ChevronDown from 'vue-material-design-icons/ChevronDown.vue'
import ChevronUp from 'vue-material-design-icons/ChevronUp.vue'

export default {
	name: 'LogsIndex',
	components: {
		NcAppContent,
		NcEmptyContent,
		NcButton,
		NcLoadingIcon,
		NcCheckboxRadioSwitch,
		NcActions,
		NcActionButton,
		NcDateTime,
		TextBoxOutline,
		Delete,
		Download,
		Refresh,
		OpenInNew,
		FileDocumentOutline,
		ChevronDown,
		ChevronUp,
	},
	data() {
		return {
			selectedItems: [],
			expandedItems: [],
		}
	},
	computed: {
		/**
		 * Get filtered logs from store
		 * @return {Array} Array of filtered logs
		 */
		filteredItems() {
			return logStore.logsList || []
		},
		/**
		 * Get paginated logs from store
		 * @return {Array} Array of paginated logs
		 */
		paginatedItems() {
			return this.filteredItems
		},
		/**
		 * Get total pages from store
		 * @return {number} Total number of pages
		 */
		totalPages() {
			return logStore.logsPagination.pages || 1
		},
		/**
		 * Get current page from store
		 * @return {number} Current page number
		 */
		currentPage() {
			return logStore.logsPagination.page || 1
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
		this.$root.$on('logs-filters-changed', this.handleFiltersChanged)
		this.$root.$on('logs-bulk-delete', this.bulkDelete)
		this.$root.$on('logs-export-filtered', this.exportLogs)
		this.$root.$on('logs-filter-by-contract', this.filterByContract)
	},
	beforeDestroy() {
		this.$root.$off('logs-filters-changed')
		this.$root.$off('logs-bulk-delete')
		this.$root.$off('logs-export-filtered')
		this.$root.$off('logs-filter-by-contract')
	},
	methods: {
		/**
		 * Load logs from store
		 * @return {Promise<void>}
		 */
		async loadItems() {
			try {
				await logStore.fetchLogs()

				// Load contract and synchronization data if not already loaded
				if (!contractStore.contractsList.length) {
					await contractStore.fetchContracts()
				}
				if (!synchronizationStore.synchronizationList.length) {
					await synchronizationStore.refreshSynchronizationList()
				}
			} catch (error) {
				console.error('Error loading logs:', error)
			}
		},
		/**
		 * Handle filter changes from sidebar
		 * @param {object} filters - Filter object from sidebar
		 * @return {void}
		 */
		async handleFiltersChanged(filters) {
			logStore.setLogsFilters(filters)

			// Reset pagination and fetch new data
			try {
				await logStore.fetchLogs({
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
		 * Filter logs by contract ID
		 * @param {string|number} contractId - Contract ID to filter by
		 * @return {void}
		 */
		async filterByContract(contractId) {
			const filters = { contract: contractId }
			await this.handleFiltersChanged(filters)
		},
		/**
		 * Get contract name by ID
		 * @param {string|number} contractId - The contract ID
		 * @return {string} The contract name
		 */
		getContractName(contractId) {
			if (!contractId) return t('openconnector', 'System')

			const contract = contractStore.contractsList.find(c => c.id === parseInt(contractId))
			return contract?.name || `Contract ${contractId}`
		},
		/**
		 * Get synchronization name by ID
		 * @param {string|number} synchronizationId - The synchronization ID
		 * @return {string} The synchronization name
		 */
		getSynchronizationName(synchronizationId) {
			if (!synchronizationId) return t('openconnector', 'Unknown')

			const synchronization = synchronizationStore.synchronizationList.find(s => s.id === parseInt(synchronizationId))
			return synchronization?.name || `Synchronization ${synchronizationId}`
		},
		/**
		 * Get level badge type
		 * @param {string} level - Log level
		 * @return {string} Badge type
		 */
		getLevelType(level) {
			switch (level?.toLowerCase()) {
			case 'error':
				return 'error'
			case 'warning':
				return 'warning'
			case 'success':
			case 'info':
				return 'success'
			case 'debug':
				return 'secondary'
			default:
				return 'secondary'
			}
		},
		/**
		 * Get level label
		 * @param {string} level - Log level
		 * @return {string} Level label
		 */
		getLevelLabel(level) {
			switch (level?.toLowerCase()) {
			case 'error':
				return t('openconnector', 'Error')
			case 'warning':
				return t('openconnector', 'Warning')
			case 'success':
				return t('openconnector', 'Success')
			case 'info':
				return t('openconnector', 'Info')
			case 'debug':
				return t('openconnector', 'Debug')
			default:
				return level || t('openconnector', 'Unknown')
			}
		},
		/**
		 * Format duration in human readable format
		 * @param {number} duration - Duration in milliseconds
		 * @return {string} Formatted duration
		 */
		formatDuration(duration) {
			if (duration < 1000) {
				return `${duration}ms`
			} else if (duration < 60000) {
				return `${(duration / 1000).toFixed(2)}s`
			} else {
				const minutes = Math.floor(duration / 60000)
				const seconds = Math.floor((duration % 60000) / 1000)
				return `${minutes}m ${seconds}s`
			}
		},
		/**
		 * Format details for display
		 * @param {object|string} details - Log details
		 * @return {string} Formatted details
		 */
		formatDetails(details) {
			if (typeof details === 'object') {
				return JSON.stringify(details, null, 2)
			}
			return details
		},
		/**
		 * Toggle details expansion for a log entry
		 * @param {string} logId - ID of the log entry
		 * @return {void}
		 */
		toggleDetails(logId) {
			const index = this.expandedItems.indexOf(logId)
			if (index > -1) {
				this.expandedItems.splice(index, 1)
			} else {
				this.expandedItems.push(logId)
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
		 * Bulk delete selected logs
		 * @return {Promise<void>}
		 */
		async bulkDelete() {
			if (this.selectedItems.length === 0) return

			try {
				await logStore.deleteMultiple(this.selectedItems)
				this.selectedItems = []
				// Refresh the list
				await this.loadItems()
			} catch (error) {
				console.error('Error deleting logs:', error)
			}
		},
		/**
		 * Export logs
		 * @return {Promise<void>}
		 */
		async exportLogs() {
			try {
				await logStore.exportLogs()
			} catch (error) {
				console.error('Error exporting logs:', error)
			}
		},
		/**
		 * View full log details
		 * @param {object} log - Log to view
		 * @return {void}
		 */
		viewFullLog(log) {
			// Set transfer data and open dialog or navigate to detail view
			navigationStore.setTransferData(log)
			navigationStore.setDialog('viewLogDetails')
		},
		/**
		 * View synchronization associated with log
		 * @param {object} log - Log entry
		 * @return {void}
		 */
		viewSynchronization(log) {
			// Navigate to synchronization view with synchronization filter
			navigationStore.setSelected('synchronizations')
			// Filter by specific synchronization
			this.$root.$emit('synchronizations-filter-by-id', log.synchronizationId)
		},
		/**
		 * Delete individual log
		 * @param {object} log - Log to delete
		 * @return {Promise<void>}
		 */
		async deleteLog(log) {
			try {
				await logStore.deleteLog(log.id)
				// Refresh the list
				await this.loadItems()
			} catch (error) {
				console.error('Error deleting log:', error)
			}
		},
		/**
		 * Change page
		 * @param {number} page - The page number to change to
		 * @return {Promise<void>}
		 */
		async changePage(page) {
			try {
				await logStore.fetchLogs({ page })
				// Clear selection when page changes
				this.selectedItems = []
			} catch (error) {
				// Handle error silently
			}
		},
		/**
		 * Refresh logs list
		 * @return {Promise<void>}
		 */
		async refreshItems() {
			await this.loadItems()
			this.selectedItems = []
			this.expandedItems = []
		},
		/**
		 * Update counts for sidebar
		 * @return {void}
		 */
		updateCounts() {
			this.$root.$emit('logs-selection-count', this.selectedItems.length)
			this.$root.$emit('logs-filtered-count', this.filteredItems.length)
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

.timestamp-column {
	min-width: 160px;
}

.message-column {
	min-width: 300px;
	max-width: 400px;
}

.message-content {
	display: flex;
	flex-direction: column;
	gap: 8px;
}

.message-text {
	word-break: break-word;
}

.details-content {
	margin-top: 8px;
	padding: 8px;
	background: var(--color-background-dark);
	border-radius: var(--border-radius);
	font-size: 0.85em;
}

.details-content pre {
	margin: 0;
	white-space: pre-wrap;
	word-break: break-word;
	max-height: 200px;
	overflow-y: auto;
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

.item-row.log-error {
	border-left: 4px solid var(--color-error);
}

.item-row.log-warning {
	border-left: 4px solid var(--color-warning);
}

.item-row.log-success {
	border-left: 4px solid var(--color-success);
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
	.message-column {
		min-width: 200px;
		max-width: 300px;
	}
}

/* Badge styles for log levels */
.badge-error {
	display: inline-block;
	padding: 4px 8px;
	background: var(--color-error);
	color: white;
	border-radius: 12px;
	font-size: 0.75rem;
	font-weight: 500;
}

.badge-warning {
	display: inline-block;
	padding: 4px 8px;
	background: var(--color-warning);
	color: white;
	border-radius: 12px;
	font-size: 0.75rem;
	font-weight: 500;
}

.badge-success {
	display: inline-block;
	padding: 4px 8px;
	background: var(--color-success);
	color: white;
	border-radius: 12px;
	font-size: 0.75rem;
	font-weight: 500;
}

.badge-secondary {
	display: inline-block;
	padding: 4px 8px;
	background: var(--color-background-dark);
	color: var(--color-text-maxcontrast);
	border-radius: 12px;
	font-size: 0.75rem;
	font-weight: 500;
}
</style>
