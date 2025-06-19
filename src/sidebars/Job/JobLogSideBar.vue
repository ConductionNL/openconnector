<script setup>
import { logStore, navigationStore, jobStore } from '../../store/store.js'
</script>

<template>
	<NcAppSidebar
		ref="sidebar"
		v-model="activeTab"
		:name="t('openconnector', 'Job Log Management')"
		:subtitle="t('openconnector', 'Filter and manage job execution logs')"
		:subname="t('openconnector', 'Export, view, or delete job logs')"
		:open="navigationStore.sidebarState.jobLogs"
		@update:open="(e) => navigationStore.setSidebarState('jobLogs', e)">
		<NcAppSidebarTab id="filters-tab" :name="t('openconnector', 'Filters')" :order="1">
			<template #icon>
				<FilterOutline :size="20" />
			</template>

			<!-- Filter Section -->
			<div class="filterSection">
				<h3>{{ t('openconnector', 'Filter Job Logs') }}</h3>
				<div class="filterGroup">
					<label for="jobSelect">{{ t('openconnector', 'Job') }}</label>
					<NcSelect
						id="jobSelect"
						v-model="selectedJob"
						:options="jobOptions"
						label="Job"
						placeholder="Select a job"
						@update:modelValue="onJobSelected" />
				</div>
				<div class="filterGroup">
					<label for="levelSelect">{{ t('openconnector', 'Log Levels') }}</label>
					<NcSelect
						id="levelSelect"
						v-model="selectedLevels"
						:options="levelOptions"
						:placeholder="t('openconnector', 'All levels')"
						:input-label="t('openconnector', 'Log Levels')"
						:multiple="true"
						:clearable="true"
						@input="applyFilters">
						<template #option="{ option }">
							{{ option && option.label ? option.label : option }}
						</template>
					</NcSelect>
				</div>
				<div class="filterGroup">
					<label>{{ t('openconnector', 'Date Range') }}</label>
					<NcDateTimePickerNative
						id="dateFromPicker"
						v-model="dateFrom"
						:label="t('openconnector', 'From date')"
						type="datetime-local"
						@input="applyFilters" />
					<NcDateTimePickerNative
						id="dateToPicker"
						v-model="dateTo"
						:label="t('openconnector', 'To date')"
						type="datetime-local"
						@input="applyFilters" />
				</div>
				<div class="filterGroup">
					<label for="messageFilter">{{ t('openconnector', 'Message') }}</label>
					<NcTextField
						id="messageFilter"
						v-model="messageFilter"
						:label="t('openconnector', 'Filter by message')"
						:placeholder="t('openconnector', 'Enter message text')"
						@update:value="handleMessageFilterChange" />
				</div>
				<div class="filterGroup">
					<NcCheckboxRadioSwitch
						v-model="filters.showOnlyErrors"
						:checked="filters.showOnlyErrors"
						:button-variant="true"
						name="show_only_errors"
						type="checkbox"
						@update:checked="(val) => { filters.showOnlyErrors = val; applyFilters() }">
						{{ t('openconnector', 'Show only errors') }}
					</NcCheckboxRadioSwitch>
				</div>
				<div class="filterGroup">
					<NcCheckboxRadioSwitch
						v-model="filters.showOnlySlow"
						:checked="filters.showOnlySlow"
						:button-variant="true"
						name="show_only_slow"
						type="checkbox"
						@update:checked="(val) => { filters.showOnlySlow = val; applyFilters() }">
						{{ t('openconnector', 'Show only slow executions') }}
					</NcCheckboxRadioSwitch>
				</div>
			</div>

			<div class="actionGroup">
				<NcButton @click="clearFilters">
					<template #icon>
						<FilterOffOutline :size="20" />
					</template>
					{{ t('openconnector', 'Clear Filters') }}
				</NcButton>
			</div>

			<NcNoteCard type="info" class="filter-hint">
				{{ t('openconnector', 'Use filters to narrow down job logs by job, log level, date range, or message content.') }}
			</NcNoteCard>
		</NcAppSidebarTab>

		<NcAppSidebarTab id="stats-tab" :name="t('openconnector', 'Statistics')" :order="2">
			<template #icon>
				<ChartLine :size="20" />
			</template>

			<!-- Statistics Section -->
			<div class="statsSection">
				<h3>{{ t('openconnector', 'Job Log Statistics') }}</h3>
				<div class="statCard">
					<div class="statNumber">
						{{ totalLogs }}
					</div>
					<div class="statLabel">
						{{ t('openconnector', 'Total Job Logs') }}
					</div>
				</div>
				<div class="statCard success">
					<div class="statNumber">
						{{ successCount }}
					</div>
					<div class="statLabel">
						{{ t('openconnector', 'Successful Executions') }}
					</div>
				</div>
				<div class="statCard error">
					<div class="statNumber">
						{{ errorCount }}
					</div>
					<div class="statLabel">
						{{ t('openconnector', 'Failed Executions') }}
					</div>
				</div>
				<div class="statCard">
					<div class="statNumber">
						{{ averageExecutionTime }}s
					</div>
					<div class="statLabel">
						{{ t('openconnector', 'Average Execution Time') }}
					</div>
				</div>
			</div>

			<!-- Log Level Distribution -->
			<div class="levelDistribution">
				<h4>{{ t('openconnector', 'Log Level Distribution') }}</h4>
				<NcListItem v-for="(level, index) in levelDistribution"
					:key="index"
					:name="level.name"
					:bold="false">
					<template #icon>
						<CheckCircle v-if="level.name === 'SUCCESS'" :size="32" />
						<AlertCircle v-else-if="level.name === 'WARNING'" :size="32" />
						<CloseCircle v-else-if="level.name === 'ERROR'" :size="32" />
						<InformationOutline v-else :size="32" />
					</template>
					<template #subname>
						{{ t('openconnector', '{count} logs', { count: level.count }) }}
					</template>
				</NcListItem>
			</div>

			<!-- Top Jobs -->
			<div class="topJobs">
				<h4>{{ t('openconnector', 'Most Active Jobs') }}</h4>
				<NcListItem v-for="(job, index) in topJobs"
					:key="index"
					:name="job.name"
					:bold="false">
					<template #icon>
						<Update :size="32" />
					</template>
					<template #subname>
						{{ t('openconnector', '{count} executions', { count: job.count }) }}
					</template>
				</NcListItem>
			</div>
		</NcAppSidebarTab>
	</NcAppSidebar>
