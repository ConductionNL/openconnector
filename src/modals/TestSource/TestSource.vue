<script setup>
import { sourceStore, navigationStore } from '../../store/store.js'
import { getTheme } from '../../services/getTheme.js'
</script>

<template>
	<NcModal v-if="navigationStore.modal === 'testSource'"
		ref="modalRef"
		label-id="testSource"
		@close="closeModal">
		<div class="modalContent">
			<h2>Test source</h2>

			<form @submit.prevent="handleSubmit">
				<div class="form-group">
					<div class="detailGrid">
						<NcSelect
							id="method"
							v-bind="methodOptions"
							v-model="methodOptions.value" />

						<NcSelect
							id="type"
							v-bind="typeOptions"
							v-model="typeOptions.value" />

						<NcTextField
							id="endpoint"
							label="Endpoint"
							:value.sync="testSourceItem.endpoint" />
					</div>
					<NcTextArea
						id="body"
						resize="vertical"
						label="Body"
						:value.sync="testSourceItem.body" />
				</div>
			</form>
			<div class="modalActionButton">
				<NcButton
					:disabled="loading"
					type="primary"
					@click="testSource()">
					<template #icon>
						<NcLoadingIcon v-if="loading" :size="20" />
						<Sync v-if="!loading" :size="20" />
					</template>
					Test connection
				</NcButton>
			</div>

			<NcNoteCard v-if="sourceStore.sourceTest && sourceStore.sourceTest.response.statusCode.toString().startsWith('2')" type="success">
				<p>The connection to the source was successful.</p>
			</NcNoteCard>
			<NcNoteCard v-if="(sourceStore.sourceTest && !sourceStore.sourceTest.response.statusCode.toString().startsWith('2')) || error" type="error">
				<p>An error occurred while testing the connection: {{ sourceStore.sourceTest ? sourceStore.sourceTest.response.statusMessage : error }}</p>
			</NcNoteCard>

			<div v-if="sourceStore.sourceTest">
				<div class="response-grid">
					<div class="response-item">
						<span class="response-label">Status:</span>
						<span class="response-value">{{ sourceStore.sourceTest.response.statusMessage }} ({{ sourceStore.sourceTest.response.statusCode }})</span>
					</div>
					<div class="response-item">
						<span class="response-label">Response time:</span>
						<span class="response-value">{{ sourceStore.sourceTest.response.responseTime }} ms</span>
					</div>
					<div class="response-item">
						<span class="response-label">Size:</span>
						<span class="response-value">{{ sourceStore.sourceTest.response.size }} bytes</span>
					</div>
					<div class="response-item">
						<span class="response-label">Remote IP:</span>
						<span class="response-value">{{ sourceStore.sourceTest.response.remoteIp || "-" }}</span>
					</div>
				</div>

				<div class="response-section">
					<h3 class="section-title">
						Headers
					</h3>
					<div :class="`codeMirrorContainer ${getTheme()}`">
						<CodeMirror
							v-model="responseHeaders"
							:extensions="editorExtensions"
							:basic="true"
							:read-only="true"
							:dark="getTheme() === 'dark'" />
					</div>
				</div>
				<div class="response-section">
					<h3 class="section-title">
						Body
					</h3>
					<div :class="`codeMirrorContainer ${getTheme()}`">
						<CodeMirror
							v-model="responseBody"
							:extensions="editorExtensions"
							:basic="true"
							:read-only="true"
							:dark="getTheme() === 'dark'" />
					</div>
				</div>
			</div>
		</div>
	</NcModal>
</template>

<script>
import {
	NcButton,
	NcModal,
	NcSelect,
	NcLoadingIcon,
	NcTextField,
	NcTextArea,
	NcNoteCard,
} from '@nextcloud/vue'
import CodeMirror from 'vue-codemirror6'
import { json } from '@codemirror/lang-json'
import { EditorView } from '@codemirror/view'
import Sync from 'vue-material-design-icons/Sync.vue'

export default {
	name: 'TestSource',
	components: {
		NcModal,
		NcButton,
		NcSelect,
		NcLoadingIcon,
		NcTextField,
		NcTextArea,
		NcNoteCard,
		CodeMirror,
	},
	data() {
		return {
			testSourceItem: {
				endpoint: '',
				body: '',
				method: '',
				type: '',
			},
			success: false,
			loading: false,
			error: false,
			typeOptions: {
				inputLabel: 'Type',
				options: [
					{ id: 'JSON', label: 'JSON' },
					{ id: 'XML', label: 'XML' },
					{ id: 'YAML', label: 'YAML' },
				],
				value: { id: 'JSON', label: 'JSON' },
			},
			methodOptions: {
				inputLabel: 'Method',
				options: [
					{ id: 'GET', label: 'GET' },
					{ id: 'POST', label: 'POST' },
					{ id: 'PUT', label: 'PUT' },
					{ id: 'DELETE', label: 'DELETE' },
				],
				value: { id: 'GET', label: 'GET' },
			},
		}
	},
	computed: {
		editorExtensions() {
			return [
				json(),
				EditorView.lineWrapping,
			]
		},
		responseBody() {
			return JSON.stringify(JSON.parse(sourceStore.sourceTest.response.body), null, 2)
		},
		responseHeaders() {
			return JSON.stringify(sourceStore.sourceTest.response.headers, null, 2)
		},
	},
	methods: {
		closeModal() {
			navigationStore.setModal(false)
			this.succes = false
			this.loading = false
			this.error = false
			this.testSourceItem = {
				endpoint: '',
				body: '',
				method: '',
				type: '',
			}
		},
		async testSource() {
			this.loading = true

			try {
				await sourceStore.testSource({
					...this.testSourceItem,
					method: this.methodOptions.value.id,
					type: this.typeOptions.value.id,
				})
				// Close modal or show success message
				this.success = true
				this.loading = false
				this.error = false
			} catch (error) {
				this.loading = false
				this.success = false
				this.error = error.message || 'Er is een fout opgetreden bij het opslaan van de bron'
				sourceStore.setSourceTest(false)
			}
		},
		prettifyJson(json) {
			if (!json) return ''
			try {
				return JSON.stringify(JSON.parse(json), null, 2)
			} catch (error) {
				return json
			}
		},
	},
}
</script>
<style>
.detail-grid {
	display: grid;
	grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
	gap: 16px;
}

.response-grid {
	display: grid;
	grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
	gap: 16px;
	margin-block-end: 24px;
}

.response-item {
	display: flex;
	background: var(--color-background-dark);
	border-radius: 8px;
	flex-direction: column;
	padding-block: 5px;
	gap: 4px;
}

.response-label {
	font-weight: 600;
	color: var(--color-text-light);
	font-size: 14px;
}

.response-value {
	color: var(--color-text);
	font-family: var(--font-mono);
}

.response-section {
	margin-block-start: 24px;
	text-align: left;
}
</style>

<style scoped>
.modalActionButton {
	display: flex;
	justify-content: end;
}
</style>
