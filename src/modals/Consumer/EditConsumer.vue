<script setup>
import { consumerStore, navigationStore } from '../../store/store.js'
import { Consumer } from '../../entities/index.js'
import { getTheme } from '../../services/getTheme.js'
</script>

<template>
	<NcModal
		v-if="navigationStore.modal === 'editConsumer'"
		ref="modalRef"
		label-id="editConsumer"
		@close="closeModal">
		<div class="modalContent">
			<h2>{{ consumerItem.id ? 'Edit' : 'Add' }} Consumer</h2>

			<div v-if="success !== null">
				<NcNoteCard v-if="success" type="success">
					<p>Consumer successfully {{ consumerItem.id ? 'updated' : 'added' }}</p>
				</NcNoteCard>
				<NcNoteCard v-if="error" type="error">
					<p>{{ error }}</p>
				</NcNoteCard>
			</div>

			<form v-if="success === null">
				<div class="form-group editConsumerForm">
					<NcTextField label="Name*" :value.sync="consumerItem.name" />

					<NcTextArea label="Description" :value.sync="consumerItem.description" />

					<NcTextArea
						label="Domains"
						:value.sync="consumerItem.domains"
						helper-text="Enter domains separated by commas (e.g. example.com, example.org)." />

					<NcTextArea
						label="IPs"
						:value.sync="consumerItem.ips"
						helper-text="Enter IP's separated by commas (e.g. 127.0.0.1, 1.1.1.1)."
						resize="none" />

					<NcSelect
						v-bind="authorizationTypeOptions"
						v-model="authorizationTypeOptions.value" />

					<div :class="`codeMirrorContainer ${getTheme()}`">
						<CodeMirror
							v-model="jsonContent"
							:basic="true"
							:dark="getTheme() === 'dark'"
							:lang="json()"
							:linter="jsonParseLinter()"
							placeholder="Enter your data in JSON format..." />
					</div>
					<NcButton class="prettifyButton" @click="prettifyJson">
						<template #icon>
							<AutoFix :size="20" />
						</template>
						Prettify
					</NcButton>
				</div>
			</form>
			<div class="buttonContainer">
				<NcButton
					v-if="success === null"
					:disabled="loading || !consumerItem.name"
					type="primary"
					@click="saveConsumerJSON">
					<template #icon>
						<NcLoadingIcon v-if="loading" :size="20" />
						<ContentSaveOutline v-else :size="20" />
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
	NcSelect,
	NcLoadingIcon,
	NcNoteCard,
	NcTextField,
	NcTextArea,
} from '@nextcloud/vue'
import ContentSaveOutline from 'vue-material-design-icons/ContentSaveOutline.vue'
import AutoFix from 'vue-material-design-icons/AutoFix.vue'
import { json, jsonParseLinter } from '@codemirror/lang-json'
import CodeMirror from 'vue-codemirror6'

export default {
	name: 'EditConsumer',
	components: {
		NcModal,
		NcButton,
		NcSelect,
		NcLoadingIcon,
		NcNoteCard,
		NcTextField,
		NcTextArea,
		CodeMirror,
		ContentSaveOutline,
		AutoFix,
	},
	data() {
		return {
			consumerItem: {
				id: null,
				name: '',
				description: '',
				domains: '',
				ips: '',
				authorizationType: '',
				authorizationConfiguration: '',
			},
			success: null,
			loading: false,
			error: false,
			authorizationTypeOptions: {
				inputLabel: 'Authorization Type',
				options: [
					{ label: 'none' },
					{ label: 'basic' },
					{ label: 'bearer' },
					{ label: 'apiKey' },
					{ label: 'oauth2' },
					{ label: 'jwt' },
				],
				value: { label: 'none' },
			},
			hasUpdated: false,
			closeTimeoutFunc: null,
		}
	},
	computed: {
		jsonContent: {
			get() {
				return JSON.stringify(this.consumerItem, null, 2)
			},
			set(newVal) {
				try {
					const parsed = JSON.parse(newVal)
					this.consumerItem = {
						...this.consumerItem,
						...parsed,
						domains: Array.isArray(parsed.domains)
							? parsed.domains.join(', ')
							: (parsed.domains || ''),
						ips: Array.isArray(parsed.ips)
							? parsed.ips.join(', ')
							: (parsed.ips || ''),
					}
				} catch (e) {
				}
			},
		},
	},
	mounted() {
		this.initializeConsumerItem()
	},
	updated() {
		if (navigationStore.modal === 'editConsumer' && !this.hasUpdated) {
			this.initializeConsumerItem()
			this.hasUpdated = true
		}
	},
	methods: {
		initializeConsumerItem() {
			const item = consumerStore.getConsumerItem()
			if (item?.id) {
				this.consumerItem = {
					...item,
					domains: Array.isArray(item.domains)
						? item.domains.join(', ')
						: item.domains,
					ips: Array.isArray(item.ips)
						? item.ips.join(', ')
						: item.ips,
				}
				if (this.authorizationTypeOptions.options
					.map(i => i.label)
					.includes(item.authorizationType)) {
					this.authorizationTypeOptions.value = { label: item.authorizationType }
				}
			}
		},

		prettifyJson() {
			try {
				this.$set(this, 'jsonContent', JSON.stringify(JSON.parse(this.jsonContent), null, 2))
			} catch (e) {
				this.error = 'Invalid JSON'
			}
		},

		closeModal() {
			navigationStore.setModal(false)
			clearTimeout(this.closeTimeoutFunc)
			this.success = null
			this.loading = false
			this.error = false
			this.hasUpdated = false
			this.consumerItem = {
				id: null,
				name: '',
				description: '',
				domains: '',
				ips: '',
				authorizationType: '',
				authorizationConfiguration: '',
			}
			this.authorizationTypeOptions.value = { label: 'none' }
		},

		saveConsumerJSON() {
			this.editConsumer()
		},

		async editConsumer() {
			this.loading = true
			const newConsumer = new Consumer({
				...this.consumerItem,
				domains: this.consumerItem.domains.trim().split(/ *, */g).filter(Boolean),
				ips: this.consumerItem.ips.trim().split(/ *, */g).filter(Boolean),
				authorizationType: this.authorizationTypeOptions.value.label,
				authorizationConfiguration: [['']],
			})
			consumerStore.saveConsumer(newConsumer)
				.then(({ response }) => {
					this.success = response.ok
					this.closeTimeoutFunc = setTimeout(this.closeModal, 2000)
				})
				.catch((e) => {
					this.success = false
					this.error = e.message || 'An error occurred while saving the consumer'
				})
				.finally(() => {
					this.loading = false
				})
		},
	},
}
</script>

<style lang="css">
.editConsumerForm .textarea__helper-text-message {
	padding-block: 4px;
	padding-inline: var(--border-radius-large);
	display: flex;
	align-items: center;
	color: var(--color-text-maxcontrast);
}
</style>

<style scoped>
.codeMirrorContainer {
	margin-block-start: 20px;
	text-align: left;
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

.buttonContainer {
	display: flex;
	gap: 10px;
	flex-direction: row-reverse;
	margin-top: 15px;
}
</style>
