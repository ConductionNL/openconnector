<script setup>
import { logStore, contractStore, synchronizationStore } from '../../store/store.js'
</script>

<template>
	<div class="sidebar">
		<div class="sidebar-header">
			<h3>{{ t('openconnector', 'Logs') }}</h3>
			<p>{{ t('openconnector', 'Filter and manage logs') }}</p>
		</div>

		<!-- Filters Section -->
		<div class="section">
			<h4>{{ t('openconnector', 'Filters') }}</h4>

			<!-- Level Filter -->
			<div class="filter-group">
				<label>{{ t('openconnector', 'Level') }}</label>
				<NcSelect
					v-model="filters.level"
					:options="levelOptions"
					:placeholder="t('openconnector', 'All levels')"
					:input-label="t('openconnector', 'Level')"
					:clearable="true"
					@input="applyFilters" />
			</div>

			<!-- Contract Filter -->
			<div class="filter-group">
				<label>{{ t('openconnector', 'Contract') }}</label>
				<NcSelect
					v-model="filters.contract"
					:options="contractOptions"
					:placeholder="t('openconnector', 'All contracts')"
					:input-label="t('openconnector', 'Contract')"
					:clearable="true"
					@input="applyFilters" />
			</div>

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

			<!-- Date Range Filter -->
			<div class="filter-group">
				<label>{{ t('openconnector', 'Date Range') }}</label>
				<div class="date-range">
					<NcDateTimePickerNative
						id="logs-date-from"
						v-model="filters.dateFrom"
						type="date"
						:placeholder="t('openconnector', 'From')"
						@input="applyFilters" />
					<NcDateTimePickerNative
						id="logs-date-to"
						v-model="filters.dateTo"
						type="date"
						:placeholder="t('openconnector', 'To')"
						@input="applyFilters" />
				</div>
			</div>

			<!-- Message Filter -->
			<div class="filter-group">
				<label>{{ t('openconnector', 'Message') }}</label>
				<NcTextField
					v-model="filters.message"
					:placeholder="t('openconnector', 'Search in messages...')"
					@input="debouncedApplyFilters" />
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
				{{ t('openconnector', '{count} logs selected', { count: selectedCount }) }}
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
					<span class="stat-label">{{ t('openconnector', 'Total Logs') }}</span>
					<span class="stat-value">{{ filteredCount }}</span>
				</div>
				<div class="stat-item">
					<span class="stat-label">{{ t('openconnector', 'Error Logs') }}</span>
					<span class="stat-value error">{{ statistics.errorCount || 0 }}</span>
				</div>
				<div class="stat-item">
					<span class="stat-label">{{ t('openconnector', 'Warning Logs') }}</span>
					<span class="stat-value warning">{{ statistics.warningCount || 0 }}</span>
				</div>
				<div class="stat-item">
					<span class="stat-label">{{ t('openconnector', 'Info Logs') }}</span>
					<span class="stat-value success">{{ statistics.infoCount || 0 }}</span>
				</div>
			</div>

			<!-- Level Distribution Chart -->
			<div v-if="statistics.levelDistribution" class="chart-container">
				<h5>{{ t('openconnector', 'Level Distribution') }}</h5>
				<div class="level-chart">
					<div v-for="(count, level) in statistics.levelDistribution"
						:key="level"
						class="level-bar"
						:class="'level-' + level">
						<div class="level-label">
							{{ getLevelLabel(level) }}
						</div>
						<div class="level-progress">
							<div class="level-fill"
								:style="{ width: getLevelPercentage(count) + '%' }" />
						</div>
						<div class="level-count">
							{{ count }}
						</div>
					</div>
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
				{{ t('openconnector', 'Export Filtered Logs') }}
			</NcButton>
		</div>
	</div>
</template>

<script>
import {
	NcSelect,
	NcTextField,
	NcButton,
	NcLoadingIcon,
	NcDateTimePickerNative,
} from '@nextcloud/vue'
import Delete from 'vue-material-design-icons/Delete.vue'
import Download from 'vue-material-design-icons/Download.vue'