</template>

<script>
import {
	NcAppSidebar,
	NcAppSidebarTab,
	NcSelect,
	NcNoteCard,
	NcButton,
	NcListItem,
	NcDateTimePickerNative,
	NcTextField,
	NcCheckboxRadioSwitch,
} from '@nextcloud/vue'
import FilterOutline from 'vue-material-design-icons/FilterOutline.vue'
import ChartLine from 'vue-material-design-icons/ChartLine.vue'
import Update from 'vue-material-design-icons/Update.vue'
import CheckCircle from 'vue-material-design-icons/CheckCircle.vue'
import AlertCircle from 'vue-material-design-icons/AlertCircle.vue'
import CloseCircle from 'vue-material-design-icons/CloseCircle.vue'
import InformationOutline from 'vue-material-design-icons/InformationOutline.vue'
import FilterOffOutline from 'vue-material-design-icons/FilterOffOutline.vue'
import { translate as t } from '@nextcloud/l10n'

export default {
	name: 'JobLogSideBar',
	components: {
		NcAppSidebar,
		NcAppSidebarTab,
		NcSelect,
		NcNoteCard,
		NcButton,
		NcListItem,
		NcDateTimePickerNative,
		NcTextField,
		NcCheckboxRadioSwitch,
		FilterOutline,
		ChartLine,
		Update,
		CheckCircle,
		AlertCircle,
		CloseCircle,
		InformationOutline,
		FilterOffOutline,
	},
	data() {
		return {
			activeTab: 'filters-tab',
			selectedJob: null,
			selectedLevels: [],
			dateFrom: null,
			dateTo: null,
			messageFilter: '',
			filters: {
				jobId: null,
				logLevel: null,
				startDate: null,
				endDate: null,
				message: '',
				showOnlyErrors: false,
				showOnlySlow: false,
			},
			filteredCount: 0,
			totalLogs: 0,
			successCount: 0,
			errorCount: 0,
			averageExecutionTime: 0,
			levelDistribution: [],
			topJobs: [],
			filterTimeout: null,
		}
	},
	computed: {
		levelOptions() {
			return [
				{ label: 'SUCCESS', value: 'SUCCESS' },
				{ label: 'INFO', value: 'INFO' },
				{ label: 'NOTICE', value: 'NOTICE' },
				{ label: 'WARNING', value: 'WARNING' },
				{ label: 'ERROR', value: 'ERROR' },
				{ label: 'CRITICAL', value: 'CRITICAL' },
				{ label: 'ALERT', value: 'ALERT' },
				{ label: 'EMERGENCY', value: 'EMERGENCY' },
				{ label: 'DEBUG', value: 'DEBUG' },
			]
		},
		jobOptions() {
			return jobStore.jobList?.map(job => ({
				value: job,
				label: job.name,
				title: job.name,
			})) || []
		},
		selectedJobValue() {
			if (!jobStore.jobItem) return null
			return jobStore.jobList?.find(j => j.id === jobStore.jobItem.id) || null
		},
	},
	watch: {
		'jobStore.jobItem'() {
			this.selectedJob = this.selectedJobValue
			this.applyFilters()
		},
	},
	mounted() {
		// Load required data
		if (!jobStore.jobList?.length) {
			jobStore.refreshJobList()
		}

		// Load initial log data
		this.loadLogData()
		this.loadStatistics()
		this.loadLevelDistribution()
		this.loadTopJobs()

		// Listen for filtered count updates
		this.$root.$on('job-log-filtered-count', (count) => {
			this.filteredCount = count
		})

		// Watch store changes and update count
		this.updateFilteredCount()

		this.selectedJob = this.selectedJobValue
	},
	beforeDestroy() {
		this.$root.$off('job-log-filtered-count')
	},
	methods: {
		/**
		 * Load job log data and update filtered count
		 */
		async loadLogData() {
			try {
				// Only refresh logs if a job is selected
				if (this.filters.jobId) {
					await jobStore.refreshJobLogs(this.filters.jobId)
					this.updateFilteredCount()
				}
			} catch (error) {
				console.error('Error loading log data:', error)
			}
		},
		/**
		 * Clear all filters
		 */
		clearAllFilters() {
			// Clear component state
			this.selectedLevels = []
			this.dateFrom = null
			this.dateTo = null
			this.messageFilter = ''
			this.filters.showOnlyErrors = false
			this.filters.showOnlySlow = false

			// Clear global stores
			jobStore.setJobItem(null)

			// Clear store filters
			logStore.setLogFilters({})

			// Refresh without applying filters
			jobStore.refreshJobLogs()
		},
		/**
		 * Clear filters (alias for clearAllFilters for template compatibility)
		 */
		clearFilters() {
			this.clearAllFilters()
		},
		/**
		 * Handle message filter change with debouncing
		 * @param {string} value - The message filter value
		 */
		handleMessageFilterChange(value) {
			this.messageFilter = value
			this.debouncedApplyFilters()
		},
		/**
		 * Apply filters and emit to parent components
		 */
		applyFilters() {
			const filters = {}

			// Build level filter
			if (Array.isArray(this.selectedLevels) && this.selectedLevels.length > 0) {
				const levels = this.selectedLevels.filter(l => l && l.value).map(l => l.value)
				if (levels.length > 0) {
					filters.level = levels.join(',')
				}
			}

			// Build job filter
			if (this.selectedJob && this.selectedJob.value) {
				filters.job_id = this.selectedJob.value.id.toString()
			}

			// Date filters
			if (this.dateFrom) {
				filters.dateFrom = this.dateFrom
			}
			if (this.dateTo) {
				filters.dateTo = this.dateTo
			}

			// Message filter
			if (this.messageFilter) {
				filters.message = this.messageFilter
			}

			// Error filter
			if (this.filters.showOnlyErrors) {
				filters.onlyErrors = true
			}

			// Slow executions filter
			if (this.filters.showOnlySlow) {
				filters.slowExecutions = true
			}

			// Set filters in store and refresh data
			logStore.setLogFilters(filters)
			jobStore.refreshJobLogs(filters)

			// Also emit for legacy compatibility
			this.$root.$emit('job-log-filters-changed', filters)
		},
		/**
		 * Debounced version of applyFilters for text input
		 */
		debouncedApplyFilters() {
			clearTimeout(this.filterTimeout)
			this.filterTimeout = setTimeout(() => {
				this.applyFilters()
			}, 500)
		},
		/**
		 * Update filtered count from store
		 */
		updateFilteredCount() {
			const logs = jobStore.jobLogs || []
			this.filteredCount = logs.length
			this.totalLogs = logs.length
			this.successCount = logs.filter(log => log.level === 'SUCCESS').length
			this.errorCount = logs.filter(log => ['ERROR', 'CRITICAL', 'ALERT', 'EMERGENCY'].includes(log.level)).length

			// Calculate average execution time
			const executionTimes = logs
				.filter(log => log.executionTime)
				.map(log => log.executionTime)
			if (executionTimes.length > 0) {
				this.averageExecutionTime = executionTimes.reduce((a, b) => a + b, 0) / executionTimes.length
			} else {
				this.averageExecutionTime = 0
			}
		},
		/**
		 * Load statistics
		 */
		async loadStatistics() {
			try {
				const logs = (jobStore.jobLogs && Array.isArray(jobStore.jobLogs.results)) ? jobStore.jobLogs.results : []
				this.totalLogs = logs.length
				this.successCount = logs.filter(log => log.level === 'SUCCESS').length
				this.errorCount = logs.filter(log => ['ERROR', 'CRITICAL', 'ALERT', 'EMERGENCY'].includes(log.level)).length
				const executionTimes = logs.filter(log => log.executionTime).map(log => log.executionTime / 1000)
				this.averageExecutionTime = executionTimes.length > 0 ? (executionTimes.reduce((sum, time) => sum + time, 0) / executionTimes.length).toFixed(3) : 0
			} catch (error) {
				console.error('Error loading statistics:', error)
			}
		},
		/**
		 * Load log level distribution for stats
		 */
		async loadLevelDistribution() {
			try {
				const logs = (jobStore.jobLogs && Array.isArray(jobStore.jobLogs.results)) ? jobStore.jobLogs.results : []
				const levelMap = {}
				logs.forEach(log => {
					const level = log.level
					if (!levelMap[level]) {
						levelMap[level] = {
							name: level,
							count: 0,
						}
					}
					levelMap[level].count++
				})
				this.levelDistribution = Object.values(levelMap).sort((a, b) => b.count - a.count)
			} catch (error) {
				console.error('Error loading level distribution:', error)
			}
		},
		/**
		 * Load top jobs for stats
		 */
		async loadTopJobs() {
			try {
				const logs = (jobStore.jobLogs && Array.isArray(jobStore.jobLogs.results)) ? jobStore.jobLogs.results : []
				const jobMap = {}
				logs.forEach(log => {
					const jobId = log.jobId
					if (!jobMap[jobId]) {
						const job = jobStore.jobList?.find(j => j.id === jobId)
						jobMap[jobId] = {
							name: job?.name || `Job ${jobId}`,
							count: 0,
						}
					}
					jobMap[jobId].count++
				})
				this.topJobs = Object.values(jobMap).sort((a, b) => b.count - a.count).slice(0, 10)
			} catch (error) {
				console.error('Error loading top jobs:', error)
			}
		},
		/**
		 * Handle job change
		 * @param {object} job - The selected job object
		 */
		onJobSelected(job) {
			this.selectedJob = job
			this.filters.jobId = job?.id || null
			jobStore.selectedJobId = job?.id || null
			this.loadLogData()
		},
	},
}
</script>

