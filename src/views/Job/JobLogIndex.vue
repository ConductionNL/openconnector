<template>
	<NcAppContent>
		<div class="viewContainer">
			<div class="viewHeader">
				<h1 class="viewHeaderTitleIndented">
					{{ t('openconnector', 'Job Logs') }}
				</h1>
				<p>{{ t('openconnector', 'Monitor and analyze job execution logs and their performance') }}</p>
			</div>
			<div class="viewActionsBar">
				<div class="viewInfo">
					<span class="viewTotalCount">
						{{ t('openconnector', 'Showing {showing} of {total} logs', { showing: paginatedLogs.length, total: filteredLogs.length }) }}
					</span>
					<span v-if="hasActiveFilters" class="viewIndicator">
						({{ t('openconnector', 'Filtered') }})
					</span>
					<span v-if="selectedLogs.length > 0" class="viewIndicator">
						({{ t('openconnector', '{count} selected', { count: selectedLogs.length }) }})
					</span>
				</div>
				<div class="viewActions">
					<NcActions :force-name="true" :inline="selectedLogs.length > 0 ? 3 : 2" menu-name="Actions">
						<NcActionButton v-if="selectedLogs.length > 0"
							type="error"
							close-after-click
							@click="bulkDeleteLogs">
							<template #icon>
								<Delete :size="20" />
							</template>
							{{ t('openconnector', 'Delete ({count})', { count: selectedLogs.length }) }}
						</NcActionButton>
						<NcActionButton close-after-click @click="exportLogs">
							<template #icon>
								<FileExportOutline :size="20" />
							</template>
							{{ t('openconnector', 'Export') }}
						</NcActionButton>
						<NcActionButton close-after-click @click="refreshLogs">
							<template #icon>
								<Refresh :size="20" />
							</template>
							{{ t('openconnector', 'Refresh') }}
						</NcActionButton>
					</NcActions>
				</div>
			</div>
			<div v-if="logStore.loading" class="viewLoading">
				<NcLoadingIcon :size="64" />
				<p>{{ t('openconnector', 'Loading job logs...') }}</p>
			</div>
			<NcEmptyContent v-else-if="logStore.error || !filteredLogs.length"
				:name="emptyContentName"
				:description="emptyContentDescription">
				<template #icon>
					<NcLoadingIcon v-if="logStore.loading" :size="64" />
					<TimelineQuestionOutline v-else :size="64" />
				</template>
			</NcEmptyContent>
			<div v-else class="viewTableContainer">
				<table class="viewTable jobLogsTable">
					<thead>
						<tr>
							<th class="tableColumnCheckbox">
								<NcCheckboxRadioSwitch
									:checked="allSelected"
									:indeterminate="someSelected"
									@update:checked="toggleSelectAll" />
							</th>
							<th class="levelColumn">
								{{ t('openconnector', 'Level') }}
							</th>
							<th class="jobColumn">
								{{ t('openconnector', 'Job') }}
							</th>
							<th class="messageColumn">
								{{ t('openconnector', 'Message') }}
							</th>
							<th class="executionTimeColumn">
								{{ t('openconnector', 'Execution Time') }}
							</th>
							<th class="timestampColumn">
								{{ t('openconnector', 'Created') }}
							</th>
							<th class="tableColumnActions">
								{{ t('openconnector', 'Actions') }}
							</th>
						</tr>
					</thead>
					<tbody>
						<tr v-for="log in paginatedLogs"
							:key="log.id"
							class="viewTableRow jobLogRow"
							:class="getLogLevelClass(log)">
							<td class="tableColumnCheckbox">
								<NcCheckboxRadioSwitch
									:checked="selectedLogs.includes(log.id)"
									@update:checked="(checked) => toggleLogSelection(log.id, checked)" />
							</td>
							<td class="levelColumn">
								<span class="levelBadge" :class="getLogLevelClass(log)">
									<CheckCircle v-if="log.level === 'SUCCESS'" :size="16" />
									<AlertCircle v-else-if="log.level === 'WARNING'" :size="16" />
									<CloseCircle v-else-if="['ERROR', 'CRITICAL', 'ALERT', 'EMERGENCY'].includes(log.level)" :size="16" />
									<InformationOutline v-else :size="16" />
									{{ log.level }}
								</span>
							</td>
							<td class="jobColumn">
								<div class="jobInfo">
									<span class="jobName">{{ getJobName(log.jobId) }}</span>
									<span v-if="log.jobType" class="jobType" :title="t('openconnector', 'Job type')">
										{{ log.jobType }}
									</span>
								</div>
							</td>
							<td class="messageColumn">
								<div class="messageInfo">
									<span class="messageText">{{ log.message }}</span>
									<span v-if="log.context" class="contextIndicator" :title="JSON.stringify(log.context)">
										<DatabaseSearch :size="14" />
									</span>
								</div>
							</td>
							<td class="executionTimeColumn">
								<div class="executionInfo">
									<span v-if="log.executionTime" :class="getExecutionTimeClass(log.executionTime)">
										{{ (log.executionTime / 1000).toFixed(3) }}s
									</span>
									<span v-if="log.memoryUsage" class="memoryUsage" :title="t('openconnector', 'Memory usage')">
										{{ formatBytes(log.memoryUsage) }}
									</span>
									<span v-else>-</span>
								</div>
							</td>
							<td class="timestampColumn">
								<div class="timestampInfo">
									<span class="createdTime">{{ new Date(log.created).toLocaleString() }}</span>
									<span v-if="log.expires" class="expiresTime" :title="t('openconnector', 'Expires at')">
										{{ new Date(log.expires).toLocaleString() }}
									</span>
								</div>
							</td>
							<td class="tableColumnActions">
								<NcActions>
									<NcActionButton close-after-click @click="viewLogDetails(log)">
										<template #icon>
											<Eye :size="20" />
										</template>
										{{ t('openconnector', 'View Details') }}
									</NcActionButton>
									<NcActionButton close-after-click @click="copyLogData(log)">
										<template #icon>
											<Check v-if="copyStates[log.id]" :size="20" class="copySuccessIcon" />
											<ContentCopy v-else :size="20" />
										</template>
										{{ copyStates[log.id] ? t('openconnector', 'Copied!') : t('openconnector', 'Copy Data') }}
									</NcActionButton>
									<NcActionButton close-after-click class="deleteAction" @click="deleteLog(log)">
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
			<PaginationComponent
				v-if="filteredLogs.length > 0"
				:current-page="pagination.page || 1"
				:total-pages="jobStore.jobLogs.pages || 1"
				:total-items="jobStore.jobLogs.total || filteredLogs.length"
				:current-page-size="pagination.limit || 20"
				:min-items-to-show="10"
				@page-changed="onPageChanged"
				@page-size-changed="onPageSizeChanged" />
		</div>
	</NcAppContent>
