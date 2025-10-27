<script setup>
import { ruleStore, navigationStore } from '../../store/store.js'
</script>

<template>
	<NcAppContent>
		<template #list>
			<RuleList />
		</template>
		<template #default>
			<NcEmptyContent v-if="!$route.params.id"
				class="detailContainer"
				name="No rule"
				description="No rule selected">
				<template #icon>
					<Update />
				</template>
				<template #action>
					<NcButton type="primary" @click="ruleStore.setRuleItem(null); navigationStore.setModal('editRule')">
						Add rule
					</NcButton>
				</template>
			</NcEmptyContent>
			<NcEmptyContent v-else-if="loadError"
				class="detailContainer"
				name="Error"
				description="Failed to load rule.">
				<template #icon>
					<Update />
				</template>
				<template #action>
					<div style="display: flex; gap: 0.5rem;">
						<NcButton type="secondary" @click="ruleStore.setRuleItem(null); loadError = false; $router.push('/rules')">
							Back
						</NcButton>
						<NcButton type="primary" @click="ruleStore.setRuleItem(null); loadError = false; $router.push('/rules'); navigationStore.setModal('editRule')">
							Add rule
						</NcButton>
					</div>
				</template>
			</NcEmptyContent>
			<RuleDetails v-else-if="!loading"
				:item="ruleStore.ruleItem"
				:loading="loading" />
		</template>
	</NcAppContent>
</template>

<script>
import { NcAppContent, NcEmptyContent, NcButton } from '@nextcloud/vue'
import RuleList from './RuleList.vue'
import RuleDetails from './RuleDetails.vue'
import Update from 'vue-material-design-icons/Update.vue'

export default {
	name: 'RuleIndex',
	components: {
		NcAppContent,
		NcEmptyContent,
		NcButton,
		RuleList,
		RuleDetails,
		Update,
	},
	data() {
		return {
			ruleStore,
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
				this.ruleStore.setRuleItem(null)
				this.loadError = false
				return
			}
			try {
				this.loading = true
				this.loadError = false
				const { response } = await this.ruleStore.fetchRule(String(id))
				if (!response.ok) {
					this.ruleStore.setRuleItem(null)
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
