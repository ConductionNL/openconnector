<script setup>
import { logStore, navigationStore } from '../../store/store.js'
</script>

<template>
	<NcDialog v-if="navigationStore.dialog === 'deleteLog'"
		name="Log verwijderen"
		size="normal"
		:can-close="false">
		<p v-if="!success">
			Wil je <b>{{ logStore.logItem.name }}</b> definitief verwijderen? Deze actie kan niet ongedaan worden gemaakt.
		</p>

		<NcNoteCard v-if="success" type="success">
			<p>Log succesvol verwijderd</p>
		</NcNoteCard>
		<NcNoteCard v-if="error" type="error">
			<p>{{ error }}</p>
		</NcNoteCard>

		<template #actions>
			<NcButton
				@click="closeModal">
				<template #icon>
					<Cancel :size="20" />
				</template>
				{{ success ? 'Sluiten' : 'Annuleer' }}
			</NcButton>
			<NcButton
				v-if="!success"
				:disabled="loading"
				type="error"
				@click="deleteLog()">
				<template #icon>
					<NcLoadingIcon v-if="loading" :size="20" />
					<TrashCanOutline v-if="!loading" :size="20" />
				</template>
				Verwijderen
			</NcButton>
		</template>
	</NcDialog>
</template>

<script>
import {
	NcButton,
	NcDialog,
	NcLoadingIcon,
	NcNoteCard,
} from '@nextcloud/vue'

import Cancel from 'vue-material-design-icons/Cancel.vue'
import TrashCanOutline from 'vue-material-design-icons/TrashCanOutline.vue'

export default {
	name: 'DeleteLog',
	components: {
		NcDialog,
		NcButton,
		NcLoadingIcon,
		NcNoteCard,
		// Icons
		TrashCanOutline,
		Cancel,
	},
	data() {
		return {
			success: false,
			loading: false,
			error: false,
			closeTimeoutFunc: null,
		}
	},
	methods: {
		closeModal() {
			navigationStore.setDialog(false)
			clearTimeout(this.closeTimeoutFunc)
			this.success = null
		},
		async deleteLog() {
			this.loading = true
			try {
				await logStore.deleteLog()
				// Close modal or show success message
				this.success = true
				this.loading = false
				this.error = false
				this.closeTimeoutFunc = setTimeout(this.closeModal, 2000)
			} catch (error) {
				this.loading = false
				this.success = false
				this.error = error.message || 'Er is een fout opgetreden bij het verwijderen van de log'
			}
		},
	},
}
</script>
