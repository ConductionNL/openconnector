<script setup>
import { sourceStore, navigationStore, searchStore } from '../../store/store.js'
</script>

<template>
	<NcAppContentList>
		<ul>
			<div class="listHeader">
				<NcTextField
					:value.sync="searchStore.search"
					:show-trailing-button="searchStore.search !== ''"
					label="Search"
					class="searchField"
					trailing-button-icon="close"
					@trailing-button-click="searchStore.clearSearch()">
					<Magnify :size="20" />
				</NcTextField>
				<NcActions>
					<NcActionButton close-after-click @click="sourceStore.refreshSourceList()">
						<template #icon>
							<Refresh :size="20" />
						</template>
						Refresh
					</NcActionButton>
					<NcActionButton close-after-click @click="sourceStore.setSourceItem({}); navigationStore.setModal('editSource')">
						<template #icon>
							<Plus :size="20" />
						</template>
						Add source
					</NcActionButton>
					<NcActionButton close-after-click @click="navigationStore.setModal('importFile')">
						<template #icon>
							<FileImportOutline :size="20" />
						</template>
						Import
					</NcActionButton>
				</NcActions>
			</div>
			<div v-if="sourceStore.sourceList && sourceStore.sourceList.length > 0">
				<NcListItem v-for="(source, i) in sourceStore.sourceList.filter(source => searchStore.search === '' || source.name.toLowerCase().includes(searchStore.search.toLowerCase()))"
					:key="`${source}${i}`"
					:name="source.name"
					:active="sourceStore.sourceItem?.id === source?.id"
					:force-display-actions="true"
					@click="sourceStore.setSourceItem(source)">
					<template #icon>
						<DatabaseArrowLeftOutline :class="sourceStore.sourceItem?.id === source.id && 'selectedSourceIcon'"
							disable-menu
							:size="44" />
					</template>
					<template #subname>
						{{ source?.description }}
					</template>
					<template #actions>
						<NcActionButton close-after-click @click="sourceStore.setSourceItem(source); navigationStore.setModal('editSource')">
							<template #icon>
								<Pencil />
							</template>
							Edit
						</NcActionButton>
						<NcActionButton close-after-click @click="sourceStore.setSourceItem(source); navigationStore.setModal('testSource')">
							<template #icon>
								<Sync :size="20" />
							</template>
							Test
						</NcActionButton>
						<NcActionButton close-after-click
							@click="() => {
								sourceStore.setSourceItem(source)
								sourceStore.setSourceConfigurationKey(null)
								navigationStore.setModal('editSourceConfiguration')
							}">
							<template #icon>
								<Plus :size="20" />
							</template>
							Add configuration
						</NcActionButton>
						<NcActionButton close-after-click
							@click="() => {
								sourceStore.setSourceItem(source)
								sourceStore.setSourceConfigurationKey(null)
								navigationStore.setModal('editSourceConfigurationAuthentication')
							}">
							<template #icon>
								<Plus :size="20" />
							</template>
							Add authentication
						</NcActionButton>
						<NcActionButton close-after-click @click="sourceStore.exportSource(source.id)">
							<template #icon>
								<FileExportOutline :size="20" />
							</template>
							Export source
						</NcActionButton>
						<NcActionButton close-after-click @click="sourceStore.setSourceItem(source); navigationStore.setDialog('deleteSource')">
							<template #icon>
								<TrashCanOutline />
							</template>
							Delete
						</NcActionButton>
					</template>
				</NcListItem>
			</div>
		</ul>

		<NcLoadingIcon v-if="!sourceStore.sourceList"
			class="loadingIcon"
			:size="64"
			appearance="dark"
			name="Loading sources" />

		<div v-if="!sourceStore.sourceList.length" class="emptyListHeader">
			No sources defined
		</div>
	</NcAppContentList>
</template>

<script>
// Components
import { NcListItem, NcActionButton, NcAppContentList, NcTextField, NcLoadingIcon, NcActions } from '@nextcloud/vue'

// Icons
import Magnify from 'vue-material-design-icons/Magnify.vue'
import DatabaseArrowLeftOutline from 'vue-material-design-icons/DatabaseArrowLeftOutline.vue'
import Refresh from 'vue-material-design-icons/Refresh.vue'
import Plus from 'vue-material-design-icons/Plus.vue'
import Pencil from 'vue-material-design-icons/Pencil.vue'
import TrashCanOutline from 'vue-material-design-icons/TrashCanOutline.vue'
import FileExportOutline from 'vue-material-design-icons/FileExportOutline.vue'
import FileImportOutline from 'vue-material-design-icons/FileImportOutline.vue'
import Sync from 'vue-material-design-icons/Sync.vue'

export default {
	name: 'SourcesList',
	components: {
		// Components
		NcListItem,
		NcActions,
		NcActionButton,
		NcAppContentList,
		NcTextField,
		NcLoadingIcon,
		// Icons
		DatabaseArrowLeftOutline,
		Magnify,
		Refresh,
		Plus,
		Pencil,
		TrashCanOutline,
	},
	mounted() {
		searchStore.clearSearch()
		sourceStore.refreshSourceList()
	},
}
</script>

<style>
/* Styles remain the same */
</style>
