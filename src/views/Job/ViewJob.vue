<template>
	<NcModal v-if="navigationStore.modal === 'viewJob'"
		ref="modalRef"
		label-id="viewJob"
		@close="navigationStore.setModal(false)">
		<div class="modal-content">
			<h2>{{ jobStore.jobItem?.name || t('openconnector', 'Job Details') }}</h2>
			<p v-if="jobStore.jobItem?.description" class="source-description">
				{{ jobStore.jobItem.description }}
			</p>
			<div class="tab-navigation">
				<NcButton :type="activeTab === 'details-tab' ? 'primary' : 'secondary'" @click="activeTab = 'details-tab'">
					{{ t('openconnector', 'Details') }}
				</NcButton>
				<NcButton :type="activeTab === 'arguments-tab' ? 'primary' : 'secondary'" @click="activeTab = 'arguments-tab'">
					{{ t('openconnector', 'Arguments') }}
				</NcButton>
				<NcButton :type="activeTab === 'logs-tab' ? 'primary' : 'secondary'" @click="activeTab = 'logs-tab'">
					{{ t('openconnector', 'Logs') }}
				</NcButton>
			</div>
			<div class="tab-content">
				<div v-if="activeTab === 'details-tab'" class="tab-panel">
					<!-- Details here -->
				</div>
				<div v-if="activeTab === 'arguments-tab'" class="tab-panel">
					<!-- Arguments here -->
				</div>
				<div v-if="activeTab === 'logs-tab'" class="tab-panel">
					<JobLogIndex />
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
import JobLogIndex from './JobLogIndex.vue'
import { jobStore, navigationStore } from '../../store/store.js'
export default {
	name: 'ViewJob',
	components: { NcModal, NcButton, JobLogIndex },
	data() {
		return { activeTab: 'details-tab', jobStore, navigationStore }
	},
}
</script>
