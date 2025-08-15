<script setup>
import { mappingStore, navigationStore } from '../../store/store.js'
import { Mapping } from '../../entities/index.js'
</script>

<template>
	<NcModal ref="modalRef"
		label-id="editMappingUnset"
		@close="closeModal">
		<div class="modalContent">
			<h2>{{ mappingStore.mappingUnsetKey ? 'Edit' : 'Add' }} Mapping Unset</h2>
			<NcNoteCard v-if="success" type="success">
				<p>Mapping Unset successfully added</p>
			</NcNoteCard>
			<NcNoteCard v-if="error" type="error">
				<p>{{ error }}</p>
			</NcNoteCard>

			<form v-if="!success" @submit.prevent="handleSubmit">
				<div class="form-group">
					<NcTextField
						id="unsetKey"
						label="Unset Key*"
						required
						:error="isUnsetTaken(unsetKey)"
						:helper-text="isUnsetTaken(unsetKey) ? 'This unset key is already in use. Please choose a different key name.' : ''"
						:value.sync="unsetKey" />
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
				<NcButton v-if="!success"
					:disabled="loading || !unsetKey || isUnsetTaken(unsetKey) || mappingStore.mappingUnsetKey === unsetKey"
					type="primary"
					@click="editMappingUnset()">
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
	name: 'EditMappingUnset',
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
			unsetKey: '',
			success: null,
			loading: false,
			error: false,
			closeTimeoutFunc: null,
		}
	},
	mounted() {
		this.initializeMappingUnset()
	},
	methods: {
		initializeMappingUnset() {
			if (mappingStore.mappingUnsetKey) {
				this.unsetKey = mappingStore.mappingUnsetKey
			}
		},
		isUnsetTaken(key) {
			if (!mappingStore.mappingItem?.unset) return false

			// if the key is the same as the one we are editing, don't check for duplicates.
			// this is safe since you are not allowed to save the same key anyway (only for edit modal).
			if (mappingStore.mappingUnsetKey === key) return false

			const allKeys = mappingStore.mappingItem.unset
			if (allKeys.includes(key)) return true

			return false
		},
		closeModal() {
			navigationStore.setModal(false)
			clearTimeout(this.closeTimeoutFunc)
			mappingStore.setMappingUnsetKey(null)
		},
		editMappingUnset() {
			this.loading = true

			// copy the mapping item unset array
			const newMappingUnset = [...mappingStore.mappingItem.unset]

			// if mappingUnsetKey is set remove that from the array
			if (mappingStore.mappingUnsetKey) {
				newMappingUnset.splice(newMappingUnset.indexOf(mappingStore.mappingUnsetKey), 1)
			}

			// add the new key
			newMappingUnset.push(this.unsetKey)

			// remove duplicates (if all went well duplicates shouldn't exist anyway)
			const uniqueMappingUnset = [...new Set(newMappingUnset)]

			const newMappingItem = new Mapping({
				...mappingStore.mappingItem,
				unset: uniqueMappingUnset,
			})

			mappingStore.saveMapping(newMappingItem)
				.then(({ response }) => {
					this.success = response.ok
					this.closeTimeoutFunc = setTimeout(this.closeModal, 2000)
				})
				.catch((error) => {
					this.error = error.message || 'An error occurred while saving the mapping unset'
				})
				.finally(() => {
					this.loading = false
				})
		},
	},
}
</script>
