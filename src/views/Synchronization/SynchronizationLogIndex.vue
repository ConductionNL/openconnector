<script setup>
import { synchronizationStore, navigationStore } from '../../store/store.js'
import { translate as t } from '@nextcloud/l10n'
</script>

<template>
	<NcAppContent>
		<div class="viewContainer">
			<div class="viewHeader">
				<h1 class="viewHeaderTitleIndented">
					{{ t('openconnector', 'Synchronization Logs') }}
				</h1>
				<p>{{ t('openconnector', 'Monitor and analyze synchronization logs and their performance') }}</p>
			</div>
			<div class="viewActionsBar">
				<div class="viewInfo">
					<span class="viewTotalCount">
						{{ t('openconnector', 'Showing {showing} of {total} logs', { showing: paginatedLogs.length, total: filteredLogs.length }) }}
					</span>
					<span v-if="selectedLogs.length > 0" class="viewIndicator">
						({{ t('openconnector', '{count} selected', { count: selectedLogs.length }) }})
					</span>
				</div>
				<div class="viewActions">
					<NcActions :force-name="true" :inline="3" menu-name="Actions">
						<NcActionButton v-if="selectedLogs.length > 0"
							type="error"
							:disabled="deletingLogs"
							close-after-click
							@click="bulkDeleteLogs">
							<template #icon>
								<NcLoadingIcon v-if="deletingLogs" :size="20" />
								<Delete v-else :size="20" />
							</template>
							{{ deletingLogs ? t('openconnector', 'Deleting...') : t('openconnector', 'Delete ({count})', { count: selectedLogs.length }) }}
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
			<div v-if="synchronizationStore.loading" class="viewLoading">
				<NcLoadingIcon :size="64" />
				<p>{{ t('openconnector', 'Loading synchronization logs...') }}</p>
			</div>
			<NcEmptyContent v-else-if="synchronizationStore.error || !filteredLogs.length"
				:name="emptyContentName"
				:description="emptyContentDescription">
				<template #icon>
					<NcLoadingIcon v-if="synchronizationStore.loading" :size="64" />
					<TimelineQuestionOutline v-else :size="64" />
				</template>
			</NcEmptyContent>
			<div v-else class="viewTableContainer">
				<table class="viewTable">
					<thead>
						<tr>
							<th class="tableColumnCheckbox">
								<NcCheckboxRadioSwitch
									:checked="allSelected"
									:indeterminate="someSelected"
									@update:checked="toggleSelectAll" />
							</th>
							<th>{{ t('openconnector', 'Status') }}</th>
							<th>{{ t('openconnector', 'Synchronization') }}</th>
							<th>{{ t('openconnector', 'Details') }}</th>
							<th>{{ t('openconnector', 'Execution Time') }}</th>
							<th>{{ t('openconnector', 'Created') }}</th>
							<th class="tableColumnActions">
								{{ t('openconnector', 'Actions') }}
							</th>
						</tr>
					</thead>
					<tbody>
						<tr v-for="log in paginatedLogs"
							:key="log.id"
							class="viewTableRow"
							:class="[getLogStatusClass(log), { viewTableRowSelected: selectedLogs.includes(log.id) }]">
							<td class="tableColumnCheckbox">
								<NcCheckboxRadioSwitch
									:checked="selectedLogs.includes(log.id)"
									@update:checked="(checked) => toggleLogSelection(log.id, checked)" />
							</td>
							<td>
								<span class="statusBadge" :class="getLogStatusClass(log)">
									<CheckCircle v-if="log.message === 'Success'" :size="16" />
									<CloseCircle v-else :size="16" />
									{{ log.message === 'Success' ? t('openconnector', 'Success') : t('openconnector', 'Error') }}
								</span>
							</td>
							<td>{{ getSynchronizationName(log.synchronizationId) }}</td>
							<td>
								<span v-if="log.message === 'Success'">
									{{ getObjectsSummary(log) }}
								</span>
								<span v-else>{{ log.message }}</span>
							</td>
							<td class="responseTimeColumn">
								<span :class="getExecutionTimeClass(log)">
									{{ (getExecutionTime(log) / 1000).toFixed(3) }}s
								</span>
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
											<ContentCopy :size="20" />
										</template>
										{{ t('openconnector', 'Copy Data') }}
									</NcActionButton>
									<NcActionButton :disabled="deletingLogs" close-after-click @click="deleteLog(log)">
										<template #icon>
											<NcLoadingIcon v-if="deletingLogs" :size="20" />
											<Delete v-else :size="20" />
										</template>
										{{ deletingLogs ? t('openconnector', 'Deleting...') : t('openconnector', 'Delete') }}
									</NcActionButton>
								</NcActions>
							</td>
						</tr>
					</tbody>
				</table>
			</div>
			<PaginationComponent
				v-if="filteredLogs.length > 0 || synchronizationStore.synchronizationLogs?.total > 0"
				:current-page="synchronizationStore.synchronizationLogs?.page || pagination.page || 1"
				:total-pages="synchronizationStore.synchronizationLogs?.pages || Math.ceil((synchronizationStore.synchronizationLogs?.total || 0) / (pagination.limit || 20))"
				:total-items="synchronizationStore.synchronizationLogs?.total || filteredLogs.length"
				:current-page-size="pagination.limit || 20"
				:min-items-to-show="0"
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
import ContentCopy from 'vue-material-design-icons/ContentCopy.vue'
import CheckCircle from 'vue-material-design-icons/CheckCircle.vue'
import CloseCircle from 'vue-material-design-icons/CloseCircle.vue'
import PaginationComponent from '../../components/PaginationComponent.vue'