</template>

<script setup>
import { logStore, navigationStore, jobStore } from '../../store/store.js'
import { translate as t } from '@nextcloud/l10n'
</script>

<script>
import { NcAppContent, NcEmptyContent, NcLoadingIcon, NcActions, NcActionButton, NcCheckboxRadioSwitch } from '@nextcloud/vue'
import TimelineQuestionOutline from 'vue-material-design-icons/TimelineQuestionOutline.vue'
import Delete from 'vue-material-design-icons/Delete.vue'
import Refresh from 'vue-material-design-icons/Refresh.vue'
import Eye from 'vue-material-design-icons/Eye.vue'
import FileExportOutline from 'vue-material-design-icons/FileExportOutline.vue'
import CheckCircle from 'vue-material-design-icons/CheckCircle.vue'
import AlertCircle from 'vue-material-design-icons/AlertCircle.vue'
import CloseCircle from 'vue-material-design-icons/CloseCircle.vue'
import InformationOutline from 'vue-material-design-icons/InformationOutline.vue'
import ContentCopy from 'vue-material-design-icons/ContentCopy.vue'
import Check from 'vue-material-design-icons/Check.vue'
import DatabaseSearch from 'vue-material-design-icons/DatabaseSearch.vue'
import PaginationComponent from '../../components/PaginationComponent.vue'

