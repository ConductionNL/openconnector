<script setup>
import { consumerStore, navigationStore } from '../../store/store.js'
</script>

<template>
	<div class="detailContainer">
		<div id="app-content">
			<div>
				<div class="detailHeader">
					<h1 class="h1">
						{{ consumerStore.consumerItem.name }}
					</h1>

					<NcActions :primary="true" menu-name="Acties">
						<template #icon>
							<DotsHorizontal :size="20" />
						</template>
						<NcActionButton close-after-click @click="navigationStore.setModal('editConsumer')">
							<template #icon>
								<Pencil :size="20" />
							</template>
							Bewerken
						</NcActionButton>
						<NcActionButton close-after-click @click="navigationStore.setDialog('deleteConsumer')">
							<template #icon>
								<TrashCanOutline :size="20" />
							</template>
							Verwijderen
						</NcActionButton>
					</NcActions>
				</div>

				<span>{{ consumerStore.consumerItem.description }}</span>

				<div class="detailGrid">
					<div class="gridContent">
						<b>Id:</b>
						<p>{{ consumerStore.consumerItem?.id || '-' }}</p>
					</div>
					<div class="gridContent">
						<b>Uuid:</b>
						<p>{{ consumerStore.consumerItem?.uuid || '-' }}</p>
					</div>
					<div class="gridContent" />

					<div class="gridContent">
						<b>Name:</b>
						<p>{{ consumerStore.consumerItem?.name || '-' }}</p>
					</div>
					<div class="gridContent">
						<b>Description:</b>
						<p>{{ consumerStore.consumerItem?.description || '-' }}</p>
					</div>
					<div class="gridContent">
						<b>Domains:</b>
						<p>{{ consumerStore.consumerItem?.domains?.join(', ') || '-' }}</p>
					</div>
					<div class="gridContent">
						<b>IP's:</b>
						<p>{{ consumerStore.consumerItem?.ips?.join(', ') || '-' }}</p>
					</div>
					<div class="gridContent">
						<b>Authorization Type:</b>
						<p>{{ consumerStore.consumerItem?.authorizationType || '-' }}</p>
					</div>
					<div class="gridContent">
						<b>Authorization Configuration:</b>
						<p>{{ consumerStore.consumerItem?.authorizationConfiguration || '-' }}</p>
					</div>

					<div class="gridContent">
						<b>Created:</b>
						<p>{{ consumerStore.consumerItem.created ? new Date(consumerStore.consumerItem.created).toLocaleDateString() : '-' }}</p>
					</div>
					<div class="gridContent">
						<b>Updated:</b>
						<p>{{ consumerStore.consumerItem.updated ? new Date(consumerStore.consumerItem.updated).toLocaleDateString() : '-' }}</p>
					</div>
				</div>
				<!-- Add more consumer-specific details here -->
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
	name: 'ConsumerDetails',
	components: {
		NcActions,
		NcActionButton,
		DotsHorizontal,
		Pencil,
		TrashCanOutline,
	},
}
</script>

<style>
/* Styles remain the same */
.gridContent {
	display: flex;
	gap: 10px;
}

.gridContent p {
	white-space: normal;
	overflow-wrap: break-word;
	word-wrap: break-word;
}
</style>
