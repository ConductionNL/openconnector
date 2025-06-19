<script setup>
import { sourceStore, navigationStore, logStore, synchronizationStore } from '../../store/store.js'
</script>

<template>
	<div class="detailContainer">
		<div id="app-content">
			<div>
				<div class="detailHeader">
					<h1 class="h1">
						{{ sourceStore.sourceItem.name }}
					</h1>

					<NcActions :primary="true" menu-name="Actions">
						<template #icon>
							<DotsHorizontal :size="20" />
						</template>
						<NcActionButton close-after-click @click="navigationStore.setModal('editSource')">
							<template #icon>
								<Pencil :size="20" />
							</template>
							Edit
						</NcActionButton>
						<NcActionButton close-after-click @click="navigationStore.setModal('testSource')">
							<template #icon>
								<Sync :size="20" />
							</template>
							Test
						</NcActionButton>
						<NcActionButton close-after-click @click="addSourceConfiguration">
							<template #icon>
								<Plus :size="20" />
							</template>
							Add Configuration
						</NcActionButton>
						<NcActionButton close-after-click @click="sourceStore.setSourceConfigurationKey(null); navigationStore.setModal('editSourceConfigurationAuthentication')">
							<template #icon>
								<Plus :size="20" />
							</template>
							Add Authentication
						</NcActionButton>
						<NcActionButton close-after-click @click="sourceStore.exportSource(sourceStore.sourceItem.id)">
							<template #icon>
								<FileExportOutline :size="20" />
							</template>
							Export source
						</NcActionButton>
						<NcActionButton close-after-click @click="navigationStore.setDialog('deleteSource')">
							<template #icon>
								<TrashCanOutline :size="20" />
							</template>
							Delete
						</NcActionButton>
					</NcActions>
				</div>
				<span>{{ sourceStore.sourceItem?.description || '-' }}</span>

				<div class="detailGrid">
					<div class="gridContent">
						<b>id:</b>
						<p>{{ sourceStore.sourceItem?.id || sourceStore.sourceItem?.uuid || '-' }}</p>
					</div>
					<div class="gridContent">
						<b>location:</b>
						<p>{{ sourceStore.sourceItem?.location || '-' }}</p>
					</div>
					<div class="gridContent">
						<b>version:</b>
						<p>{{ sourceStore.sourceItem?.version || '-' }}</p>
					</div>
					<div class="gridContent">
						<b>type:</b>
						<p>{{ sourceStore.sourceItem?.type || '-' }}</p>
					</div>
				</div>
				<div class="tabContainer">
					<BTabs content-class="mt-3" justified>
						<BTab title="Configurations">
							<div v-if="Object.keys(configuration)?.length">
								<NcListItem v-for="(value, key, i) in configuration"
									:key="`${key}${i}`"
									:name="key"
									:bold="false"
									:force-display-actions="true"
									:active="sourceStore.sourceConfigurationKey === key"
									@click="setActiveSourceConfigurationKey(key)">
									<template #icon>
										<FileCogOutline
											:class="sourceStore.sourceConfigurationKey === key && 'selectedZaakIcon'"
											disable-menu
											:size="44" />
									</template>
									<template #subname>
										{{ value }}
									</template>
									<template #actions>
										<NcActionButton close-after-click @click="editSourceConfiguration(key)">
											<template #icon>
												<Pencil :size="20" />
											</template>
											Edit
										</NcActionButton>
										<NcActionButton close-after-click @click="deleteSourceConfiguration(key)">
											<template #icon>
												<Delete :size="20" />
											</template>
											Delete
										</NcActionButton>
									</template>
								</NcListItem>
							</div>
							<div v-if="!Object.keys(configuration)?.length" class="tabPanel">
								No configurations found
							</div>
						</BTab>
						<BTab title="Authentication">
							<div v-if="Object.keys(configurationAuthentication)?.length">
								<NcListItem v-for="(value, key, i) in configurationAuthentication"
									:key="`${key}${i}`"
									:name="key"
									:bold="false"
									:force-display-actions="true"
									:active="sourceStore.sourceConfigurationKey === key">
									<template #icon>
										<FileCogOutline
											:class="sourceStore.sourceConfigurationKey === key && 'selectedZaakIcon'"
											disable-menu
											:size="44" />
									</template>
									<template #subname>
										{{ value }}
									</template>
									<template #actions>
										<NcActionButton close-after-click @click="sourceStore.setSourceConfigurationKey(key); navigationStore.setModal('editSourceConfigurationAuthentication')">
											<template #icon>
												<Pencil :size="20" />
											</template>
											Edit
										</NcActionButton>
										<NcActionButton close-after-click @click="sourceStore.setSourceConfigurationKey(key); navigationStore.setModal('deleteSourceConfigurationAuthentication')">
											<template #icon>
												<Delete :size="20" />
											</template>
											Delete
										</NcActionButton>
									</template>
								</NcListItem>
							</div>
							<div v-if="!Object.keys(configurationAuthentication)?.length" class="tabPanel">
								No authentications found
							</div>
						</BTab>
						<BTab title="Synchronizations">
							<div v-if="linkedSynchronizations?.length">
								<NcListItem v-for="(value) in linkedSynchronizations"
									:key="value.id"
									:name="value.name"
									:bold="false"
									:force-display-actions="true">
									<template #icon>
										<VectorPolylinePlus disable-menu
											:size="44" />
									</template>
									<template #subname>
										{{ value.description }}
									</template>
									<template #actions>
										<NcActionButton close-after-click @click="synchronizationStore.setSynchronizationItem(value); navigationStore.setSelected('synchronizations')">
											<template #icon>
												<EyeOutline :size="20" />
											</template>
											View
										</NcActionButton>
										<NcActionButton close-after-click @click="synchronizationStore.setSynchronizationItem(value); navigationStore.setModal('editSynchronization')">
											<template #icon>
												<Pencil :size="20" />
											</template>
											Edit
										</NcActionButton>
										<NcActionButton close-after-click @click="synchronizationStore.setSynchronizationItem(value); navigationStore.setDialog('deleteSynchronization')">
											<template #icon>
												<Delete :size="20" />
											</template>
											Delete
										</NcActionButton>
									</template>
								</NcListItem>
							</div>
							<div v-else class="tabPanel">
								No synchronizations found
							</div>
						</BTab>
						<BTab title="Logs">
							<div v-if="sourceStore.sourceLogs?.length">
								<NcListItem v-for="(log, i) in sourceStore.sourceLogs"
									:key="log.id + i"
									:class="checkIfStatusIsOk(log.statusCode) ? 'okStatus' : 'errorStatus'"
									:name="`${log.statusMessage} ${log.response?.responseTime ? `(response time: ${(log.response.responseTime / 1000).toFixed(3)} seconds)` : ''}`"
									:bold="false"
									:counter-number="log.statusCode"
									:force-display-actions="true"
									:active="logStore.activeLogKey === `sourceLog-${log.id}`"
									@click="setActiveSourceLog(log.id)">
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
											View
										</NcActionButton>
									</template>
								</NcListItem>
							</div>
							<div v-if="!sourceStore.sourceLogs?.length" class="tabPanel">
								No logs found
							</div>
						</BTab>
					</BTabs>
				</div>
			</div>
		</div>
	</div>
