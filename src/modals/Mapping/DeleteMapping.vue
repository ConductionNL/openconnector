<script setup>
import { mappingStore, navigationStore } from '../../store/store.js'
</script>

<template>
	<NcDialog v-if="navigationStore.dialog === 'deleteMapping'"
		name="Delete mapping"
		size="normal"
		:can-close="false">
		<p v-if="!success">
			Do you want to delete <b>{{ mappingStore.mappingItem.name }}</b>? This action cannot be undone.
		</p>

		<NcNoteCard v-if="success" type="success">
			<p>Mapping successfully deleted</p>
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
				{{ success ? 'Close' : 'Cancel' }}
			</NcButton>
			<NcButton
				v-if="!success"
				:disabled="loading"
				type="error"
				@click="deleteMapping()">
				<template #icon>
					<NcLoadingIcon v-if="loading" :size="20" />
					<TrashCanOutline v-if="!loading" :size="20" />
				</template>
				Delete
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
	name: 'DeleteMapping',
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
		async deleteMapping() {
			this.loading = true
			try {
				await mappingStore.deleteMapping(mappingStore.mappingItem.id)
				// Close modal or show success message
				this.success = true
				this.loading = false
				this.error = false
				mappingStore.setMappingItem(null)
				this.closeTimeoutFunc = setTimeout(this.closeModal, 2000)
			} catch (error) {
				this.loading = false
				this.success = false
				this.error = error.message || 'An error occurred while deleting the mapping'
			}
		},
	},
}
</script>
