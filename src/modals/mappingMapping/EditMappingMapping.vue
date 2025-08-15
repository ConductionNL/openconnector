<script setup>
import { mappingStore, navigationStore } from '../../store/store.js'
import { Mapping } from '../../entities/index.js'
</script>

<template>
	<NcModal v-if="navigationStore.modal === 'editMappingMapping'"
		ref="modalRef"
		label-id="editMappingMapping"
		@close="closeModal">
		<div class="modalContent">
			<h2>{{ isEdit ? 'Edit' : 'Add' }} Mapping</h2>
			<NcNoteCard v-if="success" type="success">
				<p>Mapping successfully added</p>
			</NcNoteCard>
			<NcNoteCard v-if="error" type="error">
				<p>{{ error }}</p>
			</NcNoteCard>

			<form v-if="!success" @submit.prevent="handleSubmit">
				<div class="form-group">
					<NcTextField
						id="key"
						label="Key*"
						required
						:error="checkIfKeyIsUnique(mappingItem.key)"
						:helper-text="checkIfKeyIsUnique(mappingItem.key) ? 'This key is already in use. Please choose a different key name.' : ''"
						:value.sync="mappingItem.key" />
					<NcTextField
						id="value"
						label="Value"
						:value.sync="mappingItem.value" />
				</div>
			</form>

			<div class="modal-actions">
				<NcButton v-if="!success"
					@click="closeModal">
					<template #icon>
						<CancelIcon size="20" />
					</template>
					Cancel
				</NcButton>
				<NcButton
					v-if="!success"
					:disabled="loading || !mappingItem.key || checkIfKeyIsUnique(mappingItem.key)"
					type="primary"
					@click="editMapping()">
					<template #icon>
						<NcLoadingIcon v-if="loading" :size="20" />
						<ContentSaveOutline v-if="!loading" :size="20" />
					</template>
					Save
				</NcButton>
			</div>
		</div>
	</NcModal>
</template>

<script>
import {
	NcButton,
	NcModal,
	NcLoadingIcon,
	NcNoteCard,
	NcTextField,
} from '@nextcloud/vue'
import ContentSaveOutline from 'vue-material-design-icons/ContentSaveOutline.vue'
import CancelIcon from 'vue-material-design-icons/Cancel.vue'

export default {
	name: 'EditMappingMapping',
	components: {
		NcModal,
		NcButton,
		NcLoadingIcon,
		NcNoteCard,
		NcTextField,
		// Icons
		ContentSaveOutline,
		CancelIcon,
	},
	data() {
		return {
			mappingItem: {
				key: '',
				value: '',
			},
			success: false,
			loading: false,
			error: false,
			hasUpdated: false,
			closeTimeoutFunc: null,
			oldKey: '',
			isEdit: false,
		}
	},
	mounted() {
		this.initializeMappingMapping()
	},
	updated() {
		if (navigationStore.modal === 'editMappingMapping' && !this.hasUpdated) {
			this.initializeMappingMapping()
			this.hasUpdated = true
		}
	},
	methods: {
		initializeMappingMapping() {
			if (!mappingStore.mappingMappingKey) {
				return
			}
			const mappingItem = Object.entries(mappingStore.mappingItem.mapping).find(([key]) => key === mappingStore.mappingMappingKey)
			if (mappingItem) {
				this.mappingItem = {
					key: mappingItem[0] || '',
					value: mappingItem[1] || '',
				}
				this.oldKey = mappingItem[0]
				this.isEdit = true
			}
		},
		checkIfKeyIsUnique(key) {
			if (!mappingStore.mappingItem.mapping) return false
			const keys = Object.keys(mappingStore.mappingItem.mapping)
			if (this.oldKey === key) return false
			if (keys.includes(key)) return true
			return false
		},
		closeModal() {
			navigationStore.setModal(false)
			clearTimeout(this.closeTimeoutFunc)
			this.success = false
			this.loading = false
			this.error = false
			this.hasUpdated = false
			this.isEdit = false
			this.oldKey = ''
			this.mappingItem = {
				key: '',
				value: '',
			}
		},
		async editMapping() {
			this.loading = true

			const newMappingItem = {
				...mappingStore.mappingItem,
				mapping: {
					...mappingStore.mappingItem.mapping,
					[this.mappingItem.key]: this.mappingItem.value,
				},
			}

			if (this.oldKey !== '' && this.oldKey !== this.mappingItem.key) {
				delete newMappingItem.mapping[this.oldKey]
			}

			try {
				const mappingItem = new Mapping(newMappingItem)

				await mappingStore.saveMapping(mappingItem)
				// Close modal or show success message
				this.success = true
				this.loading = false
				this.closeTimeoutFunc = setTimeout(this.closeModal, 2000)
			} catch (error) {
				this.loading = false
				this.success = false
				this.error = error.message || 'An error occurred while saving the mapping'
			}
		},
	},
}
</script>
