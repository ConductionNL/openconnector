<template>
	<NcModal v-if="navigationStore.modal === 'viewSynchronization'"
		ref="modalRef"
		label-id="viewSynchronization"
		@close="navigationStore.setModal(false)">
		<div class="modal-content">
			<h2>{{ synchronizationStore.synchronizationItem?.name || t('openconnector', 'Synchronization Details') }}</h2>
			<p v-if="synchronizationStore.synchronizationItem?.description" class="source-description">
				{{ synchronizationStore.synchronizationItem.description }}
			</p>
			<div class="tab-navigation">
				<NcButton :type="activeTab === 'details-tab' ? 'primary' : 'secondary'" @click="activeTab = 'details-tab'">
					{{ t('openconnector', 'Details') }}
				</NcButton>
				<NcButton :type="activeTab === 'contracts-tab' ? 'primary' : 'secondary'" @click="activeTab = 'contracts-tab'">
					{{ t('openconnector', 'Contracts') }}
				</NcButton>
				<NcButton :type="activeTab === 'logs-tab' ? 'primary' : 'secondary'" @click="activeTab = 'logs-tab'">
					{{ t('openconnector', 'Logs') }}
				</NcButton>
			</div>
			<div class="tab-content">
				<div v-if="activeTab === 'details-tab'" class="tab-panel">
					<!-- Details here -->
				</div>
				<div v-if="activeTab === 'contracts-tab'" class="tab-panel">
					<!-- Contracts here -->
				</div>
				<div v-if="activeTab === 'logs-tab'" class="tab-panel">
					<SynchronizationLogIndex />
				</div>
			</div>
			<div class="modal-actions">
				<NcButton @click="navigationStore.setModal(false)">
					{{ t('openconnector', 'Close') }}
				</NcButton>
			</div>
		</div>
	</NcModal>
</template>
<script>
import { NcModal, NcButton } from '@nextcloud/vue'
import SynchronizationLogIndex from './SynchronizationLogIndex.vue'
import { synchronizationStore, navigationStore } from '../../store/store.js'
export default {
	name: 'ViewSynchronization',
	components: { NcModal, NcButton, SynchronizationLogIndex },
	data() {
		return { activeTab: 'details-tab', synchronizationStore, navigationStore }
	},
}
</script>
