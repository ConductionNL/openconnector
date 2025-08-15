<script setup>
import { mappingStore, navigationStore } from '../../store/store.js'
import { Mapping } from '../../entities/index.js'
</script>

<template>
	<NcModal v-if="navigationStore.modal === 'editMappingCast'"
		ref="modalRef"
		label-id="editMappingCast"
		@close="closeModal">
		<div class="modalContent">
			<h2>{{ isEdit ? 'Edit' : 'Add' }} Cast</h2>
			<NcNoteCard v-if="success" type="success">
				<p>Cast successfully added</p>
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
						:error="checkIfKeyIsUnique(castItem.key)"
						:helper-text="checkIfKeyIsUnique(castItem.key) ? 'This key is already in use. Please choose a different key name.' : ''"
						:value.sync="castItem.key" />
					<NcTextField
						id="value"
						label="Value"
						:value.sync="castItem.value" />
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
					:disabled="loading || !castItem.key || checkIfKeyIsUnique(castItem.key)"
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
	name: 'EditMappingCast',
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
			castItem: {
				key: '',
				value: '',
			},
			success: false,
			loading: false,
			error: false,
			hasUpdated: false,
			oldKey: '',
			isEdit: false,
			closeTimeoutFunc: null,
		}
	},
	mounted() {
		this.initializeMappingCast()
	},
	updated() {
		if (navigationStore.modal === 'editMappingCast' && !this.hasUpdated) {
			this.initializeMappingCast()
			this.hasUpdated = true
		}
	},
	methods: {
		initializeMappingCast() {
			if (!mappingStore.mappingCastKey) {
				return
			}
			const castItem = Object.entries(mappingStore.mappingItem.cast).find(([key]) => key === mappingStore.mappingCastKey)
			if (castItem) {
				this.castItem = {
					key: castItem[0] || '',
					value: castItem[1] || '',
				}
				this.oldKey = castItem[0]
				this.isEdit = true
			}
		},
		checkIfKeyIsUnique(key) {
			if (!mappingStore.mappingItem.cast) return false
			const keys = Object.keys(mappingStore.mappingItem.cast)
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
			this.castItem = {
				key: '',
				value: '',
			}
		},
		async editMapping() {
			this.loading = true

			const newMappingItem = {
				...mappingStore.mappingItem,
				cast: {
					...mappingStore.mappingItem.cast,
					[this.castItem.key]: this.castItem.value,
				},
			}

			if (this.oldKey !== '' && this.oldKey !== this.castItem.key) {
				delete newMappingItem.cast[this.oldKey]
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
