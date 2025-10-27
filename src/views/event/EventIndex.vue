<script setup>
import { eventStore, navigationStore } from '../../store/store.js'
</script>

<template>
	<NcAppContent>
		<template #list>
			<EventList />
		</template>
		<template #default>
			<NcEmptyContent v-if="!$route.params.id"
				class="detailContainer"
				name="No event"
				description="No event selected">
				<template #icon>
					<Update />
				</template>
				<template #action>
					<NcButton type="primary" @click="eventStore.setEventItem(null); navigationStore.setModal('editEvent')">
						Add event
					</NcButton>
				</template>
			</NcEmptyContent>
			<NcEmptyContent v-else-if="loadError"
				class="detailContainer"
				name="Error"
				description="Failed to load event.">
				<template #icon>
					<Update />
				</template>
				<template #action>
					<div style="display: flex; gap: 0.5rem;">
						<NcButton type="secondary" @click="eventStore.setEventItem(null); loadError = false; $router.push('/cloud-events/events')">
							Back
						</NcButton>
						<NcButton type="primary" @click="eventStore.setEventItem(null); loadError = false; $router.push('/cloud-events/events'); navigationStore.setModal('editEvent')">
							Add event
						</NcButton>
					</div>
				</template>
			</NcEmptyContent>
			<EventDetails v-else-if="!loading"
				:item="eventStore.eventItem"
				:loading="loading" />
		</template>
	</NcAppContent>
</template>

<script>
import { NcAppContent, NcEmptyContent, NcButton } from '@nextcloud/vue'
import EventList from './EventList.vue'
import EventDetails from './EventDetails.vue'
import Update from 'vue-material-design-icons/Update.vue'

export default {
	name: 'EventIndex',
	components: {
		NcAppContent,
		NcEmptyContent,
		NcButton,
		EventList,
		EventDetails,
		Update,
	},
	data() {
		return {
			eventStore,
			navigationStore,
			loading: false,
			loadError: false,
		}
	},
	watch: {
		'$route.params.id'() {
			this.syncFromRoute()
		},
	},
	mounted() {
		this.syncFromRoute()
	},
	methods: {
		async syncFromRoute() {
			const id = this.$route.params.id
			if (!id) {
				this.eventStore.setEventItem(null)
				this.loadError = false
				return
			}
			try {
				this.loading = true
				this.loadError = false
				const { response } = await this.eventStore.fetchEvent(String(id))
				if (!response.ok) {
					this.eventStore.setEventItem(null)
					if (response.status >= 400 && response.status < 500) {
						throw new Error('Not found')
					}
				}
			} catch (e) {
				this.loadError = true
			} finally {
				this.loading = false
			}
		},
	},
}
</script>
