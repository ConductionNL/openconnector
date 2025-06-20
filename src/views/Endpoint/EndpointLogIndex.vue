<script setup>
import { endpointStore } from '../../store/store.js'
import { translate as t } from '@nextcloud/l10n'
</script>

<template>
	<NcAppContent>
		<div class="viewContainer">
			<div class="viewHeader">
				<h1 class="viewHeaderTitleIndented">
					{{ t('openconnector', 'Endpoint Logs') }}
				</h1>
				<p>{{ t('openconnector', 'Monitor and analyze endpoint logs and their performance') }}</p>
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
			<div v-if="endpointStore.loading" class="viewLoading">
				<NcLoadingIcon :size="64" />
				<p>{{ t('openconnector', 'Loading endpoint logs...') }}</p>
			</div>
			<NcEmptyContent v-else-if="endpointStore.error || !filteredLogs.length"
				:name="emptyContentName"
				:description="emptyContentDescription">
				<template #icon>
					<NcLoadingIcon v-if="endpointStore.loading" :size="64" />
					<TimelineQuestionOutline v-else :size="64" />
				</template>
			</NcEmptyContent>
			<div v-else class="viewTableContainer">
				<table class="viewTable callLogsTable">
					<thead>
						<tr>
							<th class="tableColumnCheckbox">
								<NcCheckboxRadioSwitch
									:checked="allSelected"
									:indeterminate="someSelected"
									@update:checked="toggleSelectAll" />
							</th>
							<th class="statusColumn">
								{{ t('openconnector', 'Status') }}
							</th>
							<th class="methodColumn">
								{{ t('openconnector', 'Method') }}
							</th>
							<th class="endpointColumn">
								{{ t('openconnector', 'Endpoint') }}
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
							class="viewTableRow callLogRow"
							:class="getLogStatusClass(log)">
							<td class="tableColumnCheckbox">
								<NcCheckboxRadioSwitch
									:checked="selectedLogs.includes(log.id)"
									@update:checked="(checked) => toggleLogSelection(log.id, checked)" />
							</td>
							<td class="statusColumn">
								<span class="statusBadge" :class="getLogStatusClass(log)">
									<CheckCircle v-if="log.statusCode >= 200 && log.statusCode < 300" :size="16" />
									<AlertCircle v-else-if="log.statusCode >= 400 && log.statusCode < 500" :size="16" />
									<CloseCircle v-else-if="log.statusCode >= 500" :size="16" />
									<InformationOutline v-else :size="16" />
									{{ log.statusCode }} {{ log.statusMessage || 'Unknown' }}
								</span>
							</td>
							<td class="methodColumn">
								<span class="methodBadge" :class="`method-${(log.method || 'unknown').toLowerCase()}`">
									{{ log.method || 'UNKNOWN' }}
								</span>
							</td>
							<td class="endpointColumn">
								<span class="truncatedUrl" :title="log.endpoint">{{ log.endpoint }}</span>
							</td>
							<td class="timestampColumn">
								<span class="createdTime">{{ new Date(log.created).toLocaleString() }}</span>
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
				:total-pages="Math.ceil(filteredLogs.length / (pagination.limit || 20))"
				:total-items="filteredLogs.length"
				:current-page-size="pagination.limit || 20"
				:min-items-to-show="10"
				@page-changed="onPageChanged"
				@page-size-changed="onPageSizeChanged" />
		</div>
	</NcAppContent>
</template>

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
import PaginationComponent from '../../components/PaginationComponent.vue'

export default {
	name: 'EndpointLogIndex',
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
		endpointStore() {
			return endpointStore
		},
		hasActiveFilters() {
			return false // TODO: Implement filter logic
		},
		filteredLogs() {
			return (endpointStore.endpointLogs && Array.isArray(endpointStore.endpointLogs)) ? endpointStore.endpointLogs : []
		},
		paginatedLogs() {
			const start = ((this.pagination.page || 1) - 1) * (this.pagination.limit || 20)
			const end = start + (this.pagination.limit || 20)
			return this.filteredLogs.slice(start, end)
		},
		allSelected() {
			return this.paginatedLogs.length > 0 && this.paginatedLogs.every(log => this.selectedLogs.includes(log.id))
		},
		someSelected() {
			return this.selectedLogs.length > 0 && !this.allSelected
		},
		emptyContentName() {
			if (endpointStore.loading) {
				return t('openconnector', 'Loading logs...')
			} else if (endpointStore.error) {
				return endpointStore.error
			} else if (!endpointStore.endpointLogs?.length) {
				return t('openconnector', 'No logs found')
			} else if (!this.filteredLogs.length) {
				return t('openconnector', 'No logs match your filters')
			}
			return ''
		},
		emptyContentDescription() {
			if (endpointStore.loading) {
				return t('openconnector', 'Please wait while we fetch your logs.')
			} else if (endpointStore.error) {
				return t('openconnector', 'Please try again later.')
			} else if (!endpointStore.endpointLogs?.length) {
				return t('openconnector', 'No endpoint logs are available.')
			} else if (!this.filteredLogs.length) {
				return t('openconnector', 'Try adjusting your filter settings in the sidebar.')
			}
			return ''
		},
	},
	mounted() {
		endpointStore.refreshEndpointLogs()
	},
	methods: {
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
		onPageChanged(page) {
			this.pagination.page = page
		},
		onPageSizeChanged(pageSize) {
			this.pagination.page = 1
			this.pagination.limit = pageSize
		},
		getLogStatusClass(log) {
			if (!log.statusCode) return 'unknownStatus'
			if (log.statusCode >= 200 && log.statusCode < 300) return 'successStatus'
			if (log.statusCode >= 400 && log.statusCode < 500) return 'clientErrorStatus'
			if (log.statusCode >= 500) return 'serverErrorStatus'
			return 'infoStatus'
		},
		viewLogDetails(log) {
			// TODO: Implement view log details modal
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
			// TODO: Implement delete log
		},
		async bulkDeleteLogs() {
			// TODO: Implement bulk delete
		},
		async exportLogs() {
			// TODO: Implement export
		},
		refreshLogs() {
			endpointStore.refreshEndpointLogs()
			this.selectedLogs = []
		},
	},
}
</script>

<style scoped>
/* All CSS is provided by main.css */
</style>
