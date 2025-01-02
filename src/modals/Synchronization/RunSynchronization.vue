<script setup>
import { synchronizationStore, navigationStore } from '../../store/store.js'
import { getTheme } from '../../services/getTheme.js'
</script>

<template>
	<NcModal ref="modalRef"
		label-id="runSynchronization"
		@close="closeModal">
		<div class="modalContent runSynchronization">
			<h2>Run synchronization</h2>

			<div v-if="response === null" class="runButtonContainer">
				<NcButton type="primary" @click="runSynchronization">
					<template #icon>
						<Play :size="20" />
					</template>
					Run
				</NcButton>
			</div>

			<div v-if="loading">
				<NcLoadingIcon :size="64" name="Running synchronization" />
			</div>

			<NcNoteCard v-if="success === false" type="error">
				<p>An error occurred while running the synchronization.</p>
			</NcNoteCard>

			<div v-if="success !== null">
				<div v-if="success" class="SuccessMarker">
					Success Marker to expand the modal
				</div>

				<NcNoteCard v-if="response?.ok" type="success">
					<p>The synchronization was run successfully.</p>
				</NcNoteCard>
				<NcNoteCard v-if="!response?.ok || error" type="error">
					<p>
						An error occurred while running the synchronization: {{
							synchronizationStore.synchronizationRun
								? synchronizationStore.synchronizationRun.message
									? synchronizationStore.synchronizationRun.message
									: synchronizationStore.synchronizationRun.error
								: response?.statusMessage
									? response?.statusMessage
									: `${response?.status} - ${response?.statusText}`
						}}
					</p>
				</NcNoteCard>

				<div v-if="response" class="detailTable">
					<table>
						<tr>
							<td><b>Status:</b></td>
							<td>{{ response?.statusText }} ({{ response?.status }})</td>
						</tr>
						<tr>
							<td><b>Response time:</b></td>
							<td>{{ response?.responseTime ?? 'Onbekend' }} (Milliseconds)</td>
						</tr>
						<tr>
							<td><b>Size:</b></td>
							<td>{{ response?.size ?? 'Onbekend' }} (Bytes)</td>
						</tr>
						<tr>
							<td><b>Remote IP:</b></td>
							<td>{{ response?.remoteIp ?? 'Onbekend' }}</td>
						</tr>
						<tr>
							<td><b>Headers:</b></td>
							<td>
								<table>
									<tr v-for="(header, index) in response?.headers" :key="index">
										<td><b>{{ header[0] }}:</b></td>
										<td>{{ header[1] }}</td>
									</tr>
								</table>
							</td>
						</tr>
						<tr>
							<td><b>Body:</b></td>
							<td :class="`codeMirrorContainer ${getTheme()}`">
								<CodeMirror v-model="responseBodyString"
									:basic="true"
									:dark="getTheme() === 'dark'"
									:linter="jsonParseLinter()"
									:lang="json()"
									:readonly="true" />
							</td>
						</tr>
					</table>
				</div>
			</div>
		</div>
	</NcModal>
</template>

<script>
import {
	NcModal,
	NcLoadingIcon,
	NcNoteCard,
	NcButton,
} from '@nextcloud/vue'
import CodeMirror from 'vue-codemirror6'
import { json, jsonParseLinter } from '@codemirror/lang-json'

import Play from 'vue-material-design-icons/Play.vue'

export default {
	name: 'RunSynchronization',
	components: {
		NcModal,
		NcLoadingIcon,
		NcNoteCard,
		CodeMirror,
		NcButton,
	},
	data() {
		return {
			response: null,
			responseBody: '',
			responseBodyString: '',
			success: null,
			loading: false,
			error: false,
		}
	},
	methods: {
		closeModal() {
			navigationStore.setModal(false)
			synchronizationStore.synchronizationRun = null
		},
		async runSynchronization() {
			this.success = null
			this.loading = true
			this.error = false

			synchronizationStore.runSynchronization(synchronizationStore.synchronizationItem.id)
				.then(({ response, data }) => {
					this.response = response
					this.responseBody = data
					this.responseBodyString = JSON.stringify(data, null, 2)
					this.success = response.ok
				}).catch((error) => {
					this.success = false
					this.error = error.message || 'An error occurred while running the synchronization'
					console.error(error)
				}).finally(() => {
					this.loading = false
				})
		},
	},
}
</script>
<style>
div[class='modal-container']:has(.runSynchronization .SuccessMarker) {
	width: 900px !important;
}

.detailGrid {
	display: grid;
	grid-template-columns: 1fr 1fr;
	gap: 5px;
}

.detailTable {
	overflow-x: auto;
}

.detailTable > table {
	width: 100%;
	border: 1px solid grey;
	border-collapse: collapse;
}

.detailTable > table > tr > td,
.detailTable > table > tr > th {
	border: 1px solid grey;
	padding: 5px;
}
</style>

<style scoped>
.runButtonContainer {
	display: flex;
	justify-content: center;
	margin-block-start: 10px;
}

.SuccessMarker {
	display: none;
}

/* ================ */
/*    CodeMirror    */
/* ================ */
.codeMirrorContainer {
	margin-block-start: 6px;
}

.codeMirrorContainer :deep(.cm-content) {
	border-radius: 0 !important;
	border: none !important;
}
.codeMirrorContainer :deep(.cm-editor) {
	outline: none !important;
}
.codeMirrorContainer.light > .vue-codemirror {
	border: 1px dotted silver;
}
.codeMirrorContainer.dark > .vue-codemirror {
	border: 1px dotted grey;
}

/* value text color */
.codeMirrorContainer.light :deep(.ͼe) {
	color: #448c27;
}
.codeMirrorContainer.dark :deep(.ͼe) {
	color: #88c379;
}

/* text cursor */
.codeMirrorContainer :deep(.cm-content) * {
	cursor: text !important;
}

/* value number color */
.codeMirrorContainer.light :deep(.ͼd) {
	color: #c68447;
}
.codeMirrorContainer.dark :deep(.ͼd) {
	color: #d19a66;
}

/* value boolean color */
.codeMirrorContainer.light :deep(.ͼc) {
	color: #221199;
}
.codeMirrorContainer.dark :deep(.ͼc) {
	color: #260dd4;
}
</style>
