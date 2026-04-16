<script setup>
import { translate as t } from '@nextcloud/l10n'
import { synchronizationStore, navigationStore, searchStore } from '../../store/store.js'
</script>

<template>
	<NcAppContentList>
		<ul>
			<div class="listHeader">
				<NcTextField
					:value.sync="searchStore.search"
					:show-trailing-button="searchStore.search !== ''"
					:label="t('openconnector', 'Search')"
					class="searchField"
					trailing-button-icon="close"
					@trailing-button-click="searchStore.clearSearch()">
					<Magnify :size="20" />
				</NcTextField>
				<NcActions>
					<NcActionButton close-after-click @click="synchronizationStore.refreshSynchronizationList()">
						<template #icon>
							<Refresh :size="20" />
						</template>
						{{ t('openconnector', 'Refresh') }}
					</NcActionButton>
					<NcActionButton close-after-click @click="synchronizationStore.setSynchronizationItem(null); navigationStore.setModal('editSynchronization')">
						<template #icon>
							<Plus :size="20" />
						</template>
						{{ t('openconnector', 'Add synchronization') }}
					</NcActionButton>
					<NcActionButton close-after-click @click="navigationStore.setModal('importFile')">
						<template #icon>
							<FileImportOutline :size="20" />
						</template>
						{{ t('openconnector', 'Import') }}
					</NcActionButton>
				</NcActions>
			</div>
			<div v-if="synchronizationStore.synchronizationList && synchronizationStore.synchronizationList.length > 0">
				<NcListItem v-for="(synchronization, i) in synchronizationStore.synchronizationList.filter(synchronization => searchStore.search === '' || synchronization.name.toLowerCase().includes(searchStore.search.toLowerCase()))"
					:key="`${synchronization}${i}`"
					:name="synchronization.name"
					:active="synchronizationStore.synchronizationItem?.id === synchronization?.id"
					:force-display-actions="true"
					@click="synchronizationStore.setSynchronizationItem(synchronization)">
					<template #icon>
						<VectorPolylinePlus :class="synchronizationStore.synchronizationItem?.id === synchronization.id && 'selectedSynchronizationIcon'"
							disable-menu
							:size="44" />
					</template>
					<template #subname>
						{{ synchronization?.description }}
					</template>
					<template #actions>
						<NcActionButton close-after-click @click="synchronizationStore.setSynchronizationItem(synchronization); navigationStore.setModal('editSynchronization')">
							<template #icon>
								<Pencil />
							</template>
							{{ t('openconnector', 'Edit') }}
						</NcActionButton>
						<NcActionButton close-after-click
							@click="() => {
								synchronizationStore.setSynchronizationItem(synchronization)
								synchronizationStore.setSynchronizationSourceConfigKey(null)
								navigationStore.setModal('editSynchronizationSourceConfig')
							}">
							<template #icon>
								<DatabaseSettingsOutline :size="20" />
							</template>
							{{ t('openconnector', 'Add Source Config') }}
						</NcActionButton>
						<NcActionButton close-after-click
							@click="() => {
								synchronizationStore.setSynchronizationItem(synchronization)
								synchronizationStore.setSynchronizationTargetConfigKey(null)
								navigationStore.setModal('editSynchronizationTargetConfig')
							}">
							<template #icon>
								<CardBulletedSettingsOutline :size="20" />
							</template>
							{{ t('openconnector', 'Add Target Config') }}
						</NcActionButton>
						<NcActionButton close-after-click @click="synchronizationStore.setSynchronizationItem(synchronization); navigationStore.setModal('testSynchronization')">
							<template #icon>
								<Sync :size="20" />
							</template>
							{{ t('openconnector', 'Test') }}
						</NcActionButton>
						<NcActionButton close-after-click @click="synchronizationStore.setSynchronizationItem(synchronization); navigationStore.setModal('runSynchronization')">
							<template #icon>
								<Play :size="20" />
							</template>
							{{ t('openconnector', 'Run') }}
						</NcActionButton>
						<NcActionButton close-after-click @click="synchronizationStore.exportSynchronization(synchronization.id)">
							<template #icon>
								<FileExportOutline :size="20" />
							</template>
							{{ t('openconnector', 'Export synchronization') }}
						</NcActionButton>
						<NcActionButton close-after-click @click="synchronizationStore.setSynchronizationItem(synchronization); navigationStore.setDialog('deleteSynchronization')">
							<template #icon>
								<TrashCanOutline />
							</template>
							{{ t('openconnector', 'Delete') }}
						</NcActionButton>
					</template>
				</NcListItem>
			</div>
		</ul>

		<NcLoadingIcon v-if="!synchronizationStore.synchronizationList"
			class="loadingIcon"
			:size="64"
			appearance="dark"
			:name="t('openconnector', 'Loading synchronizations...')" />

		<div v-if="!synchronizationStore.synchronizationList.length" class="emptyListHeader">
			{{ t('openconnector', 'No synchronizations are available.') }}
		</div>
	</NcAppContentList>
</template>

<script>
import { NcListItem, NcActionButton, NcAppContentList, NcTextField, NcLoadingIcon, NcActions } from '@nextcloud/vue'
import Magnify from 'vue-material-design-icons/Magnify.vue'
import VectorPolylinePlus from 'vue-material-design-icons/VectorPolylinePlus.vue'
import Refresh from 'vue-material-design-icons/Refresh.vue'
import Plus from 'vue-material-design-icons/Plus.vue'
import Pencil from 'vue-material-design-icons/Pencil.vue'
import TrashCanOutline from 'vue-material-design-icons/TrashCanOutline.vue'
import FileExportOutline from 'vue-material-design-icons/FileExportOutline.vue'
import FileImportOutline from 'vue-material-design-icons/FileImportOutline.vue'
import DatabaseSettingsOutline from 'vue-material-design-icons/DatabaseSettingsOutline.vue'
import CardBulletedSettingsOutline from 'vue-material-design-icons/CardBulletedSettingsOutline.vue'
import Sync from 'vue-material-design-icons/Sync.vue'
import Play from 'vue-material-design-icons/Play.vue'

export default {
	name: 'SynchronizationsList',
	components: {
		NcListItem,
		NcActions,
		NcActionButton,
		NcAppContentList,
		NcTextField,
		NcLoadingIcon,
		Magnify,
		VectorPolylinePlus,
		Refresh,
		Plus,
		Pencil,
		TrashCanOutline,
	},
	mounted() {
		searchStore.clearSearch()
		synchronizationStore.refreshSynchronizationList()
	},
}
</script>

<style scoped>
/* Styles remain the same */
</style>
