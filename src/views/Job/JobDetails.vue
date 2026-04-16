<script setup>
import { translate as t } from '@nextcloud/l10n'
import { jobStore, navigationStore, logStore } from '../../store/store.js'
</script>

<template>
	<div class="detailContainer">
		<div id="app-content">
			<div>
				<div class="detailHeader">
					<h1 class="h1">
						{{ jobStore.jobItem.name }}
					</h1>

					<NcActions :primary="true" :menu-name="t('openconnector', 'Actions')">
						<template #icon>
							<DotsHorizontal :size="20" />
						</template>
						<NcActionButton close-after-click @click="navigationStore.setModal('editJob')">
							<template #icon>
								<Pencil :size="20" />
							</template>
							{{ t('openconnector', 'Edit') }}
						</NcActionButton>
						<NcActionButton close-after-click @click="addJobArgument()">
							<template #icon>
								<Plus :size="20" />
							</template>
							{{ t('openconnector', 'Add Argument') }}
						</NcActionButton>
						<NcActionButton close-after-click @click="navigationStore.setModal('testJob')">
							<template #icon>
								<Update :size="20" />
							</template>
							{{ t('openconnector', 'Test') }}
						</NcActionButton>
						<NcActionButton close-after-click @click="navigationStore.setModal('runJob')">
							<template #icon>
								<Play :size="20" />
							</template>
							{{ t('openconnector', 'Run') }}
						</NcActionButton>
						<NcActionButton close-after-click @click="refreshJobLogs()">
							<template #icon>
								<Sync :size="20" />
							</template>
							{{ t('openconnector', 'Refresh Logs') }}
						</NcActionButton>
						<NcActionButton close-after-click @click="jobStore.exportJob(jobStore.jobItem.id)">
							<template #icon>
								<FileExportOutline :size="20" />
							</template>
							{{ t('openconnector', 'Export job') }}
						</NcActionButton>
						<NcActionButton close-after-click @click="navigationStore.setDialog('deleteJob')">
							<template #icon>
								<TrashCanOutline :size="20" />
							</template>
							{{ t('openconnector', 'Delete') }}
						</NcActionButton>
					</NcActions>
				</div>
				<span>{{ jobStore.jobItem.description }}</span>

				<div class="detailGrid">
					<div class="gridContent gridFullWidth">
						<b>{{ t('openconnector', 'ID') }}:</b>
						<p>{{ jobStore.jobItem?.id || '-' }}</p>
					</div>
					<div class="gridContent">
						<b>{{ t('openconnector', 'Status') }}:</b>
						<p>{{ jobStore.jobItem?.status || '-' }}</p>
					</div>
					<div class="gridContent">
						<b>{{ t('openconnector', 'Version') }}:</b>
						<p>{{ jobStore.jobItem?.version || '-' }}</p>
					</div>
					<div class="gridContent">
						<b>{{ jobStore.jobItem?.isEnabled ? t('openconnector', 'Enabled') : t('openconnector', 'Disabled') }}</b>
						<NcUserStatusIcon :class="!jobStore.jobItem?.isEnabled && 'jobStatusDisabled'" :status="'online'" />
					</div>
					<div class="gridContent">
						<b>{{ t('openconnector', 'Job Class') }}:</b>
						<p>{{ jobStore.jobItem?.jobClass || '-' }}</p>
					</div>
					<div class="gridContent">
						<b>{{ t('openconnector', 'Interval') }}:</b>
						<p>{{ jobStore.jobItem?.interval || '-' }}</p>
					</div>
					<div class="gridContent">
						<b>{{ t('openconnector', 'Execution Time') }}:</b>
						<p>{{ jobStore.jobItem?.executionTime || '-' }}</p>
					</div>
					<div class="gridContent">
						<b>{{ t('openconnector', 'Time Sensitive') }}:</b>
						<p>{{ jobStore.jobItem?.timeSensitive || '-' }}</p>
					</div>
					<div class="gridContent">
						<b>{{ t('openconnector', 'Allow Parallel Runs') }}:</b>
						<p>{{ jobStore.jobItem?.allowParallelRuns || '-' }}</p>
					</div>
					<div class="gridContent">
						<b>{{ t('openconnector', 'Single Run') }}:</b>
						<p>{{ jobStore.jobItem?.singleRun || '-' }}</p>
					</div>
					<div class="gridContent">
						<b>{{ t('openconnector', 'Next Run') }}:</b>
						<p>
							{{ getValidISOstring(jobStore.jobItem.nextRun) ? new Date(jobStore.jobItem.nextRun).toLocaleString() : 'N/A' }}
						</p>
					</div>
					<div class="gridContent">
						<b>{{ t('openconnector', 'Last Run') }}:</b>
						<p>
							{{ getValidISOstring(jobStore.jobItem.lastRun) ? new Date(jobStore.jobItem.lastRun).toLocaleString() : 'N/A' }}
						</p>
					</div>
				</div>

				<div class="tabContainer">
					<BTabs content-class="mt-3" justified>
						<BTab :title="t('openconnector', 'Job Arguments')">
							<div class="tabButtonsContainer">
								<NcButton type="primary"
									class="fullWidthButton"
									:aria-label="t('openconnector', 'Add Argument')"
									@click="addJobArgument">
									<template #icon>
										<Plus :size="20" />
									</template>
									{{ t('openconnector', 'Add Argument') }}
								</NcButton>
							</div>
							<div v-if="jobStore.jobItem?.arguments !== null && Object.keys(jobStore.jobItem?.arguments).length > 0">
								<NcListItem v-for="(value, key, i) in jobStore.jobItem?.arguments"
									:key="`${key}${i}`"
									:name="key"
									:bold="false"
									:force-display-actions="true"
									:active="jobStore.jobArgumentKey === key"
									@click="setActiveJobArgumentKey(key)">
									<template #icon>
										<SitemapOutline
											:class="jobStore.jobArgumentKey === key && 'selectedZaakIcon'"
											disable-menu
											:size="44" />
									</template>
									<template #subname>
										{{ value }}
									</template>
									<template #actions>
										<NcActionButton close-after-click @click="editJobArgument(key)">
											<template #icon>
												<Pencil :size="20" />
											</template>
											{{ t('openconnector', 'Edit') }}
										</NcActionButton>
										<NcActionButton close-after-click @click="deleteJobArgument(key)">
											<template #icon>
												<Delete :size="20" />
											</template>
											{{ t('openconnector', 'Delete') }}
										</NcActionButton>
									</template>
								</NcListItem>
							</div>
							<div v-if="jobStore.jobItem?.arguments === null || !Object.keys(jobStore.jobItem?.arguments).length" class="tabPanel">
								{{ t('openconnector', 'No arguments found for this job') }}
							</div>
						</BTab>
						<BTab :title="t('openconnector', 'Logs')">
							<div v-if="jobStore.jobLogs?.length">
								<NcListItem v-for="(log, i) in jobStore.jobLogs"
									:key="log.id + i"
									:class="getLevelColor(log.level)"
									:name="log.message"
									:bold="false"
									:counter-number="log.level"
									:force-display-actions="true"
									:active="logStore.activeLogKey === `jobLog-${log.id}`"
									@click="setActiveJobLog(log.id)">
									>
									<template #icon>
										<TimelineQuestionOutline disable-menu
											:size="44" />
									</template>
									<template #subname>
										{{ new Date(log.created).toLocaleString() }}
									</template>
									<template #actions>
										<NcActionButton close-after-click @click="viewLog(log)">
											<template #icon>
												<EyeOutline :size="20" />
											</template>
											{{ t('openconnector', 'View') }}
										</NcActionButton>
									</template>
								</NcListItem>
							</div>
							<div v-if="!jobStore.jobLogs?.length" class="tabPanel">
								{{ t('openconnector', 'No logs found') }}
							</div>
						</BTab>
					</BTabs>
				</div>
			</div>
		</div>
	</div>
