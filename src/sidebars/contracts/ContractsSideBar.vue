<script setup>
import { synchronizationStore } from '../../store/store.js'
</script>

<template>
	<div class="sidebar">
		<div class="sidebar-header">
			<h3>{{ t('openconnector', 'Contracts') }}</h3>
			<p>{{ t('openconnector', 'Filter and manage contracts') }}</p>
		</div>

		<!-- Filters Section -->
		<div class="section">
			<h4>{{ t('openconnector', 'Filters') }}</h4>

			<!-- Synchronization Filter -->
			<div class="filter-group">
				<label>{{ t('openconnector', 'Synchronization') }}</label>
				<NcSelect
					v-model="filters.synchronization"
					:options="synchronizationOptions"
					:placeholder="t('openconnector', 'All synchronizations')"
					:input-label="t('openconnector', 'Synchronization')"
					:clearable="true"
					@input="applyFilters" />
			</div>

			<!-- Sync Status Filter -->
			<div class="filter-group">
				<label>{{ t('openconnector', 'Sync Status') }}</label>
				<NcSelect
					v-model="filters.syncStatus"
					:options="syncStatusOptions"
					:placeholder="t('openconnector', 'All sync statuses')"
					:input-label="t('openconnector', 'Sync Status')"
					:clearable="true"
					@input="applyFilters" />
			</div>

			<!-- Date Range Filter -->
			<div class="filter-group">
				<label>{{ t('openconnector', 'Last Synced') }}</label>
				<div class="date-range">
					<NcDateTimePickerNative
						id="contracts-date-from"
						v-model="filters.dateFrom"
						type="date"
						:placeholder="t('openconnector', 'From')"
						@input="applyFilters" />
					<NcDateTimePickerNative
						id="contracts-date-to"
						v-model="filters.dateTo"
						type="date"
						:placeholder="t('openconnector', 'To')"
						@input="applyFilters" />
				</div>
			</div>

			<!-- Clear Filters -->
			<NcButton v-if="hasActiveFilters" @click="clearFilters">
				{{ t('openconnector', 'Clear Filters') }}
			</NcButton>
		</div>

		<!-- Bulk Actions Section -->
		<div v-if="selectedCount > 0" class="section">
			<h4>{{ t('openconnector', 'Bulk Actions') }}</h4>
			<p class="selection-info">
				{{ t('openconnector', '{count} contracts selected', { count: selectedCount }) }}
			</p>
			<div class="bulk-actions">
				<NcButton type="error" @click="bulkDelete">
					<template #icon>
						<Delete :size="20" />
					</template>
					{{ t('openconnector', 'Delete Selected') }}
				</NcButton>
			</div>
		</div>

		<!-- Statistics Section -->
		<div class="section">
			<h4>{{ t('openconnector', 'Statistics') }}</h4>
			<div v-if="statisticsLoading" class="loading-small">
				<NcLoadingIcon :size="24" />
			</div>
			<div v-else class="stats-grid">
				<div class="stat-item">
					<span class="stat-label">{{ t('openconnector', 'Total Contracts') }}</span>
					<span class="stat-value">{{ filteredCount }}</span>
				</div>
			</div>
		</div>

		<!-- Export Section -->
		<div class="section">
			<h4>{{ t('openconnector', 'Export') }}</h4>
			<NcButton @click="exportFiltered">
				<template #icon>
					<Download :size="20" />
				</template>
				{{ t('openconnector', 'Export Filtered Contracts') }}
			</NcButton>
		</div>
	</div>
</template>

<script>
import {
	NcSelect,
	NcButton,
	NcLoadingIcon,
	NcDateTimePickerNative,
} from '@nextcloud/vue'
import Download from 'vue-material-design-icons/Download.vue'
import Delete from 'vue-material-design-icons/Delete.vue'

