<script setup>
import { webhookStore, navigationStore } from '../../store/store.js'
</script>

<template>
	<NcAppContent>
		<template #list>
			<WebhooksList />
		</template>
		<template #default>
			<NcEmptyContent v-if="!webhookStore.webhookItem || $route.path != '/webhooks'"
				class="detailContainer"
				name="Geen webhook"
				description="Nog geen webhook geselecteerd">
				<template #icon>
					<Webhook />
				</template>
				<template #action>
					<NcButton type="primary" @click="webhookStore.setWebhookItem({}); navigationStore.setModal('editWebhook')">
						Webhook toevoegen
					</NcButton>
				</template>
			</NcEmptyContent>
			<WebhookDetails v-if="webhookStore.webhookItem && $route.path === '/webhooks'" />
		</template>
	</NcAppContent>
</template>

<script>
import { NcAppContent, NcEmptyContent, NcButton } from '@nextcloud/vue'
import WebhooksList from './WebhooksList.vue'
import WebhookDetails from './WebhookDetails.vue'
import Webhook from 'vue-material-design-icons/Webhook.vue'

export default {
	name: 'WebhooksIndex',
	components: {
		NcAppContent,
		NcEmptyContent,
		NcButton,
		WebhooksList,
		WebhookDetails,
		Webhook,
	},
}
</script>