</template>

<script>
import { NcActions, NcActionButton, NcListItem, NcUserStatusIcon, NcButton } from '@nextcloud/vue'
import { BTabs, BTab } from 'bootstrap-vue'
import DotsHorizontal from 'vue-material-design-icons/DotsHorizontal.vue'
import Pencil from 'vue-material-design-icons/Pencil.vue'
import TrashCanOutline from 'vue-material-design-icons/TrashCanOutline.vue'
import TimelineQuestionOutline from 'vue-material-design-icons/TimelineQuestionOutline.vue'
import Plus from 'vue-material-design-icons/Plus.vue'
import Delete from 'vue-material-design-icons/Delete.vue'
import SitemapOutline from 'vue-material-design-icons/SitemapOutline.vue'
import Update from 'vue-material-design-icons/Update.vue'
import Sync from 'vue-material-design-icons/Sync.vue'
import EyeOutline from 'vue-material-design-icons/EyeOutline.vue'
import Play from 'vue-material-design-icons/Play.vue'
import FileExportOutline from 'vue-material-design-icons/FileExportOutline.vue'

import getValidISOstring from '../../services/getValidISOstring.js'

export default {
	name: 'JobDetails',
	components: {
		NcActions,
		NcActionButton,
		DotsHorizontal,
		Pencil,
		TrashCanOutline,
		Update,
		BTabs,
		BTab,
		NcListItem,
		Plus,
		NcButton,
	},
	mounted() {
		jobStore.refreshJobLogs(jobStore.jobItem.id)
	},
	methods: {
		deleteJobArgument(key) {
			jobStore.setJobArgumentKey(key)
			navigationStore.setModal('deleteJobArgument')
		},
		editJobArgument(key) {
			jobStore.setJobArgumentKey(key)
			navigationStore.setModal('editJobArgument')
		},
		addJobArgument() {
			jobStore.setJobArgumentKey(null)
			navigationStore.setModal('editJobArgument')
		},
		setActiveJobArgumentKey(jobArgumentKey) {
			if (jobStore.jobArgumentKey === jobArgumentKey) {
				jobStore.setJobArgumentKey(false)
			} else { jobStore.setJobArgumentKey(jobArgumentKey) }
		},
		setActiveJobLog(jobLogId) {
			if (logStore.activeLogKey === `jobLog-${jobLogId}`) {
				logStore.setActiveLogKey(null)
			} else {
				logStore.setActiveLogKey(`jobLog-${jobLogId}`)
			}
		},
		viewLog(log) {
			logStore.setViewLogItem(log)
			navigationStore.setModal('viewJobLog')
		},
		refreshJobLogs() {
			jobStore.refreshJobLogs(jobStore.jobItem.id)
		},
		getLevelColor(level) {
			switch (level) {
			case 'SUCCESS':
				return 'successLevel'
			case 'INFO':
				return 'infoLevel'
			case 'NOTICE':
				return 'noticeLevel'
			case 'WARNING':
				return 'warningLevel'
			case 'ERROR':
				return 'errorLevel'
			case 'CRITICAL':
				return 'criticalLevel'
			case 'ALERT':
				return 'alertLevel'
			case 'EMERGENCY':
				return 'emergencyLevel'
			case 'DEBUG':
				return 'debugLevel'
			default:
				return 'debugLevel'
			}
		},

	},
}
</script>

