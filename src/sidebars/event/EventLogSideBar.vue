<script setup>
import { eventStore } from '../../store/store.js'
</script>

<template>
	<NcAppSidebar>
		<div class="sidebarContainer">
			<!-- Filter Section -->
			<div class="sidebarSection">
				<h2 class="sidebarSectionTitle">
					{{ t('openconnector', 'Filter Logs') }}
				</h2>

				<!-- Event Filter -->
				<div class="sidebarFilter">
					<label class="sidebarFilterLabel">
						{{ t('openconnector', 'Event') }}
					</label>
					<NcSelect
						v-model="filters.eventId"
						:options="eventOptions"
						:clearable="true"
						:placeholder="t('openconnector', 'Select event')"
						@update:model-value="handleFilterChange" />
				</div>

				<!-- Log Level Filter -->
				<div class="sidebarFilter">
					<label class="sidebarFilterLabel">
						{{ t('openconnector', 'Log Level') }}
					</label>
					<NcSelect
						v-model="filters.level"
						:options="logLevelOptions"
						:clearable="true"
						:placeholder="t('openconnector', 'Select level')"
						@update:model-value="handleFilterChange" />
				</div>

				<!-- Date Range Filter -->
				<div class="sidebarFilter">
					<label class="sidebarFilterLabel">
						{{ t('openconnector', 'Date Range') }}
					</label>
					<div class="dateRangeContainer">
						<NcDateTimePickerNative
							v-model="filters.startDate"
							type="datetime-local"
							:placeholder="t('openconnector', 'Start date')"
							@update:model-value="handleFilterChange" />
						<NcDateTimePickerNative
							v-model="filters.endDate"
							type="datetime-local"
							:placeholder="t('openconnector', 'End date')"
							@update:model-value="handleFilterChange" />
					</div>
				</div>

				<!-- Message Filter -->
				<div class="sidebarFilter">
					<label class="sidebarFilterLabel">
						{{ t('openconnector', 'Message') }}
					</label>
					<NcTextField
						v-model="filters.message"
						type="text"
						:placeholder="t('openconnector', 'Search in messages')"
						@update:model-value="handleFilterChange" />
				</div>

				<!-- Additional Filters -->
				<div class="sidebarFilter">
					<label class="sidebarFilterLabel">
						{{ t('openconnector', 'Options') }}
					</label>
					<div class="filterOptions">
						<NcCheckboxRadioSwitch
							v-model="filters.showOnlyErrors"
							:title="t('openconnector', 'Show only errors')"
							@update:model-value="handleFilterChange">
							{{ t('openconnector', 'Show only errors') }}
						</NcCheckboxRadioSwitch>
						<NcCheckboxRadioSwitch
							v-model="filters.showOnlySlow"
							:title="t('openconnector', 'Show only slow executions')"
							@update:model-value="handleFilterChange">
							{{ t('openconnector', 'Show only slow executions') }}
						</NcCheckboxRadioSwitch>
					</div>
				</div>

				<!-- Clear Filters Button -->
				<NcButton
					v-if="hasActiveFilters"
					type="secondary"
					class="clearFiltersButton"
					@click="clearFilters">
					<template #icon>
						<Close :size="20" />
					</template>
					{{ t('openconnector', 'Clear Filters') }}
				</NcButton>
			</div>

			<!-- Statistics Section -->
			<div class="sidebarSection">
				<h2 class="sidebarSectionTitle">
					{{ t('openconnector', 'Statistics') }}
				</h2>

				<div class="statisticsGrid">
					<div class="statisticCard">
						<span class="statisticLabel">
							{{ t('openconnector', 'Total Logs') }}
						</span>
						<span class="statisticValue">
							{{ statistics.totalLogs }}
						</span>
					</div>
					<div class="statisticCard">
						<span class="statisticLabel">
							{{ t('openconnector', 'Successful') }}
						</span>
						<span class="statisticValue success">
							{{ statistics.successfulLogs }}
						</span>
					</div>
					<div class="statisticCard">
						<span class="statisticLabel">
							{{ t('openconnector', 'Failed') }}
						</span>
						<span class="statisticValue error">
							{{ statistics.failedLogs }}
						</span>
					</div>
					<div class="statisticCard">
						<span class="statisticLabel">
							{{ t('openconnector', 'Avg. Execution Time') }}
						</span>
						<span class="statisticValue">
							{{ formatExecutionTime(statistics.averageExecutionTime) }}
						</span>
					</div>
				</div>
			</div>

			<!-- Note Section -->
			<div class="sidebarSection">
				<NcNoteCard type="info">
					<template #icon>
						<InformationOutline :size="20" />
					</template>
					<template #title>
						{{ t('openconnector', 'About Event Logs') }}
					</template>
					{{ t('openconnector', 'Event logs track the execution of events in the system. Use the filters above to find specific logs or analyze patterns in event execution.') }}
				</NcNoteCard>
			</div>
		</div>
	</NcAppSidebar>
