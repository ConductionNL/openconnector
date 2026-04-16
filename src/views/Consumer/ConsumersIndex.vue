<script setup>
import { translate as t } from '@nextcloud/l10n'
import { consumerStore, navigationStore } from '../../store/store.js'
</script>

<template>
	<NcAppContent>
		<template #list>
			<ConsumersList />
		</template>
		<template #default>
			<NcEmptyContent v-if="!$route.params.id"
				class="detailContainer"
				:name="t('openconnector', 'No consumer')"
				:description="t('openconnector', 'No consumer selected')">
				<template #icon>
					<Webhook />
				</template>
				<template #action>
					<NcButton type="primary" @click="consumerStore.setConsumerItem(null); navigationStore.setModal('editConsumer')">
						{{ t('openconnector', 'Add consumer') }}
					</NcButton>
				</template>
			</NcEmptyContent>
			<NcEmptyContent v-else-if="loading"
				class="detailContainer"
				:name="t('openconnector', 'Loading...')"
				:description="t('openconnector', 'Fetching consumer details')">
				<template #icon>
					<NcLoadingIcon />
				</template>
			</NcEmptyContent>
			<NcEmptyContent v-else-if="loadError"
				class="detailContainer"
				:name="t('openconnector', 'Error')"
				:description="t('openconnector', 'Failed to load consumer.')">
				<template #icon>
					<Webhook />
				</template>
				<template #action>
					<div style="display: flex; gap: 0.5rem;">
						<NcButton type="secondary" @click="consumerStore.setConsumerItem(null); loadError = false; $router.push('/consumers')">
							{{ t('openconnector', 'Back') }}
						</NcButton>
						<NcButton type="primary" @click="consumerStore.setConsumerItem(null); loadError = false; $router.push('/consumers'); navigationStore.setModal('editConsumer')">
							{{ t('openconnector', 'Add consumer') }}
						</NcButton>
					</div>
				</template>
			</NcEmptyContent>
			<ConsumerDetails v-else
				:consumer="selectedConsumer"
				@consumer-updated="syncFromStore" />
		</template>
	</NcAppContent>
</template>

<script>
import { NcAppContent, NcEmptyContent, NcButton, NcLoadingIcon } from '@nextcloud/vue'
import ConsumersList from './ConsumersList.vue'
import ConsumerDetails from './ConsumerDetails.vue'
import Webhook from 'vue-material-design-icons/Webhook.vue'

export default {
	name: 'ConsumersIndex',
	components: {
		NcAppContent,
		NcEmptyContent,
		NcButton,
		ConsumersList,
		ConsumerDetails,
		Webhook,
	},
	data() {
		return {
			selectedConsumer: null,
			loading: false,
			loadError: null,
		}
	},
	watch: {
		'$route.params.id': {
			immediate: true,
			async handler(newId) {
				await this.loadByRoute(newId)
			},
		},
	},
	methods: {
		async loadByRoute(id) {
			this.loadError = null
			if (!id) {
				this.selectedConsumer = null
				consumerStore.setConsumerItem(null)
				this.loading = false
				return
			}

			this.loading = true
			try {
				const { entity, response } = await consumerStore.fetchConsumer(String(id))
				if (!response.ok) {
					throw new Error(response.statusText)
				}
				this.selectedConsumer = entity
			} catch (e) {
				this.loadError = e?.message || 'Failed to load consumer'
				this.selectedConsumer = null
			} finally {
				this.loading = false
			}
		},
		syncFromStore() {
			this.selectedConsumer = consumerStore.getConsumerItem()
		},
	},
}
</script>
