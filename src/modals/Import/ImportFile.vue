<script setup>
import { navigationStore, importExportStore } from '../../store/store.js'
</script>

<template>
	<NcModal v-if="navigationStore.modal === 'importFile'"
		ref="modalRef"
		label-id="ImportFileModal"
		@close="closeModal()">
		<div class="modalContent">
			<h2>Import {{ importExportStore.importFileName }}</h2>

			<div v-if="success !== null || error">
				<NcNoteCard v-if="success" type="success">
					<p>Successfully imported file</p>
				</NcNoteCard>
				<NcNoteCard v-if="!success" type="error">
					<p>Something went wrong while importing</p>
				</NcNoteCard>
				<NcNoteCard v-if="error && !success" type="error">
					<p>{{ error }}</p>
				</NcNoteCard>
			</div>
			<div v-if="success === null" class="form-group">
				<div class="addFileContainer">
					<div :ref="'dropZoneRef'" class="filesListDragDropNotice">
						<div class="filesListDragDropNoticeWrapper">
							<div class="filesListDragDropNoticeWrapperIcon">
								<TrayArrowDown :size="48" />
								<h3 class="filesListDragDropNoticeTitle">
									Drag and drop a file here
								</h3>
							</div>

							<h3 class="filesListDragDropNoticeTitle">
								Or
							</h3>

							<div class="filesListDragDropNoticeTitle">
								<NcButton v-if="success === null && !files"
									:disabled="loading"
									type="primary"
									@click="openFileUpload()">
									<template #icon>
										<Plus :size="20" />
									</template>
									Add file
								</NcButton>

								<NcButton v-if="success === null && files"
									:disabled=" loading"
									type="primary"
									@click="reset()">
									<template #icon>
										<Minus :size="20" />
									</template>
									<span v-for="file of files" :key="file.name">{{ file.name }}</span>
								</NcButton>
							</div>
						</div>
					</div>
				</div>
				<div class="modal-actions">
					<NcButton v-if="success === null"
						@click="closeModal">
						<template #icon>
							<CancelIcon size="20" />
						</template>
						Cancel
					</NcButton>
					<NcButton v-if="success === null"
						type="primary"
						:disabled="!files"
						@click="importFile()">
						<template #icon>
							<NcLoadingIcon v-if="loading" :size="20" />
							<FileImportOutline v-if="!loading" :size="20" />
						</template>
						Import
					</NcButton>
				</div>
			</div>
		</div>
	</NcModal>
</template>

<script>
import { NcButton, NcLoadingIcon, NcModal, NcNoteCard } from '@nextcloud/vue'
import { useFileSelection } from '../../composables/UseFileSelection.js'

import { ref } from 'vue'

import Minus from 'vue-material-design-icons/Minus.vue'
import Plus from 'vue-material-design-icons/Plus.vue'
import TrayArrowDown from 'vue-material-design-icons/TrayArrowDown.vue'
import FileImportOutline from 'vue-material-design-icons/FileImportOutline.vue'
import CancelIcon from 'vue-material-design-icons/Cancel.vue'

const dropZoneRef = ref()
const { openFileUpload, files, reset, setFiles } = useFileSelection({ allowMultiple: false, dropzone: dropZoneRef, allowedFileTypes: ['.json', '.yaml', '.yml'] })

export default {
	name: 'ImportFile',
	components: {
		NcModal,
		NcButton,
		NcLoadingIcon,
		NcNoteCard,
		CancelIcon,
	},
	props: {
		dropFiles: {
			type: Array,
			required: false,
			default: null,
		},
	},
	data() {
		return {
			loading: false,
			success: null,
			error: false,
			labelOptions: {
				inputLabel: 'Labels',
				multiple: true,
				options: ['Besluit', 'Convenant', 'Document', 'Informatieverzoek', 'Inventarisatielijst'],
			},
		}
	},
	watch: {
		dropFiles: {
			handler(addedFiles) {
				setFiles(addedFiles)
			},
			deep: true,
		},
	},
	mounted() {
	},
	methods: {

		closeModal() {
			navigationStore.setModal(false)
			reset()

		},
		importFile() {
			this.loading = true
			this.errorMessage = false
			importExportStore.importFile(files, reset).then((response) => {
				this.success = true

				const self = this
				setTimeout(function() {
					self.success = null
					self.closeModal()
				}, 2000)
			}).catch((err) => {
				this.error = err.response?.data?.error ?? err
				this.loading = false
			})
		},
	},
}
</script>

<style scoped>
.addFileContainer{
	margin-block-end: var(--OC-margin-20);
}
.addFileContainer--disabled{
	opacity: 0.4;
}

.zaakDetailsContainer {
    margin-block-start: var(--OC-margin-20);
    margin-inline-start: var(--OC-margin-20);
    margin-inline-end: var(--OC-margin-20);
}

.success {
    color: green;
}
</style>