</template>

<script>
import { NcAppSidebar, NcSelect, NcTextField, NcButton, NcCheckboxRadioSwitch, NcDateTimePickerNative, NcNoteCard } from '@nextcloud/vue'
import { translate as t } from '@nextcloud/l10n'
import Close from 'vue-material-design-icons/Close.vue'
import InformationOutline from 'vue-material-design-icons/InformationOutline.vue'

export default {
	name: 'EventLogSideBar',
	components: {
		NcAppSidebar,
		NcSelect,
		NcTextField,
		NcButton,
		NcCheckboxRadioSwitch,
		NcDateTimePickerNative,
		NcNoteCard,
		Close,
		InformationOutline,
	},
	data() {
		return {
			filters: {
				eventId: null,
				level: null,
				startDate: null,
				endDate: null,
				message: '',
				showOnlyErrors: false,
				showOnlySlow: false,
			},
			statistics: {
				totalLogs: 0,
				successfulLogs: 0,
				failedLogs: 0,
				averageExecutionTime: 0,
			},
		}
	},
	computed: {
		hasActiveFilters() {
			return Object.values(this.filters).some(value => {
				if (typeof value === 'boolean') return value
				return value !== null && value !== ''
			})
		},
		eventOptions() {
			if (!eventStore.eventList) return []
			return eventStore.eventList.map(event => ({
				label: event.name,
				value: event.id,
			}))
		},
		logLevelOptions() {
			return [
				{ label: t('openconnector', 'Success'), value: 'SUCCESS' },
				{ label: t('openconnector', 'Warning'), value: 'WARNING' },
				{ label: t('openconnector', 'Error'), value: 'ERROR' },
				{ label: t('openconnector', 'Critical'), value: 'CRITICAL' },
				{ label: t('openconnector', 'Alert'), value: 'ALERT' },
				{ label: t('openconnector', 'Emergency'), value: 'EMERGENCY' },
				{ label: t('openconnector', 'Info'), value: 'INFO' },
			]
		},
	},
	mounted() {
		eventStore.refreshEventList()
		this.updateStatistics()
	},
	methods: {
		handleFilterChange() {
			this.$root.$emit('event-log-filters-changed', this.filters)
			this.updateStatistics()
		},
		clearFilters() {
			this.filters = {
				eventId: null,
				level: null,
				startDate: null,
				endDate: null,
				message: '',
				showOnlyErrors: false,
				showOnlySlow: false,
			}
			this.handleFilterChange()
		},
		updateStatistics() {
			const logs = eventStore.eventLogs?.results || []
			this.statistics = {
				totalLogs: logs.length,
				successfulLogs: logs.filter(log => log.level === 'SUCCESS').length,
				failedLogs: logs.filter(log => ['ERROR', 'CRITICAL', 'ALERT', 'EMERGENCY'].includes(log.level)).length,
				averageExecutionTime: this.calculateAverageExecutionTime(logs),
			}
		},
		calculateAverageExecutionTime(logs) {
			const executionTimes = logs
				.filter(log => log.executionTime)
				.map(log => log.executionTime)
			if (executionTimes.length === 0) return 0
			return executionTimes.reduce((sum, time) => sum + time, 0) / executionTimes.length
		},
		formatExecutionTime(time) {
			if (!time) return '0s'
			return `${(time / 1000).toFixed(3)}s`
		},
	},
}
</script>

<style scoped>
.sidebarContainer {
	padding: 16px;
	display: flex;
	flex-direction: column;
	gap: 24px;
}

.sidebarSection {
	display: flex;
	flex-direction: column;
	gap: 16px;
}

.sidebarSectionTitle {
	font-size: 1.1em;
	font-weight: 600;
	margin: 0;
}

.sidebarFilter {
	display: flex;
	flex-direction: column;
	gap: 8px;
}

.sidebarFilterLabel {
	font-size: 0.9em;
	color: var(--color-text-lighter);
}

.dateRangeContainer {
	display: flex;
	flex-direction: column;
	gap: 8px;
}

.filterOptions {
	display: flex;
	flex-direction: column;
	gap: 8px;
}

.clearFiltersButton {
	margin-top: 8px;
}

.statisticsGrid {
	display: grid;
	grid-template-columns: repeat(2, 1fr);
	gap: 12px;
}

.statisticCard {
	background: var(--color-background-hover);
	padding: 12px;
	border-radius: 8px;
	display: flex;
	flex-direction: column;
	gap: 4px;
}

.statisticLabel {
	font-size: 0.8em;
	color: var(--color-text-lighter);
}

.statisticValue {
	font-size: 1.2em;
	font-weight: 600;
}

.statisticValue.success {
	color: var(--color-success);
}

.statisticValue.error {
	color: var(--color-error);
}
</style>
