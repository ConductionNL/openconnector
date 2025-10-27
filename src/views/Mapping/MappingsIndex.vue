<script setup>
import { mappingStore, navigationStore } from '../../store/store.js'
</script>

<template>
	<NcAppContent>
		<template #list>
			<MappingsList />
		</template>
		<template #default>
			<NcEmptyContent v-if="!$route.params.id"
				class="detailContainer"
				name="No mapping"
				description="No mapping selected">
				<template #icon>
					<SitemapOutline />
				</template>
				<template #action>
					<NcButton type="primary" @click="mappingStore.setMappingItem(null); navigationStore.setModal('editMapping')">
						Add mapping
					</NcButton>
				</template>
			</NcEmptyContent>
			<NcEmptyContent v-else-if="loadError"
				class="detailContainer"
				name="Error"
				description="Failed to load mapping.">
				<template #icon>
					<SitemapOutline />
				</template>
				<template #action>
					<div style="display: flex; gap: 0.5rem;">
						<NcButton type="secondary" @click="mappingStore.setMappingItem(null); loadError = false; $router.push('/mappings')">
							Back
						</NcButton>
						<NcButton type="primary" @click="mappingStore.setMappingItem(null); loadError = false; $router.push('/mappings'); navigationStore.setModal('editMapping')">
							Add mapping
						</NcButton>
					</div>
				</template>
			</NcEmptyContent>
			<MappingDetails v-else-if="!loading" :item="mappingStore.mappingItem" :loading="loading" />
		</template>
	</NcAppContent>
</template>

<script>
import { NcAppContent, NcEmptyContent, NcButton } from '@nextcloud/vue'
import MappingsList from './MappingsList.vue'
import MappingDetails from './MappingDetails.vue'
import SitemapOutline from 'vue-material-design-icons/SitemapOutline.vue'

export default {
	name: 'MappingsIndex',
	components: {
		NcAppContent,
		NcEmptyContent,
		NcButton,
		MappingsList,
		MappingDetails,
		SitemapOutline,
	},
	data() {
		return {
			mappingStore,
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
				this.mappingStore.setMappingItem(null)
				this.loadError = false
				return
			}
			try {
				this.loading = true
				this.loadError = false
				const { response } = await this.mappingStore.fetchMapping(String(id))
				if (!response.ok) {
					this.mappingStore.setMappingItem(null)
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
