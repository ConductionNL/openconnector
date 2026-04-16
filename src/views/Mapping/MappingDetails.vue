<script setup>
import { translate as t } from '@nextcloud/l10n'
import { mappingStore, navigationStore } from '../../store/store.js'
</script>

<template>
	<div class="detailContainer">
		<div id="app-content">
			<div>
				<div class="detailHeader">
					<h1 class="h1">
						{{ item?.name || '-' }}
					</h1>

					<NcActions :primary="true" :menu-name="t('openconnector', 'Actions')">
						<template #icon>
							<DotsHorizontal :size="20" />
						</template>
						<NcActionButton close-after-click @click="navigationStore.setModal('editMapping')">
							<template #icon>
								<Pencil :size="20" />
							</template>
							{{ t('openconnector', 'Edit') }}
						</NcActionButton>
						<NcActionButton close-after-click @click="addMappingMapping()">
							<template #icon>
								<MapPlus :size="20" />
							</template>
							{{ t('openconnector', 'Add mapping') }}
						</NcActionButton>
						<NcActionButton close-after-click @click="addMappingCast()">
							<template #icon>
								<SwapHorizontal :size="20" />
							</template>
							{{ t('openconnector', 'Add cast') }}
						</NcActionButton>
						<NcActionButton close-after-click @click="mappingStore.setEditingMode('unset'); mappingStore.setEditingMappingId(mappingStore.mappingItem?.id); mappingStore.setMappingUnsetKey(null); navigationStore.setDialog('editMappingItem')">
							<template #icon>
								<Eraser :size="20" />
							</template>
							{{ t('openconnector', 'Add unset') }}
						</NcActionButton>
						<NcActionButton close-after-click @click="navigationStore.setModal('testMapping')">
							<template #icon>
								<TestTube :size="20" />
							</template>
							{{ t('openconnector', 'Test') }}
						</NcActionButton>
						<NcActionButton close-after-click @click="mappingStore.exportMapping(mappingStore.mappingItem.id)">
							<template #icon>
								<FileExportOutline :size="20" />
							</template>
							{{ t('openconnector', 'Export mapping') }}
						</NcActionButton>
						<NcActionButton close-after-click @click="navigationStore.setDialog('deleteMapping')">
							<template #icon>
								<TrashCanOutline :size="20" />
							</template>
							{{ t('openconnector', 'Delete') }}
						</NcActionButton>
					</NcActions>
				</div>
				<span>{{ item?.description || '-' }}</span>

				<div class="detailGrid">
					<div class="gridContent gridFullWidth">
						<b>{{ t('openconnector', 'ID') }}:</b>
						<p>{{ item?.id || '-' }}</p>
					</div>
					<div class="gridContent gridFullWidth">
						<b>{{ t('openconnector', 'UUID') }}:</b>
						<p>{{ item?.uuid || '-' }}</p>
					</div>
					<div class="gridContent gridFullWidth">
						<b>{{ t('openconnector', 'Reference') }}:</b>
						<p>{{ item?.reference || '-' }}</p>
					</div>
					<div class="gridContent gridFullWidth">
						<b>{{ t('openconnector', 'Version') }}:</b>
						<p>{{ item?.version || '-' }}</p>
					</div>
				</div>
				<div class="tabContainer">
					<BTabs content-class="mt-3" justified>
						<BTab :title="t('openconnector', 'Mapping')">
							<div class="tabButtonsContainer">
								<NcButton type="primary"
									class="fullWidthButton"
									:aria-label="t('openconnector', 'Add mapping')"
									@click="addMappingMapping">
									<template #icon>
										<Plus :size="20" />
									</template>
									{{ t('openconnector', 'Add mapping') }}
								</NcButton>
							</div>
							<div v-if="item?.mapping !== null && Object.keys(item?.mapping || {}).length">
								<NcListItem v-for="(value, key, i) in item?.mapping"
									:key="`${key}${i}`"
									:name="key"
									:bold="false"
									:force-display-actions="true"
									:active="mappingStore.mappingMappingKey === key"
									@click="setActiveMappingMappingKey(key)">
									<template #icon>
										<SitemapOutline
											:class="mappingStore.mappingMappingKey === key && 'selectedZaakIcon'"
											disable-menu
											:size="44" />
									</template>
									<template #subname>
										{{ value }}
									</template>
									<template #actions>
										<NcActionButton close-after-click @click="editMappingMapping(key)">
											<template #icon>
												<Pencil :size="20" />
											</template>
											{{ t('openconnector', 'Edit') }}
										</NcActionButton>
										<NcActionButton close-after-click @click="deleteMappingMapping(key)">
											<template #icon>
												<Delete :size="20" />
											</template>
											{{ t('openconnector', 'Delete') }}
										</NcActionButton>
									</template>
								</NcListItem>
							</div>
							<div v-if="!Object.keys(item?.mapping || {}).length" class="tabPanel">
								{{ t('openconnector', 'No mapping found for this mapping') }}
							</div>
						</BTab>
						<BTab :title="t('openconnector', 'Cast')">
							<div class="tabButtonsContainer">
								<NcButton type="primary"
									class="fullWidthButton"
									:aria-label="t('openconnector', 'Add cast')"
									@click="addMappingCast">
									<template #icon>
										<Plus :size="20" />
									</template>
									{{ t('openconnector', 'Add cast') }}
								</NcButton>
							</div>
							<div v-if="item?.cast !== null && Object.keys(item?.cast || {}).length">
								<NcListItem v-for="(value, key, i) in item?.cast"
									:key="`${key}${i}`"
									:name="key"
									:bold="false"
									:force-display-actions="true"
									:active="mappingStore.mappingCastKey === key"
									@click="setActiveMappingCastKey(key)">
									<template #icon>
										<SwapHorizontal
											:class="mappingStore.mappingCastKey === key && 'selectedZaakIcon'"
											disable-menu
											:size="44" />
									</template>
									<template #subname>
										{{ value }}
									</template>
									<template #actions>
										<NcActionButton close-after-click @click="editMappingCast(key)">
											<template #icon>
												<Pencil :size="20" />
											</template>
											{{ t('openconnector', 'Edit') }}
										</NcActionButton>
										<NcActionButton close-after-click @click="deleteMappingCast(key)">
											<template #icon>
												<Delete :size="20" />
											</template>
											{{ t('openconnector', 'Delete') }}
										</NcActionButton>
									</template>
								</NcListItem>
							</div>
							<div v-if="!Object.keys(item?.cast || {}).length" class="tabPanel">
								{{ t('openconnector', 'No cast found for this mapping') }}
							</div>
						</BTab>
						<BTab :title="t('openconnector', 'Unset')">
							<div class="tabButtonsContainer">
								<NcButton type="primary"
									class="fullWidthButton"
									:aria-label="t('openconnector', 'Add unset')"
									@click="mappingStore.setEditingMode('unset'); mappingStore.setEditingMappingId(mappingStore.mappingItem?.id); mappingStore.setMappingUnsetKey(null); navigationStore.setDialog('editMappingItem')">
									<template #icon>
										<Plus :size="20" />
									</template>
									{{ t('openconnector', 'Add unset') }}
								</NcButton>
							</div>
							<div v-if="item?.unset?.length">
								<NcListItem v-for="(value, i) in item?.unset"
									:key="`${value}${i}`"
									:name="value"
									:bold="false"
									:force-display-actions="true">
									<template #icon>
										<Eraser
											:class="mappingStore.mappingUnsetKey === value && 'selectedZaakIcon'"
											disable-menu
											:size="44" />
									</template>
									<template #actions>
										<NcActionButton close-after-click @click="mappingStore.setEditingMode('unset'); mappingStore.setEditingMappingId(mappingStore.mappingItem?.id); mappingStore.setMappingUnsetKey(value); navigationStore.setDialog('editMappingItem')">
											<template #icon>
												<Pencil :size="20" />
											</template>
											{{ t('openconnector', 'Edit') }}
										</NcActionButton>
										<NcActionButton close-after-click @click="mappingStore.setEditingMode('unset'); mappingStore.setEditingMappingId(mappingStore.mappingItem?.id); mappingStore.setMappingUnsetKey(value); navigationStore.setDialog('deleteMappingItem')">
											<template #icon>
												<Delete :size="20" />
											</template>
											{{ t('openconnector', 'Delete') }}
										</NcActionButton>
									</template>
								</NcListItem>
							</div>
							<div v-if="!item?.unset?.length" class="tabPanel">
								{{ t('openconnector', 'No unset found for this mapping') }}
							</div>
						</BTab>
					</BTabs>
				</div>
			</div>
		</div>
	</div>