export default {
	name: 'LogsSideBar',
	components: {
		NcSelect,
		NcTextField,
		NcButton,
		NcLoadingIcon,
		NcDateTimePickerNative,
		Delete,
		Download,
	},
	data() {
		return {
			filters: {
				level: null,
				contract: null,
				synchronization: null,
				dateFrom: null,
				dateTo: null,
				message: '',
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
		 * Get level filter options
		 * @return {Array} Array of level options
		 */
		levelOptions() {
			return [
				{ id: 'error', label: this.t('openconnector', 'Error') },
				{ id: 'warning', label: this.t('openconnector', 'Warning') },
				{ id: 'info', label: this.t('openconnector', 'Info') },
				{ id: 'success', label: this.t('openconnector', 'Success') },
				{ id: 'debug', label: this.t('openconnector', 'Debug') },
			]
		},
		/**
		 * Get contract filter options
		 * @return {Array} Array of contract options
		 */
		contractOptions() {
			return contractStore.contractsList.map(contract => ({
				id: contract.id,
				label: contract.name || `Contract ${contract.id}`,
			}))
		},
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
		this.$root.$on('logs-selection-count', this.updateSelectionCount)
		this.$root.$on('logs-filtered-count', this.updateFilteredCount)
	},
	beforeDestroy() {
		this.$root.$off('logs-selection-count')
		this.$root.$off('logs-filtered-count')

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
				await logStore.fetchStatistics()
				this.statistics = logStore.logsStatistics
			} catch (error) {
				console.error('Error loading statistics:', error)
				// Set default empty statistics to prevent errors
				this.statistics = {
					errorCount: 0,
					warningCount: 0,
					infoCount: 0,
					levelDistribution: {},
				}
			} finally {
				this.statisticsLoading = false
			}
		},
		/**
		 * Apply filters with debouncing for text inputs
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
			this.$root.$emit('logs-filters-changed', cleanFilters)
		},
		/**
		 * Clear all filters
		 * @return {void}
		 */
		clearFilters() {
			this.filters = {
				level: null,
				contract: null,
				synchronization: null,
				dateFrom: null,
				dateTo: null,
				message: '',
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
			this.$root.$emit('logs-bulk-delete')
		},
		/**
		 * Trigger export filtered action
		 * @return {void}
		 */
		exportFiltered() {
			this.$root.$emit('logs-export-filtered')
		},
		/**
		 * Get level label for display
		 * @param {string} level - Log level
		 * @return {string} Level label
		 */
		getLevelLabel(level) {
			const levelOption = this.levelOptions.find(option => option.id === level)
			return levelOption ? levelOption.label : level
		},
		/**
		 * Get percentage for level distribution
		 * @param {number} count - Count for this level
		 * @return {number} Percentage
		 */
		getLevelPercentage(count) {
			const total = Object.values(this.statistics.levelDistribution || {}).reduce((sum, c) => sum + c, 0)
			return total > 0 ? (count / total) * 100 : 0
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

.level-chart {
	display: flex;
	flex-direction: column;
	gap: 8px;
}

.level-bar {
	display: flex;
	align-items: center;
	gap: 10px;
	font-size: 0.85rem;
}

.level-label {
	min-width: 60px;
	font-weight: 500;
}

.level-progress {
	flex: 1;
	height: 8px;
	background: var(--color-background-dark);
	border-radius: 4px;
	overflow: hidden;
}

.level-fill {
	height: 100%;
	transition: width 0.3s ease;
}

.level-bar.level-error .level-fill {
	background: var(--color-error);
}

.level-bar.level-warning .level-fill {
	background: var(--color-warning);
}

.level-bar.level-info .level-fill,
.level-bar.level-success .level-fill {
	background: var(--color-success);
}

.level-bar.level-debug .level-fill {
	background: var(--color-background-dark);
}

.level-count {
	min-width: 30px;
	text-align: right;
	font-weight: 500;
}
</style>
