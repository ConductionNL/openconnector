<script setup>
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
				name="Geen consumer"
				description="Nog geen consumer geselecteerd">
				<template #icon>
					<Webhook />
				</template>
				<template #action>
					<NcButton type="primary" @click="consumerStore.setConsumerItem(null); navigationStore.setModal('editConsumer')">
						Consumer toevoegen
					</NcButton>
				</template>
			</NcEmptyContent>
			<NcEmptyContent v-else-if="loadError"
				class="detailContainer"
				name="Error"
				description="Failed to load consumer.">
				<template #icon>
					<Webhook />
				</template>
				<template #action>
					<div style="display: flex; gap: 0.5rem;">
						<NcButton type="secondary" @click="consumerStore.setConsumerItem(null); loadError = false; $router.push('/consumers')">
							Terug
						</NcButton>
						<NcButton type="primary" @click="consumerStore.setConsumerItem(null); loadError = false; $router.push('/consumers'); navigationStore.setModal('editConsumer')">
							Consumer toevoegen
						</NcButton>
					</div>
				</template>
			</NcEmptyContent>
			<ConsumerDetails v-else-if="!loading"
				:consumer="selectedConsumer"
				:loading="loading"
				@consumer-updated="syncFromStore" />
		</template>
	</NcAppContent>
</template>

<script>
import { NcAppContent, NcEmptyContent, NcButton } from '@nextcloud/vue'
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