<style scoped>
.filterSection,
.statsSection {
	padding: 12px 0;
	border-bottom: 1px solid var(--color-border);
}

.filterSection:last-child,
.statsSection:last-child {
	border-bottom: none;
}

.filterSection h3,
.statsSection h3 {
	color: var(--color-text-maxcontrast);
	font-size: 14px;
	font-weight: bold;
	padding: 0 16px;
	margin: 0 0 12px 0;
}

.filterGroup {
	display: flex;
	flex-direction: column;
	gap: 8px;
	padding: 0 16px;
	margin-bottom: 16px;
}

.filterGroup label {
	font-size: 0.9em;
	color: var(--color-text-maxcontrast);
}

.actionGroup {
	padding: 0 16px;
	margin-bottom: 12px;
}

.filter-hint {
	margin: 8px 16px;
}

.statsSection {
	padding: 16px;
}

.statCard {
	background: var(--color-background-hover);
	border-radius: var(--border-radius);
	padding: 16px;
	margin-bottom: 12px;
	text-align: center;
}

.statCard.success {
	border-left: 4px solid var(--color-success);
}

.statCard.error {
	border-left: 4px solid var(--color-error);
}

.statNumber {
	font-size: 2rem;
	font-weight: bold;
	color: var(--color-primary);
	margin-bottom: 4px;
}

.statLabel {
	font-size: 0.9rem;
	color: var(--color-text-maxcontrast);
}

.levelDistribution,
.topJobs {
	margin-top: 20px;
}

.levelDistribution h4,
.topJobs h4 {
	margin: 0 0 12px 0;
	font-size: 1rem;
	font-weight: 500;
	color: var(--color-main-text);
}

/* Add some spacing between select inputs */
:deep(.v-select) {
	margin-bottom: 8px;
}
</style>