</template>

<script>
import { NcActions, NcActionButton, NcListItem, NcButton } from '@nextcloud/vue'
import { BTab, BTabs } from 'bootstrap-vue'
import DotsHorizontal from 'vue-material-design-icons/DotsHorizontal.vue'
import Pencil from 'vue-material-design-icons/Pencil.vue'
import MapPlus from 'vue-material-design-icons/MapPlus.vue'
import SitemapOutline from 'vue-material-design-icons/SitemapOutline.vue'
import SwapHorizontal from 'vue-material-design-icons/SwapHorizontal.vue'
import TrashCanOutline from 'vue-material-design-icons/TrashCanOutline.vue'
import Delete from 'vue-material-design-icons/Delete.vue'
import TestTube from 'vue-material-design-icons/TestTube.vue'
import Eraser from 'vue-material-design-icons/Eraser.vue'
import FileExportOutline from 'vue-material-design-icons/FileExportOutline.vue'
import Plus from 'vue-material-design-icons/Plus.vue'
export default {
	name: 'MappingDetails',
	components: {
		NcActions,
		NcActionButton,
		NcListItem,
		TrashCanOutline,
		BTab,
		BTabs,
		NcButton,
		Plus,
	},
	props: {
		item: {
			type: Object,
			default: null,
		},
		loading: {
			type: Boolean,
			default: false,
		},
	},
	methods: {
		deleteMappingMapping(key) {
			mappingStore.setEditingMode('mapping')
			mappingStore.setEditingMappingId(mappingStore.mappingItem?.id)
			mappingStore.setMappingMappingKey(key)
			navigationStore.setDialog('deleteMappingItem')
		},
		editMappingMapping(key) {
			mappingStore.setEditingMode('mapping')
			mappingStore.setEditingMappingId(mappingStore.mappingItem?.id)
			mappingStore.setMappingMappingKey(key)
			navigationStore.setDialog('editMappingItem')
		},
		addMappingMapping() {
			mappingStore.setEditingMode('mapping')
			mappingStore.setEditingMappingId(mappingStore.mappingItem?.id)
			mappingStore.setMappingMappingKey(null)
			navigationStore.setDialog('editMappingItem')
		},
		setActiveMappingMappingKey(mappingMappingKey) {
			if (mappingStore.mappingMappingKey === mappingMappingKey) {
				mappingStore.setMappingMappingKey(false)
			} else { mappingStore.setMappingMappingKey(mappingMappingKey) }
		},
		deleteMappingCast(key) {
			mappingStore.setEditingMode('cast')
			mappingStore.setEditingMappingId(mappingStore.mappingItem?.id)
			mappingStore.setMappingCastKey(key)
			navigationStore.setDialog('deleteMappingItem')
		},
		editMappingCast(key) {
			mappingStore.setEditingMode('cast')
			mappingStore.setEditingMappingId(mappingStore.mappingItem?.id)
			mappingStore.setMappingCastKey(key)
			navigationStore.setDialog('editMappingItem')
		},
		addMappingCast() {
			mappingStore.setEditingMode('cast')
			mappingStore.setEditingMappingId(mappingStore.mappingItem?.id)
			mappingStore.setMappingCastKey(null)
			navigationStore.setDialog('editMappingItem')
		},
		setActiveMappingCastKey(mappingCastKey) {
			if (mappingStore.mappingCastKey === mappingCastKey) {
				mappingStore.setMappingCastKey(false)
			} else { mappingStore.setMappingCastKey(mappingCastKey) }
		},
	},
}
</script>

<style scoped>
.tabButtonsContainer {
	display: flex;
	flex-direction: column;
	gap: 1rem;
	margin-bottom: 1rem;
}
</style>
