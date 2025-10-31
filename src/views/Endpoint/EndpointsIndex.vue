<script setup>
import { endpointStore, navigationStore } from '../../store/store.js'
</script>

<template>
	<NcAppContent>
		<template #list>
			<EndpointsList />
		</template>
		<template #default>
			<NcEmptyContent v-if="!$route.params.id"
				class="detailContainer"
				name="Geen endpoint"
				description="Nog geen endpoint geselecteerd">
				<template #icon>
					<Api />
				</template>
				<template #action>
					<NcButton type="primary" @click="endpointStore.setEndpointItem(null); navigationStore.setModal('editEndpoint')">
						Endpoint toevoegen
					</NcButton>
				</template>
			</NcEmptyContent>
			<NcEmptyContent v-else-if="loading"
				class="detailContainer"
				name="Loading..."
				description="Fetching rule details">
				<template #icon>
					<NcLoadingIcon />
				</template>
			</NcEmptyContent>
			<NcEmptyContent v-else-if="loadError"
				class="detailContainer"
				name="Error"
				description="Failed to load endpoint.">
				<template #icon>
					<Api />
				</template>
				<template #action>
					<div style="display: flex; gap: 0.5rem;">
						<NcButton type="secondary" @click="endpointStore.setEndpointItem(null); loadError = false; $router.push('/endpoints')">
							Terug
						</NcButton>
						<NcButton type="primary" @click="endpointStore.setEndpointItem(null); loadError = false; $router.push('/endpoints'); navigationStore.setModal('editEndpoint')">
							Endpoint toevoegen
						</NcButton>
					</div>
				</template>
			</NcEmptyContent>
			<EndpointDetails v-else
				:endpoint="selectedEndpoint"
				@endpoint-updated="syncFromStore" />
		</template>
	</NcAppContent>
</template>

<script>
import { NcAppContent, NcEmptyContent, NcButton, NcLoadingIcon } from '@nextcloud/vue'
import EndpointsList from './EndpointsList.vue'
import EndpointDetails from './EndpointDetails.vue'
import Api from 'vue-material-design-icons/Api.vue'

export default {
	name: 'EndpointsIndex',
	components: {
		NcAppContent,
		NcEmptyContent,
		NcButton,
		EndpointsList,
		EndpointDetails,
		Api,
	},
	data() {
		return {
			selectedEndpoint: null,
			loading: false,
			loadError: null,
			activeLoadId: null,
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
			const loadId = Symbol('RaceConditionGuard')
			this.activeLoadId = loadId
			this.loadError = null
			if (!id) {
				this.selectedEndpoint = null
				endpointStore.setEndpointItem(null)
				this.loading = false
				return
			}

			this.loading = true
			try {
				const { entity, response } = await endpointStore.fetchEndpoint(String(id))
				if (this.activeLoadId !== loadId) return // Stale request
				if (!response.ok) {
					throw new Error(response.statusText)
				}
				this.selectedEndpoint = entity
			} catch (e) {
				if (this.activeLoadId !== loadId) return // Stale request
				this.loadError = e?.message || 'Failed to load endpoint'
				this.selectedEndpoint = null
			} finally {
				this.loading = false
			}
		},
		syncFromStore() {
			this.selectedEndpoint = endpointStore.getEndpointItem()
		},
	},
}
</script>
