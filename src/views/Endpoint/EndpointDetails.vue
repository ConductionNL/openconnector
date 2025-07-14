<script setup>
import { endpointStore, navigationStore, ruleStore } from '../../store/store.js'
</script>

<template>
	<div class="detailContainer">
		<div id="app-content">
			<div>
				<div class="detailHeader">
					<h1 class="h1">
						{{ endpointStore.endpointItem.name }}
					</h1>

					<NcActions :primary="true" menu-name="Acties">
						<template #icon>
							<DotsHorizontal :size="20" />
						</template>
						<NcActionButton close-after-click @click="navigationStore.setModal('editEndpoint')">
							<template #icon>
								<Pencil :size="20" />
							</template>
							Bewerken
						</NcActionButton>
						<NcActionButton close-after-click @click="endpointStore.exportEndpoint(endpointStore.endpointItem.id)">
							<template #icon>
								<FileExportOutline :size="20" />
							</template>
							Export endpoint
						</NcActionButton>
						<NcActionButton close-after-click @click="navigationStore.setDialog('deleteEndpoint')">
							<template #icon>
								<TrashCanOutline :size="20" />
							</template>
							Verwijderen
						</NcActionButton>
						<NcActionButton close-after-click @click="navigationStore.setModal('addEndpointRule')">
							<template #icon>
								<Plus :size="20" />
							</template>
							Add Rule
						</NcActionButton>
					</NcActions>
				</div>

				<div class="detailGrid">
					<div class="gridContent">
						<b>Id:</b>
						<p>{{ endpointStore.endpointItem?.id || '-' }}</p>
					</div>
					<div class="gridContent">
						<b>Uuid:</b>
						<p>{{ endpointStore.endpointItem?.uuid || '-' }}</p>
					</div>
					<div class="gridContent" />

					<div class="gridContent">
						<b>Name:</b>
						<p>{{ endpointStore.endpointItem?.name || '-' }}</p>
					</div>
					<div class="gridContent">
						<b>Description:</b>
						<p>{{ endpointStore.endpointItem?.description || '-' }}</p>
					</div>

					<div class="gridContent">
						<b>Version:</b>
						<p>{{ endpointStore.endpointItem?.version || '-' }}</p>
					</div>
					<div class="gridContent">
						<b>Endpoint:</b>
						<p>{{ endpointStore.endpointItem?.endpoint || '-' }}</p>
					</div>
					<div class="gridContent">
						<b>Endpoint Array:</b>
						<p>{{ endpointStore.endpointItem.endpointArray.join(', ') || '-' }}</p>
					</div>
					<div class="gridContent">
						<b>Endpoint Regex:</b>
						<p>{{ endpointStore.endpointItem?.endpointRegex || '-' }}</p>
					</div>
					<div class="gridContent">
						<b>Method:</b>
						<p>{{ endpointStore.endpointItem?.method || '-' }}</p>
					</div>
					<div class="gridContent">
						<b>Target Type:</b>
						<p>{{ endpointStore.endpointItem?.targetType || '-' }}</p>
					</div>
					<div class="gridContent">
						<b>Target Id:</b>
						<p>{{ endpointStore.endpointItem?.targetId || '-' }}</p>
					</div>

					<div class="gridContent">
						<b>Created:</b>
						<p>{{ endpointStore.endpointItem?.created ? new Date(endpointStore.endpointItem.created).toLocaleDateString() : '-' }}</p>
					</div>
					<div class="gridContent">
						<b>Updated:</b>
						<p>{{ endpointStore.endpointItem.updated ? new Date(endpointStore.endpointItem.updated).toLocaleDateString() : '-' }}</p>
					</div>
				</div>

				<div class="tabContainer">
					<BTabs content-class="mt-3" justified>
						<!-- Rules Tab -->
						<BTab title="Rules">
							<div class="tabButtonsContainer">
								<NcButton type="primary"
									class="fullWidthButton"
									aria-label="Add Rule"
									@click="navigationStore.setModal('addEndpointRule')">
									<template #icon>
										<Plus :size="20" />
									</template>
									Add Rule
								</NcButton>
							</div>
							<div v-if="endpointStore.endpointItem?.rules?.length">
								<NcListItem v-for="ruleId in endpointStore.endpointItem.rules"
									:key="ruleId"
									:name="getRuleName(ruleId)"
									:bold="false"
									:force-display-actions="true"
									@click="viewRule(ruleId)">
									<template #icon>
										<SitemapOutline
											disable-menu
											:size="44" />
									</template>
									<template #subname>
										<span v-if="rulesLoaded">{{ getRuleType(ruleId) }}</span>
										<span v-else>Loading...</span>
									</template>
									<template #actions>
										<NcActionButton close-after-click @click.stop="viewRule(ruleId)">
											<template #icon>
												<EyeOutline :size="20" />
											</template>
											View
										</NcActionButton>
										<NcActionButton close-after-click @click.stop="removeRule(ruleId)">
											<template #icon>
												<LinkOff :size="20" />
											</template>
											Remove
										</NcActionButton>
									</template>
								</NcListItem>
							</div>
							<div v-else class="tabPanel">
								No rules found
							</div>
						</BTab>

						<!-- Logs Tab -->
						<BTab title="Logs">
							<div class="tabPanel">
								No logs found
							</div>
						</BTab>
					</BTabs>
				</div>
			</div>
		</div>
	</div>
