<script setup>
import { navigationStore } from '../../store/store.js'
</script>
<template>
	<NcAppSidebar
		ref="sidebar"
		v-model="activeTab"
		:name="t('openconnector', 'Synchronization Log Management')"
		:subtitle="t('openconnector', 'Filter and manage synchronization logs')"
		:subname="t('openconnector', 'Export, view, or delete logs')"
		:open="navigationStore.sidebarState.synchronizationLogs"
		@update:open="(e) => navigationStore.setSidebarState('synchronizationLogs', e)">
		<NcAppSidebarTab id="filters-tab" :name="t('openconnector', 'Filters')" :order="1">
			<template #icon>
				<FilterOutline :size="20" />
			</template>
			<!-- Filter Section (similar to SourceLogSideBar) -->
			<!-- ... -->
		</NcAppSidebarTab>
		<NcAppSidebarTab id="stats-tab" :name="t('openconnector', 'Statistics')" :order="2">
			<template #icon>
				<ChartLine :size="20" />
			</template>
			<!-- Statistics Section (similar to SourceLogSideBar) -->
			<!-- ... -->
		</NcAppSidebarTab>
	</NcAppSidebar>
</template>
<script>
import {
	NcAppSidebar,
	NcAppSidebarTab,
} from '@nextcloud/vue'
import FilterOutline from 'vue-material-design-icons/FilterOutline.vue'
import ChartLine from 'vue-material-design-icons/ChartLine.vue'
import { translate as t } from '@nextcloud/l10n'
export default {
	name: 'SynchronizationLogSideBar',
	components: {
		NcAppSidebar,
		NcAppSidebarTab,
		FilterOutline,
		ChartLine,
	},
	data() {
		return {
			activeTab: 'filters-tab',
			// ...
		}
	},
	// ...
}
</script>