export default {
	name: 'SynchronizationLogIndex',
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
		ContentCopy,
		CheckCircle,
		CloseCircle,
		PaginationComponent,
	},
	data() {
		return {
			synchronizationStore,
			navigationStore,
			selectedLogs: [],
			deletingLogs: false,
			pagination: {
				page: 1,
				limit: 20,
			},
		}
	},
	computed: {
		filteredLogs() {
			if (!this.synchronizationStore.synchronizationLogs) return []
			if (Array.isArray(this.synchronizationStore.synchronizationLogs.results)) {
				return this.synchronizationStore.synchronizationLogs.results
			}
			return this.synchronizationStore.synchronizationLogs
		},
		paginatedLogs() {
			return this.filteredLogs
		},
		allSelected() {
			return this.filteredLogs.length > 0 && this.filteredLogs.every(log => this.selectedLogs.includes(log.id))
		},
		someSelected() {
			return this.selectedLogs.length > 0 && !this.allSelected
		},
		emptyContentName() {
			if (this.synchronizationStore.loading) {
				return t('openconnector', 'Loading logs...')
			} else if (this.synchronizationStore.error) {
				return this.synchronizationStore.error
			} else if (!this.synchronizationStore.synchronizationLogs?.length) {
				return t('openconnector', 'No logs found')
			}
			return ''
		},
		emptyContentDescription() {
			if (this.synchronizationStore.loading) {
				return t('openconnector', 'Please wait while we fetch your logs.')
			} else if (this.synchronizationStore.error) {
				return t('openconnector', 'Please try again later.')
			} else if (!this.synchronizationStore.synchronizationLogs?.length) {
				return t('openconnector', 'No synchronization logs are available.')
			}
			return ''
		},
	},
	mounted() {
		this.loadLogs()
	},
	methods: {
		toggleSelectAll(checked) {
			if (checked) {
				this.selectedLogs = this.filteredLogs.map(log => log.id)
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
		async loadLogs() {
			await this.synchronizationStore.refreshSynchronizationLogs({
				page: this.pagination.page,
				limit: this.pagination.limit,
			})
		},
		async onPageChanged(page) {
			this.pagination.page = page
			await this.loadLogs()
		},
		async onPageSizeChanged(pageSize) {
			this.pagination.page = 1
			this.pagination.limit = pageSize
			await this.loadLogs()
		},
		getLogStatusClass(log) {
			if (log.message === 'Success') return 'successStatus'
			return 'clientErrorStatus' // For non-success messages, treat as error
		},
		getExecutionTime(log) {
			return log.result?.timing?.total_ms || log.executionTime || 0
		},
		getExecutionTimeClass(log) {
			const ms = this.getExecutionTime(log)
			if (ms < 5000) return 'fast-response' // Below 5 seconds is good
			if (ms < 30000) return 'medium-response' // Under 30 seconds is average
			return 'slow-response' // Above 30 seconds is bad
		},
		viewLogDetails(log) {
			// TODO: Implement view log details modal
		},
		async copyLogData(log) {
			try {
				const data = JSON.stringify(log, null, 2)
				await navigator.clipboard.writeText(data)
				OC.Notification.showSuccess(t('openconnector', 'Log data copied to clipboard'))
			} catch (error) {
				console.error('Error copying to clipboard:', error)
				OC.Notification.showError(t('openconnector', 'Failed to copy data to clipboard'))
			}
		},
		async deleteLog(log) {
			this.deletingLogs = true
			try {
				const result = await this.synchronizationStore.deleteSynchronizationLog(log.id.toString())
				if (result.response.ok) {
					OC.Notification.showSuccess(t('openconnector', 'Log deleted successfully'))
					// Clear selection if the deleted log was selected
					this.selectedLogs = this.selectedLogs.filter(id => id !== log.id)
					// Check if we need to adjust pagination after deleting
					let targetPage = this.pagination.page
					if (this.filteredLogs.length === 1 && this.pagination.page > 1) {
						// If this was the last item on the current page, go to previous page
						targetPage = this.pagination.page - 1
						this.pagination.page = targetPage
					}

					// Force refresh the table with adjusted pagination
					await this.synchronizationStore.refreshSynchronizationLogs({
						page: targetPage,
						limit: this.pagination.limit,
					})
				} else {
					const errorData = await result.response.json()
					OC.Notification.showError(t('openconnector', 'Failed to delete log: {error}', { error: errorData.error || 'Unknown error' }))
				}
			} catch (error) {
				OC.Notification.showError(t('openconnector', 'Failed to delete log'))
			} finally {
				this.deletingLogs = false
			}
		},
		async bulkDeleteLogs() {
			if (this.selectedLogs.length === 0) {
				OC.Notification.showError(t('openconnector', 'No logs selected'))
				return
			}

			this.deletingLogs = true
			try {
				const totalLogs = this.selectedLogs.length
				let deletedCount = 0
				const errors = []

				// Delete each log individually
				for (const logId of this.selectedLogs) {
					try {
						const result = await this.synchronizationStore.deleteSynchronizationLog(logId.toString())
						if (result.response.ok) {
							deletedCount++
						} else {
							const errorData = await result.response.json()
							errors.push(`Log ${logId}: ${errorData.error || 'Unknown error'}`)
						}
					} catch (error) {
						console.error(`Error deleting log ${logId}:`, error)
						errors.push(`Log ${logId}: Network error`)
					}
				}

				// Show results
				if (deletedCount === totalLogs) {
					OC.Notification.showSuccess(t('openconnector', 'All {count} selected logs deleted successfully', { count: deletedCount }))
				} else if (deletedCount > 0) {
					OC.Notification.showError(t('openconnector', 'Deleted {deleted} of {total} logs. Errors: {errors}', {
						deleted: deletedCount,
						total: totalLogs,
						errors: errors.slice(0, 3).join(', ') + (errors.length > 3 ? '...' : ''),
					}))
				} else {
					OC.Notification.showError(t('openconnector', 'Failed to delete any logs: {errors}', {
						errors: errors.slice(0, 3).join(', ') + (errors.length > 3 ? '...' : ''),
					}))
				}

				// After deleting multiple logs, we might need to adjust pagination
				// If we deleted all logs on current page, go to previous page
				const remainingLogsOnPage = this.filteredLogs.length - deletedCount
				let targetPage = this.pagination.page

				if (remainingLogsOnPage === 0 && this.pagination.page > 1) {
					targetPage = this.pagination.page - 1
					this.pagination.page = targetPage
				}

				// Force refresh the table with adjusted pagination
				await this.synchronizationStore.refreshSynchronizationLogs({
					page: targetPage,
					limit: this.pagination.limit,
				})
			} finally {
				this.deletingLogs = false
				// Ensure selection is cleared even if refresh fails
				this.selectedLogs = []
			}
		},
		async exportLogs() {
			try {
				const result = await this.synchronizationStore.exportSynchronizationLogs()
				if (result.response.ok) {
					OC.Notification.showSuccess(t('openconnector', 'Logs exported successfully'))
				} else {
					OC.Notification.showError(t('openconnector', 'Failed to export logs'))
				}
			} catch (error) {
				console.error('Error exporting logs:', error)
				OC.Notification.showError(t('openconnector', 'Failed to export logs'))
			}
		},
		async refreshLogs() {
			try {
				// Force a fresh reload of the logs
				await this.loadLogs()
				// Clear any selections after refresh
				this.selectedLogs = []
			} catch (error) {
				OC.Notification.showError(t('openconnector', 'Failed to refresh logs'))
			}
		},
		getSynchronizationName(synchronizationId) {
			if (!synchronizationId) return t('openconnector', 'Unknown')
			// TODO: Get synchronization name from store if available
			return `Sync ${synchronizationId}`
		},
		getObjectsSummary(log) {
			const o = log.result?.objects || {}
			return `Found: ${o.found ?? 0}, Created: ${o.created ?? 0}, Updated: ${o.updated ?? 0}, Deleted: ${o.deleted ?? 0}, Skipped: ${o.skipped ?? 0}, Invalid: ${o.invalid ?? 0}`
		},
	},
}
</script>

<style scoped>
/* All CSS is provided by main.css */
</style>