<style scoped>
.successLevel * .counter-bubble__counter {
	background-color: var(--OC-color-status-background-success);
	color: var(--OC-color-status-success);
}

.errorLevel * .counter-bubble__counter {
	background-color: var(--OC-color-status-background-error);
	color: var(--OC-color-status-error);
}

.noticeLevel * .counter-bubble__counter {
	background-color: var(--OC-color-status-background-notice);
	color: var(--OC-color-status-notice);
}

.warningLevel * .counter-bubble__counter {
	background-color: var(--OC-color-status-background-warning);
	color: var(--OC-color-status-warning);
}

.infoLevel * .counter-bubble__counter {
	background-color: var(--OC-color-status-background-info);
	color: var(--OC-color-status-info);
}

.criticalLevel * .counter-bubble__counter {
	background-color: var(--OC-color-status-background-critical);
	color: var(--OC-color-status-critical);
}

.alertLevel * .counter-bubble__counter {
	background-color: var(--OC-color-status-background-alert);
	color: var(--OC-color-status-alert);
}

.emergencyLevel * .counter-bubble__counter {
	background-color: var(--OC-color-status-background-emergency);
	color: var(--OC-color-status-emergency);
}

.debugLevel * .counter-bubble__counter {
	background-color: var(--OC-color-status-background-debug);
	color: var(--OC-color-status-debug);
}

.gridContent p {
	white-space: normal;
	overflow-wrap: break-word;
	word-wrap: break-word;
}

</style>