</template>

<script>
// Components
import { BTabs, BTab } from 'bootstrap-vue'
import { NcActions, NcActionButton, NcListItem } from '@nextcloud/vue'

// Icons
import DotsHorizontal from 'vue-material-design-icons/DotsHorizontal.vue'
import Pencil from 'vue-material-design-icons/Pencil.vue'
import TrashCanOutline from 'vue-material-design-icons/TrashCanOutline.vue'
import Sync from 'vue-material-design-icons/Sync.vue'
import TimelineQuestionOutline from 'vue-material-design-icons/TimelineQuestionOutline.vue'
import Plus from 'vue-material-design-icons/Plus.vue'
import EyeOutline from 'vue-material-design-icons/EyeOutline.vue'
import FileCogOutline from 'vue-material-design-icons/FileCogOutline.vue'
import Delete from 'vue-material-design-icons/Delete.vue'
import FileExportOutline from 'vue-material-design-icons/FileExportOutline.vue'
import VectorPolylinePlus from 'vue-material-design-icons/VectorPolylinePlus.vue'

export default {
	name: 'SourceDetails',
	components: {
		NcActions,
		NcActionButton,
		BTabs,
		BTab,
		NcListItem,
		// Icons
		DotsHorizontal,
		Pencil,
		TrashCanOutline,
		Sync,
	},
	computed: {
		/**
		 * Returns the configuration without the authentication configuration
		 */
		configuration() {
			const config = sourceStore.sourceItem?.configuration || {}
			const { authentication, ...configWithoutAuth } = config
			return configWithoutAuth
		},
		/**
		 * Returns the authentication configuration
		 */
		configurationAuthentication() {
			return sourceStore.sourceItem?.configuration?.authentication || {}
		},
		/**
		 * returns a filtered list of synchronizations which use this source
		 */
		linkedSynchronizations() {
			return synchronizationStore.synchronizationList?.filter((item) => item.sourceId.toString() === sourceStore.sourceItem?.id?.toString()) || []
		},
	},
	mounted() {
		this.refreshSourceLogs()
		synchronizationStore.refreshSynchronizationList()
	},
	methods: {
		deleteSourceConfiguration(key) {
			sourceStore.setSourceConfigurationKey(key)
			navigationStore.setModal('deleteSourceConfiguration')
		},
		editSourceConfiguration(key) {
			sourceStore.setSourceConfigurationKey(key)
			navigationStore.setModal('editSourceConfiguration')
		},
		addSourceConfiguration() {
			sourceStore.setSourceConfigurationKey(null)
			navigationStore.setModal('editSourceConfiguration')
		},
		viewLog(log) {
			logStore.setViewLogItem(log)
			navigationStore.setModal('viewSourceLog')
		},
		setActiveSourceConfigurationKey(sourceConfigurationKey) {
			if (sourceStore.sourceConfigurationKey === sourceConfigurationKey) {
				sourceStore.setSourceConfigurationKey(false)
			} else { sourceStore.setSourceConfigurationKey(sourceConfigurationKey) }
		},
		setActiveSourceLog(sourceLogId) {
			if (logStore.activeLogKey === `sourceLog-${sourceLogId}`) {
				logStore.setActiveLogKey(null)
			} else {
				logStore.setActiveLogKey(`sourceLog-${sourceLogId}`)
			}
		},
		refreshSourceLogs() {
			sourceStore.refreshSourceLogs()
		},
		checkIfStatusIsOk(statusCode) {
			if (statusCode > 199 && statusCode < 300) {
				return true
			}
			return false
		},
	},
}
</script>

<style scoped>
.h1 {
	display: block !important;
	font-size: 2em !important;
	margin-block-start: 0.67em !important;
	margin-block-end: 0.67em !important;
	margin-inline-start: 0 !important;
	margin-inline-end: 0 !important;
	font-weight: bold !important;
	unicode-bidi: isolate !important;
}

.okStatus * .counter-bubble__counter {
	background-color: #69b090;
	color: white
}

.errorStatus * .counter-bubble__counter {
	background-color: #dd3c49;
	color: white
}

.gridContent {
	display: flex;
	gap: 10px;
}
</style>
