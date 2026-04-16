<script setup>
import { translate as t } from '@nextcloud/l10n'
import { webhookStore, navigationStore } from '../../store/store.js'
</script>

<template>
	<div class="detailContainer">
		<div id="app-content">
			<div>
				<div class="head">
					<h1 class="h1">
						{{ webhookStore.webhookItem.name }}
					</h1>

					<NcActions :primary="true" :menu-name="t('openconnector', 'Actions')">
						<template #icon>
							<DotsHorizontal :size="20" />
						</template>
						<NcActionButton close-after-click @click="navigationStore.setModal('editWebhook')">
							<template #icon>
								<Pencil :size="20" />
							</template>
							{{ t('openconnector', 'Edit') }}
						</NcActionButton>
						<NcActionButton close-after-click @click="navigationStore.setDialog('deleteWebhook')">
							<template #icon>
								<TrashCanOutline :size="20" />
							</template>
							{{ t('openconnector', 'Delete') }}
						</NcActionButton>
					</NcActions>
				</div>
				<span>{{ webhookStore.webhookItem.description }}</span>

				<div class="detailGrid">
					<div class="gridContent gridFullWidth">
						<b>{{ t('openconnector', 'ID') }}:</b>
						<p>{{ webhookStore.webhookItem.uuid }}</p>
					</div>
					<div class="gridContent gridFullWidth">
						<b>{{ t('openconnector', 'URL') }}:</b>
						<p>{{ webhookStore.webhookItem.url }}</p>
					</div>
				</div>
				<!-- Add more webhook-specific details here -->
			</div>
		</div>
	</div>
</template>

<script>
import { NcActions, NcActionButton } from '@nextcloud/vue'
import DotsHorizontal from 'vue-material-design-icons/DotsHorizontal.vue'
import Pencil from 'vue-material-design-icons/Pencil.vue'
import TrashCanOutline from 'vue-material-design-icons/TrashCanOutline.vue'

export default {
	name: 'WebhookDetails',
	components: {
		NcActions,
		NcActionButton,
		DotsHorizontal,
		Pencil,
		TrashCanOutline,
	},
}
</script>

<style scoped>
/* Styles remain the same */
</style>