</template>

<script>
import { NcActions, NcActionButton, NcListItem, NcButton } from '@nextcloud/vue'
import { BTabs, BTab } from 'bootstrap-vue'
import DotsHorizontal from 'vue-material-design-icons/DotsHorizontal.vue'
import Pencil from 'vue-material-design-icons/Pencil.vue'
import TrashCanOutline from 'vue-material-design-icons/TrashCanOutline.vue'
import FileExportOutline from 'vue-material-design-icons/FileExportOutline.vue'
import SitemapOutline from 'vue-material-design-icons/SitemapOutline.vue'
import Plus from 'vue-material-design-icons/Plus.vue'
import EyeOutline from 'vue-material-design-icons/EyeOutline.vue'
import LinkOff from 'vue-material-design-icons/LinkOff.vue'
import _ from 'lodash'

import { Endpoint } from '../../entities/index.js'

export default {
	name: 'EndpointDetails',
	components: {
		NcActions,
		NcActionButton,
		NcListItem,
		BTabs,
		BTab,
		DotsHorizontal,
		Pencil,
		TrashCanOutline,
		FileExportOutline,
		SitemapOutline,
		Plus,
		EyeOutline,
		LinkOff,
		NcButton,
	},
	data() {
		return {
			rulesList: [],
			rulesLoaded: false,
		}
	},
	mounted() {
		this.loadRules()
	},
	methods: {

		async loadRules() {
			try {
				await ruleStore.refreshRuleList()
				this.rulesList = ruleStore.ruleList
				this.rulesLoaded = true
			} catch (error) {
				console.error('Failed to load rules:', error)
			}
		},
		getRuleName(ruleId) {
			const rule = this.rulesList.find(rule => String(rule.id) === String(ruleId))
			return rule ? rule.name : `Rule ${ruleId}`
		},
		getRuleType(ruleId) {
			const rule = this.rulesList.find(rule => String(rule.id) === String(ruleId))
			if (!rule) return 'Unknown type'

			// Convert type to more readable format
			switch (rule.type) {
			case 'error':
				return 'Error Handler'
			case 'mapping':
				return 'Data Mapping'
			case 'synchronization':
				return 'Synchronization'
			case 'javascript':
				return 'JavaScript'
			default:
				return rule.type || 'Unknown type'
			}
		},
		viewRule(ruleId) {
			const rule = this.rulesList.find(rule => String(rule.id) === String(ruleId))
			if (rule) {
				ruleStore.setRuleItem(rule)
				navigationStore.setSelected('rules')
			}
		},
		async removeRule(ruleId) {
			try {
				const updatedEndpoint = _.cloneDeep(endpointStore.endpointItem)

				// Remove the rule ID from the rules array
				updatedEndpoint.rules = updatedEndpoint.rules.filter(id => String(id) !== String(ruleId))

				const newEndpointItem = new Endpoint({
					...updatedEndpoint,
					endpointArray: Array.isArray(updatedEndpoint.endpointArray)
						? updatedEndpoint.endpointArray
						: updatedEndpoint.endpointArray.split(/ *, */g),
					rules: updatedEndpoint.rules.map(id => String(id)),
				})

				// Save the updated endpoint
				await endpointStore.saveEndpoint(newEndpointItem)

				// Refresh the rules list
				await this.loadRules()
			} catch (error) {
				console.error('Failed to remove rule:', error)
			}
		},
	},
}
</script>

<style>
.detailHeader {
	display: flex;
	justify-content: space-between;
	align-items: center;
	margin-bottom: 20px;
}
</style>

<style scoped>
.detailContainer {
	padding: 20px;
}

.detailGrid {
	display: grid;
	grid-template-columns: 1fr 1fr;
	gap: 20px;
	margin: 20px 0;
}
.gridContent {
	display: flex;
	gap: 10px;
}
.gridFullWidth {
	grid-column: 1 / -1;
}

.tabContainer {
	margin-top: 20px;
}

.tabPanel {
	padding: 20px;
	text-align: center;
	color: var(--color-text-maxcontrast);
}
</style>