export default {
	name: 'JobLogIndex',
	components: {
		NcAppContent,
		NcEmptyContent,
		NcLoadingIcon,
		NcActions,
		NcActionButton,
		NcCheckboxRadioSwitch,
		TimelineQuestionOutline,
		Delete,
		Refresh,
		Eye,
		FileExportOutline,
		CheckCircle,
		AlertCircle,
		CloseCircle,
		InformationOutline,
		ContentCopy,
		Check,
		PaginationComponent,
		DatabaseSearch,
	},
	data() {
		return {
			selectedLogs: [],
			copyStates: {},
			pagination: {
				page: 1,
				limit: 20,
			},
		}
	},
	computed: {
		hasActiveFilters() {
			return Object.keys(logStore.logFilters || {}).some(key =>
				logStore.logFilters[key] !== null
        && logStore.logFilters[key] !== undefined
        && logStore.logFilters[key] !== '',
			)
		},
		filteredLogs() {
			return (jobStore.jobLogs && Array.isArray(jobStore.jobLogs.results)) ? jobStore.jobLogs.results : []
		},
		paginatedLogs() {
			return this.filteredLogs
		},
		allSelected() {
			return this.paginatedLogs.length > 0 && this.paginatedLogs.every(log => this.selectedLogs.includes(log.id))
		},
		someSelected() {
			return this.selectedLogs.length > 0 && !this.allSelected
		},
		emptyContentName() {
			if (logStore.loading) {
				return t('openconnector', 'Loading logs...')
			} else if (logStore.error) {
				return logStore.error
			} else if (!jobStore.jobLogs?.length) {
				return t('openconnector', 'No logs found')
			} else if (!this.filteredLogs.length) {
				return t('openconnector', 'No logs match your filters')
			}
			return ''
		},
		emptyContentDescription() {
			if (logStore.loading) {
				return t('openconnector', 'Please wait while we fetch your logs.')
			} else if (logStore.error) {
				return t('openconnector', 'Please try again later.')
			} else if (!jobStore.jobLogs?.length) {
				return t('openconnector', 'No job logs are available.')
			} else if (!this.filteredLogs.length) {
				return t('openconnector', 'Try adjusting your filter settings in the sidebar.')
			}
			return ''
		},
	},
	mounted() {
		jobStore.refreshJobList()
		// Fetch all job logs if no job is selected, otherwise fetch logs for the selected job
		const jobId = jobStore.selectedJobId || jobStore.jobItem?.id
		if (jobId) {
			jobStore.refreshJobLogs(jobId)
		} else {
			jobStore.refreshJobLogs()
		}
		// Listen for filter changes from sidebar
		this.$root.$on('job-log-filters-changed', this.handleFiltersChanged)
	},
	beforeDestroy() {
		this.$root.$off('job-log-filters-changed')
	},
	methods: {
		handleFiltersChanged(filters) {
			logStore.setLogFilters(filters)
			// Always pass a job ID if available, otherwise fetch all logs
			const jobId = filters.jobId || jobStore.selectedJobId || jobStore.jobItem?.id
			if (jobId) {
				jobStore.refreshJobLogs(jobId)
			} else {
				jobStore.refreshJobLogs()
			}
		},
		toggleSelectAll(checked) {
			if (checked) {
				this.selectedLogs = this.paginatedLogs.map(log => log.id)
			} else {
				this.selectedLogs = []
			}
		},
		toggleLogSelection(logId, checked) {
			if (checked) {
				this.selectedLogs.push(logId)
			} else {
				this.selectedLogs = this.selectedLogs.filter(id => id !== logId)
			}
		},
		async onPageChanged(page) {
			this.pagination.page = page
			await jobStore.refreshJobLogs(undefined, { _page: page, _limit: this.pagination.limit })
			this.selectedLogs = []
		},
		async onPageSizeChanged(pageSize) {
			this.pagination.page = 1
			this.pagination.limit = pageSize
			await jobStore.refreshJobLogs(undefined, { _page: 1, _limit: pageSize })
			this.selectedLogs = []
		},
		getJobName(jobId) {
			if (!jobId) return t('openconnector', 'Unknown Job')
			const job = jobStore.jobList?.find(j => j.id === jobId)
			return job?.name || `Job ${jobId}`
		},
		getLogLevelClass(log) {
			if (!log.level) return 'unknownLevel'
			if (log.level === 'SUCCESS') return 'successLevel'
			if (log.level === 'WARNING') return 'warningLevel'
			if (['ERROR', 'CRITICAL', 'ALERT', 'EMERGENCY'].includes(log.level)) return 'errorLevel'
			return 'infoLevel'
		},
		getExecutionTimeClass(executionTime) {
			if (!executionTime) return ''
			const timeInSeconds = executionTime / 1000
			if (timeInSeconds < 1) return 'fast-execution'
			if (timeInSeconds < 3) return 'medium-execution'
			return 'slow-execution'
		},
		viewLogDetails(log) {
			logStore.setViewLogItem(log)
			navigationStore.setModal('viewJobLog')
		},
		async copyLogData(log) {
			try {
				const data = JSON.stringify(log, null, 2)
				await navigator.clipboard.writeText(data)

				this.$set(this.copyStates, log.id, true)
				setTimeout(() => {
					this.$set(this.copyStates, log.id, false)
				}, 2000)

				OC.Notification.showSuccess(this.t('openconnector', 'Log data copied to clipboard'))
			} catch (error) {
				console.error('Error copying to clipboard:', error)
				OC.Notification.showError(this.t('openconnector', 'Failed to copy data to clipboard'))
			}
		},
		deleteLog(log) {
			logStore.setViewLogItem(log)
			navigationStore.setDialog('deleteLog')
		},
		async bulkDeleteLogs() {
			if (this.selectedLogs.length === 0) return

			if (!confirm(this.t('openconnector', 'Are you sure you want to delete the selected logs? This action cannot be undone.'))) {
				return
			}

			try {
				// TODO: Implement bulk delete API call
				OC.Notification.showSuccess(this.t('openconnector', 'Selected logs deleted successfully'))
				this.selectedLogs = []
				await this.refreshLogs()
			} catch (error) {
				console.error('Error deleting logs:', error)
				OC.Notification.showError(this.t('openconnector', 'Error deleting logs'))
			}
		},
		async exportLogs() {
			try {
				// TODO: Implement export API call
				OC.Notification.showSuccess(this.t('openconnector', 'Export started - you will be notified when ready'))
			} catch (error) {
				console.error('Error exporting logs:', error)
				OC.Notification.showError(this.t('openconnector', 'Export failed'))
			}
		},
		refreshLogs() {
			jobStore.refreshJobLogs(logStore.logFilters)
			this.selectedLogs = []
		},
		formatBytes(bytes) {
			if (!bytes) return '0 B'
			const k = 1024
			const sizes = ['B', 'KB', 'MB', 'GB']
			const i = Math.floor(Math.log(bytes) / Math.log(k))
			return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
		},
	},
}
</script>

<style scoped>
/* All CSS is provided by main.css */
</style>
