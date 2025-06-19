<template>
	<NcModal v-if="navigationStore.modal === 'viewEndpoint'"
		ref="modalRef"
		label-id="viewEndpoint"
		@close="navigationStore.setModal(false)">
		<div class="modal-content">
			<h2>{{ endpointStore.endpointItem?.name || t('openconnector', 'Endpoint Details') }}</h2>
			<p v-if="endpointStore.endpointItem?.description" class="source-description">
				{{ endpointStore.endpointItem.description }}
			</p>
			<div class="tab-navigation">
				<NcButton :type="activeTab === 'details-tab' ? 'primary' : 'secondary'" @click="activeTab = 'details-tab'">
					{{ t('openconnector', 'Details') }}
				</NcButton>
				<NcButton :type="activeTab === 'rules-tab' ? 'primary' : 'secondary'" @click="activeTab = 'rules-tab'">
					{{ t('openconnector', 'Rules') }}
				</NcButton>
				<NcButton :type="activeTab === 'logs-tab' ? 'primary' : 'secondary'" @click="activeTab = 'logs-tab'">
					{{ t('openconnector', 'Logs') }}
				</NcButton>
			</div>
			<div class="tab-content">
				<div v-if="activeTab === 'details-tab'" class="tab-panel">
					<!-- Details here -->
				</div>
				<div v-if="activeTab === 'rules-tab'" class="tab-panel">
					<!-- Rules here -->
				</div>
				<div v-if="activeTab === 'logs-tab'" class="tab-panel">
					<EndpointLogIndex />
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
import EndpointLogIndex from './EndpointLogIndex.vue'
import { endpointStore, navigationStore } from '../../store/store.js'
export default {
	name: 'ViewEndpoint',
	components: { NcModal, NcButton, EndpointLogIndex },
	data() {
		return { activeTab: 'details-tab', endpointStore, navigationStore }
	},
}
</script>