export default {
	name: 'ContractsSideBar',
	components: {
		NcSelect,
		NcButton,
		NcLoadingIcon,
		NcDateTimePickerNative,
		Download,
		Delete,
	},
	data() {
		return {
			filters: {
				synchronization: null,
				syncStatus: null,
				dateFrom: null,
				dateTo: null,
			},
			selectedCount: 0,
			filteredCount: 0,
			statistics: {},
			statisticsLoading: false,
			debounceTimer: null,
		}
	},
	computed: {
		/**
		 * Get synchronization filter options
		 * @return {Array} Array of synchronization options
		 */
		synchronizationOptions() {
			return synchronizationStore.synchronizationList.map(sync => ({
				id: sync.id,
				label: sync.name || `Synchronization ${sync.id}`,
			}))
		},
		/**
		 * Get sync status filter options
		 * @return {Array} Array of sync status options
		 */
		syncStatusOptions() {
			return [
				{ id: 'synced', label: this.t('openconnector', 'Synced') },
				{ id: 'stale', label: this.t('openconnector', 'Stale') },
				{ id: 'unsynced', label: this.t('openconnector', 'Unsynced') },
			]
		},
		/**
		 * Check if any filters are active
		 * @return {boolean} Whether any filters are active
		 */
		hasActiveFilters() {
			return Object.values(this.filters).some(value => value !== null && value !== '')
		},
	},
	async mounted() {
		// Load initial statistics
		await this.loadStatistics()

		// Listen for events from main view
		this.$root.$on('contracts-selection-count', this.updateSelectionCount)
		this.$root.$on('contracts-filtered-count', this.updateFilteredCount)
	},
	beforeDestroy() {
		this.$root.$off('contracts-selection-count')
		this.$root.$off('contracts-filtered-count')

		if (this.debounceTimer) {
			clearTimeout(this.debounceTimer)
		}
	},
	methods: {
		/**
		 * Load statistics data
		 * @return {Promise<void>}
		 */
		async loadStatistics() {
			this.statisticsLoading = true
			try {
				// For contracts, we only need basic count statistics
				// The filteredCount will be updated from the main view
				this.statistics = {}
			} catch (error) {
				console.error('Error loading statistics:', error)
				this.statistics = {}
			} finally {
				this.statisticsLoading = false
			}
		},
		/**
		 * Apply filters with debouncing for number inputs
		 * @return {void}
		 */
		debouncedApplyFilters() {
			if (this.debounceTimer) {
				clearTimeout(this.debounceTimer)
			}
			this.debounceTimer = setTimeout(() => {
				this.applyFilters()
			}, 500)
		},
		/**
		 * Apply current filters
		 * @return {void}
		 */
		applyFilters() {
			// Clean up empty values
			const cleanFilters = {}
			Object.entries(this.filters).forEach(([key, value]) => {
				if (value !== null && value !== '') {
					cleanFilters[key] = value
				}
			})

			// Emit filters to main view
			this.$root.$emit('contracts-filters-changed', cleanFilters)
		},
		/**
		 * Clear all filters
		 * @return {void}
		 */
		clearFilters() {
			this.filters = {
				synchronization: null,
				syncStatus: null,
				dateFrom: null,
				dateTo: null,
			}
			this.applyFilters()
		},
		/**
		 * Update selection count from main view
		 * @param {number} count - Number of selected items
		 * @return {void}
		 */
		updateSelectionCount(count) {
			this.selectedCount = count
		},
		/**
		 * Update filtered count from main view
		 * @param {number} count - Number of filtered items
		 * @return {void}
		 */
		updateFilteredCount(count) {
			this.filteredCount = count
		},
		/**
		 * Trigger bulk delete action
		 * @return {void}
		 */
		bulkDelete() {
			this.$root.$emit('contracts-bulk-delete')
		},
		/**
		 * Trigger export filtered action
		 * @return {void}
		 */
		exportFiltered() {
			this.$root.$emit('contracts-export-filtered')
		},
	},
}
</script>

<style scoped>
.sidebar {
	padding: 20px;
	background: var(--color-main-background);
	border-left: 1px solid var(--color-border);
	height: 100%;
	overflow-y: auto;
}

.sidebar-header {
	margin-bottom: 30px;
}

.sidebar-header h3 {
	margin: 0 0 10px 0;
	font-size: 1.5rem;
	font-weight: 300;
}

.sidebar-header p {
	color: var(--color-text-maxcontrast);
	margin: 0;
}

.section {
	margin-bottom: 30px;
	padding-bottom: 20px;
	border-bottom: 1px solid var(--color-border);
}

.section:last-child {
	border-bottom: none;
}

.section h4 {
	margin: 0 0 15px 0;
	font-size: 1.1rem;
	font-weight: 500;
}

.section h5 {
	margin: 15px 0 10px 0;
	font-size: 1rem;
	font-weight: 500;
}

.filter-group {
	margin-bottom: 15px;
}

.filter-group label {
	display: block;
	margin-bottom: 5px;
	font-weight: 500;
	color: var(--color-text-maxcontrast);
}

.date-range {
	display: flex;
	gap: 10px;
}

.date-range > * {
	flex: 1;
}

.loading-small {
	text-align: center;
	padding: 20px;
}

.selection-info {
	color: var(--color-text-maxcontrast);
	margin-bottom: 15px;
}

.bulk-actions {
	display: flex;
	flex-direction: column;
	gap: 10px;
}

.stats-grid {
	display: grid;
	grid-template-columns: 1fr;
	gap: 15px;
}

.stat-item {
	display: flex;
	justify-content: space-between;
	align-items: center;
	padding: 10px;
	background: var(--color-background-hover);
	border-radius: var(--border-radius);
}

.stat-label {
	font-size: 0.9rem;
	color: var(--color-text-maxcontrast);
}

.stat-value {
	font-weight: 600;
	font-size: 1.1rem;
}

.stat-value.error {
	color: var(--color-error);
}

.stat-value.warning {
	color: var(--color-warning);
}

.stat-value.success {
	color: var(--color-success);
}

.chart-container {
	margin-top: 20px;
}

.performance-chart {
	display: flex;
	flex-direction: column;
	gap: 8px;
}

.performance-bar {
	display: flex;
	align-items: center;
	gap: 10px;
	font-size: 0.85rem;
}

.period-label {
	min-width: 80px;
	font-weight: 500;
}

.performance-progress {
	flex: 1;
	height: 8px;
	background: var(--color-background-dark);
	border-radius: 4px;
	overflow: hidden;
}

.performance-fill {
	height: 100%;
	background: var(--color-success);
	transition: width 0.3s ease;
}

.performance-rate {
	min-width: 40px;
	text-align: right;
	font-weight: 500;
}
</style>
