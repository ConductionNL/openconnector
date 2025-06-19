<script setup>
import { synchronizationStore, navigationStore, sourceStore, mappingStore, ruleStore } from '../../store/store.js'
import { Synchronization } from '../../entities/index.js'
</script>

<template>
	<NcModal ref="modalRef"
		label-id="editSynchronization"
		@close="closeModal">
		<div class="modalContent">
			<h2>{{ synchronizationItem.id ? 'Edit' : 'Add' }} Synchronization</h2>

			<!-- ====================== -->
			<!-- Open Register notecard -->
			<!-- ====================== -->
			<div v-if="!openRegisterInstalled && !openRegisterCloseAlert" class="openregister-notecard">
				<NcNoteCard
					:type="openRegisterIsAvailable ? 'info' : 'error'"
					:heading="openRegisterIsAvailable ? 'Open Register is not installed' : 'Failed to install Open Register'">
					<p>
						{{ openRegisterIsAvailable
							? 'Some features require Open Register to be installed'
							: 'This either means that Open Register is not available on this server or you need to confirm your password' }}
					</p>

					<div class="install-buttons">
						<NcButton v-if="openRegisterIsAvailable"
							aria-label="Install OpenRegister"
							size="small"
							type="primary"
							:loading="openRegisterLoading"
							@click="installOpenRegister">
							<template #icon>
								<CloudDownload :size="20" />
							</template>
							Install OpenRegister
						</NcButton>
						<NcButton
							aria-label="Install OpenRegister Manually"
							size="small"
							type="secondary"
							@click="openLink('/index.php/settings/apps/organization/openregister', '_blank')">
							<template #icon>
								<OpenInNew :size="20" />
							</template>
							Install OpenRegister Manually
						</NcButton>
					</div>
					<div class="close-button">
						<NcActions>
							<NcActionButton close-after-click @click="openRegisterCloseAlert = true">
								<template #icon>
									<Close :size="20" />
								</template>
								Close
							</NcActionButton>
						</NcActions>
					</div>
				</NcNoteCard>
			</div>

			<!-- ====================== -->
			<!-- Success/Error notecard -->
			<!-- ====================== -->
			<div v-if="success || error">
				<NcNoteCard v-if="success" type="success">
					<p>Synchronization successfully added</p>
				</NcNoteCard>
				<NcNoteCard v-if="error" type="error">
					<p>{{ error || 'An error occurred' }}</p>
				</NcNoteCard>
			</div>

			<!-- ====================== -->
			<!--          Form          -->
			<!-- ====================== -->
			<form v-if="!success" @submit.prevent="handleSubmit">
				<NcTextField :value.sync="synchronizationItem.name"
					label="Name"
					required />

				<NcTextArea
					resize="vertical"
					:value.sync="synchronizationItem.description"
					label="Description" />

				<NcTextArea
					resize="vertical"
					:value.sync="synchronizationItem.conditions"
					label="Conditions (json logic)" />

				<NcSelect v-bind="ruleOptions"
					v-model="ruleOptions.value"
					multiple
					:loading="rulesLoading"
					input-label="Rules" />
				<div>
					<NcSelect v-bind="typeOptions"
						v-model="typeOptions.value"
						:selectable="(option) => {
							return option.id === 'register/schema' ? openRegisterInstalled : true
						}"
						input-label="Source Type" />
				</div>

				<div>
					<NcSelect v-if="typeOptions.value?.id !== 'register/schema'"
						v-bind="sourceOptions"
						v-model="sourceOptions.sourceValue"
						required
						:loading="sourcesLoading"
						input-label="Source ID" />

					<div v-if="typeOptions.value?.id === 'register/schema'">
						<p>Source ID</p>

						<div class="css-fix-reg/schema">
							<NcSelect v-bind="registerOptions"
								v-model="registerOptions.sourceValue"
								:disabled="!openRegisterInstalled"
								input-label="Register" />
							<p>/</p>
							<NcSelect v-bind="selectedRegisterSourceValueSchemas"
								v-model="schemaOptions.sourceValue"
								:disabled="!openRegisterInstalled"
								input-label="Schema" />
						</div>
					</div>
				</div>

				<NcSelect v-bind="sourceTargetMappingOptions"
					v-model="sourceTargetMappingOptions.hashValue"
					:loading="sourceTargetMappingLoading"
					input-label="Source hash mapping" />

				<NcSelect v-bind="sourceTargetMappingOptions"
					v-model="sourceTargetMappingOptions.sourceValue"
					:loading="sourceTargetMappingLoading"
					input-label="Source target mapping" />

				<NcTextField :value.sync="synchronizationItem.sourceConfig.idPosition"
					label="(optional) Position of id in source object" />

				<NcTextField :value.sync="synchronizationItem.sourceConfig.resultsPosition"
					label="(optional) Position of results in source object" />

				<NcTextField :value.sync="synchronizationItem.sourceConfig.endpoint"
					label="(optional) Endpoint on which to fetch data" />

				<NcSelect v-bind="targetTypeOptions"
					v-model="targetTypeOptions.value"
					:selectable="(option) => {
						return option.id === 'register/schema' ? openRegisterInstalled : true
					}"
					input-label="Target Type" />

				<div>
					<NcSelect v-if="targetTypeOptions.value?.id === 'api'"
						v-bind="sourceOptions"
						v-model="sourceOptions.targetValue"
						:loading="sourcesLoading"
						input-label="Target ID" />

					<div v-if="targetTypeOptions.value?.id === 'register/schema'">
						<p>Target ID</p>

						<div class="css-fix-reg/schema">
							<NcSelect v-bind="registerOptions"
								v-model="registerOptions.value"
								:disabled="!openRegisterInstalled"
								input-label="Register" />
							<p>/</p>
							<NcSelect v-bind="selectedRegisterValueSchemas"
								v-model="schemaOptions.value"
								:disabled="!openRegisterInstalled"
								input-label="Schema" />
						</div>
					</div>
				</div>

				<NcSelect v-bind="sourceTargetMappingOptions"
					v-model="sourceTargetMappingOptions.targetValue"
					:loading="sourceTargetMappingLoading"
					input-label="Target source mapping" />
			</form>

			<NcButton v-if="!success"
				:disabled="loading
					|| !synchronizationItem.name
					|| (typeOptions.value?.id !== 'register/schema' && !sourceOptions.sourceValue?.id)
					// both register and schema need to be selected for register/schema target type
					|| (targetTypeOptions.value?.id === 'register/schema' && (!registerOptions.value?.id || !schemaOptions.value?.id))
					|| (typeOptions.value?.id === 'register/schema' && (!registerOptions.sourceValue?.id || !schemaOptions.sourceValue?.id))
					|| (targetTypeOptions.value?.id === 'api' && (!sourceOptions.targetValue))"
				type="primary"
				@click="editSynchronization()">
				<template #icon>
					<NcLoadingIcon v-if="loading" :size="20" />
					<ContentSaveOutline v-if="!loading" :size="20" />
				</template>
				Save
			</NcButton>
		</div>
	</NcModal>
</template>

<script>
import {
	NcButton,
	NcModal,
	NcTextField,
	NcTextArea,
	NcSelect,
	NcLoadingIcon,
	NcNoteCard,
	NcActions,
	NcActionButton,
} from '@nextcloud/vue'
import openLink from '../../services/openLink.js'

import ContentSaveOutline from 'vue-material-design-icons/ContentSaveOutline.vue'
import CloudDownload from 'vue-material-design-icons/CloudDownload.vue'
import OpenInNew from 'vue-material-design-icons/OpenInNew.vue'
import Close from 'vue-material-design-icons/Close.vue'

export default {
	name: 'EditSynchronization',
	components: {
		NcModal,
		NcButton,
		NcTextField,
		NcTextArea,
		NcSelect,
		NcLoadingIcon,
		NcNoteCard,
		NcActions,
		NcActionButton,
	},
	data() {
		return {
			/**
			 * Indicates if this is an edit modal or a create modal.
			 */
			IS_EDIT: !!synchronizationStore.synchronizationItem?.id,
			success: null, // Indicates if saving the synchronization was successful
			loading: false, // Indicates if saving the synchronization is in progress
			error: false,
			// synchronization item
			synchronizationItem: { // Initialize with empty fields
				name: '',
				description: '',
				conditions: '',
				sourceId: '',
				sourceType: '',
				sourceConfig: {
					idPosition: '',
					resultsPosition: '',
					endpoint: '',
					headers: {},
					query: {},
				},
				actions: [],
				sourceHashMapping: '',
				sourceTargetMapping: '',
				targetId: '',
				targetType: 'register/schema',
				targetConfig: {},
				targetSourceMapping: '',
			},
			// ============================= //
			// source options
			// ============================= //
			typeOptions: {
				options: [
					{ label: 'Database', id: 'database' },
					{ label: 'API', id: 'api' },
					{ label: 'File', id: 'file' },
					{ label: 'Register/Schema', id: 'register/schema' },
				],
				value: { label: 'API', id: 'api' }, // Default source type
			},
			sourcesLoading: false, // Indicates if the sources are loading
			sourceOptions: { // This should be populated with available sources
				options: [],
				sourceValue: null,
				targetValue: null,
			},
			sourceTargetMappingLoading: false, // Indicates if the mappings are loading
			sourceTargetMappingOptions: { // A list of mappings
				options: [],
				hashValue: null,
				sourceValue: null,
				targetValue: null,
			},
			// ============================= //
			// target options
			// ============================= //
			targetTypeOptions: {
				options: [
					{ label: 'Register/Schema', id: 'register/schema' },
					{ label: 'API', id: 'api' },
					// { label: 'Database', id: 'database' },
				],
				value: { label: 'API', id: 'api' }, // Default target type
			},
			// registerOptions
			registerLoading: false, // Indicates if the registers are loading
			registerOptions: {
				options: [],
				value: null,
				sourceValue: null,
			},
			// schemaOptions
			schemaLoading: false, // Indicates if the schemas are loading
			schemaOptions: {
				options: [],
				value: null,
				sourceValue: null,
			},
			// ============================= //
			// rule options
			// ============================= //
			rulesLoading: false, // Indicates if the rules are loading
			ruleOptions: {
				options: [],
				value: null,
			},
			// ============================= //
			// OpenRegister
			// ============================= //
			openRegisterInstalled: true, // Indicates if OpenRegister is installed
			openRegisterLoading: true, // Indicates if installing OpenRegister is in progress
			openRegisterIsAvailable: true, // Indicates if OpenRegister is available
			openRegisterCloseAlert: false, // Indicates if the OpenRegister alert should be closed
			// ============================= //
			closeTimeoutFunc: null, // Function to close the modal after a timeout
		}
	},
	computed: {
		selectedRegisterSourceValueSchemas() {
			return this.registerOptions?.sourceValue?.schemas || []
		},
		selectedRegisterValueSchemas() {
			return this.registerOptions?.value?.schemas || []
		},
	},
	watch: {
		'registerOptions.value': {
			handler() {
				this.schemaOptions.value = null
			},
			deep: true,
		},
		'registerOptions.sourceValue': {
			handler() {
				this.schemaOptions.sourceValue = null
			},
			deep: true,
		},
	},
	mounted() {
		if (this.IS_EDIT) {
			// If there is a synchronization item in the store, use it
			this.synchronizationItem = {
				...synchronizationStore.synchronizationItem,
				conditions: JSON.stringify(synchronizationStore.synchronizationItem.conditions),
			}

			// update targetTypeOptions with the synchronization item target type
			this.targetTypeOptions.value = this.targetTypeOptions.options.find(option => option.id === this.synchronizationItem.targetType)
			this.typeOptions.value = this.typeOptions.options.find(option => option.id === this.synchronizationItem.sourceType)
		}

		// Fetch sources, mappings, register, and schema
		this.getSources()
		this.getSourceTargetMappings()
		this.getRegisterWithSchemas()
		this.getRules()
	},
	methods: {
		/**
		 * Fetches the list of available sources from the source store and updates the source options.
		 * Sets the loading state to true while fetching and updates the source options with the fetched data.
		 * If a source is already selected, it sets it as the active source.
		 * If the target type is 'api', it sets the active target source.
		 */
		getSources() {
			this.sourcesLoading = true

			sourceStore.refreshSourceList()
				.then(({ entities }) => {
					const activeSourceSource = entities.find(source => source.id.toString() === this.synchronizationItem.sourceId.toString())

					let activeSourceTarget = null
					if (this.IS_EDIT && this.synchronizationItem.targetType === 'api') {
						activeSourceTarget = entities.find(source => source.id.toString() === this.synchronizationItem.targetId.toString())
					}

					this.sourceOptions = {
						options: entities.map(source => ({
							label: source.name,
							id: source.id,
						})),
						sourceValue: activeSourceSource
							? {
								label: activeSourceSource.name,
								id: activeSourceSource.id,
							}
							: null,
						targetValue: activeSourceTarget
							? {
								label: activeSourceTarget.name,
								id: activeSourceTarget.id,
							}
							: null,
					}
				})
				.finally(() => {
					this.sourcesLoading = false
				})
		},
		/**
		 * Fetches the list of source-target mappings from the mapping store and updates the mapping options.
		 * Sets the loading state to true while fetching and updates the mapping options with the fetched data.
		 * If a mapping is already selected, it sets it as the active source and target mapping.
		 */
		getSourceTargetMappings() {
			this.sourceTargetMappingLoading = true

			mappingStore.refreshMappingList()
				.then(({ entities }) => {
					const activeSourceMapping = entities.find(mapping => mapping.id.toString() === this.synchronizationItem.sourceTargetMapping.toString())
					const activeTargetMapping = entities.find(mapping => mapping.id.toString() === this.synchronizationItem.targetSourceMapping.toString())
					const sourceHashMapping = entities.find(mapping => mapping.id.toString() === this.synchronizationItem.sourceHashMapping.toString())

					this.sourceTargetMappingOptions = {
						options: entities.map(mapping => ({
							label: mapping.name,
							id: mapping.id,
						})),
						hashValue: sourceHashMapping
							? {
								label: sourceHashMapping.name,
								id: sourceHashMapping.id,
							}
							: null,
						sourceValue: activeSourceMapping
							? {
								label: activeSourceMapping.name,
								id: activeSourceMapping.id,
							}
							: null,
						targetValue: activeTargetMapping
							? {
								label: activeTargetMapping.name,
								id: activeTargetMapping.id,
							}
							: null,
					}
				})
				.finally(() => {
					this.sourceTargetMappingLoading = false
				})
		},
		/**
		 * Fetches the list of registers from the mapping store and updates the register options.
		 * Sets the loading state to true while fetching and updates the register options with the fetched data.
		 * If a register is already selected, it sets it as the active register.
		 * If OpenRegister is not installed, it updates the state accordingly.
		 *
		 * additionally it adds the schemas of a register to its options data,
		 * which'll be used to populate the schema options when you select a register.
		 */
		getRegisterWithSchemas() {
			this.registerLoading = true

			mappingStore.getMappingObjects()
				.then(({ data }) => {
					if (!data.openRegisters) {
						this.registerLoading = false
						this.openRegisterInstalled = false
						return
					}

					// registers
					const registers = data.availableRegisters

					let activeRegister = null
					if (this.IS_EDIT && this.synchronizationItem.targetType === 'register/schema') {
						const registerId = this.synchronizationItem.targetId.split('/')[0]
						activeRegister = registers.find(object => object.id.toString() === registerId.toString())
					}

					let activeSourceRegister = null
					if (this.IS_EDIT && this.synchronizationItem.sourceType === 'register/schema') {
						const registerId = this.synchronizationItem.sourceId.split('/')[0]
						activeSourceRegister = registers.find(object => object.id.toString() === registerId.toString())
					}

					// schemas
					const schemas = registers.map(register => register.schemas).flat()
						.filter(schema => typeof schema === 'object')

					let activeSchema = null
					if (this.IS_EDIT && this.synchronizationItem.targetType === 'register/schema') {
						const schemaId = this.synchronizationItem.targetId.split('/')[1]
						activeSchema = schemas.find(schema => schema.id.toString() === schemaId.toString())
					}

					let activeSourceSchema = null
					if (this.IS_EDIT && this.synchronizationItem.sourceType === 'register/schema') {
						const schemaId = this.synchronizationItem.sourceId.split('/')[1]
						activeSourceSchema = schemas.find(schema => schema.id.toString() === schemaId.toString())
					}

					// load registers (with schema's in options)
					this.registerOptions = {
						options: registers.map(object => ({
							label: object.title || object.name,
							id: object.id,
							schemas: {
								options: object.schemas.filter(schema => typeof schema === 'object').map(schema => ({
									label: schema.title || schema.name,
									id: schema.id,
								})),
							},
						})),
						value: activeRegister
							? {
								label: activeRegister.title || activeRegister.name,
								id: activeRegister.id,
								schemas: {
									options: activeRegister.schemas.filter(schema => typeof schema === 'object').map(schema => ({
										label: schema.title || schema.name,
										id: schema.id,
									})),
								},
							}
							: null,
						sourceValue: activeSourceRegister
							? {
								label: activeSourceRegister.title || activeSourceRegister.name,
								id: activeSourceRegister.id,
								schemas: {
									options: activeSourceRegister.schemas.filter(schema => typeof schema === 'object').map(schema => ({
										label: schema.title || schema.name,
										id: schema.id,
									})),
								},
							}
							: null,
					}

					// set active schema
					this.schemaOptions = {
						value: activeSchema
							? {
								label: activeSchema.title || activeSchema.name,
								id: activeSchema.id,
							}
							: null,
						sourceValue: activeSourceSchema
							? {
								label: activeSourceSchema.title || activeSourceSchema.name,
								id: activeSourceSchema.id,
							}
							: null,
					}
				})
				.finally(() => {
					this.registerLoading = false
				})
		},
		/**
		 * Fetches the list of available rules from the rules store and updates the rules options.
		 * Sets the loading state to true while fetching and updates the rules options with the fetched data.
		 * If a rules is already selected, it sets it as the active rules.
		 * If the target type is 'api', it sets the active target rules.
		 */
		getRules() {
			this.rulesLoading = true

			ruleStore.refreshRuleList()
				.then(() => {
					const rules = ruleStore.ruleList
					const activeRule = rules.filter(rule => this.synchronizationItem.actions.includes(rule.id))

					this.ruleOptions = {
						options: rules.map(rule => ({
							label: rule.name,
							id: rule.id,
						})),
						value: activeRule.map(rule => ({
							label: rule.name,
							id: rule.id,
						})),
					}
				})
				.finally(() => {
					this.rulesLoading = false
				})
		},
		/**
		 * Installs OpenRegister by sending a request to the server.
		 * Sets the loading state to true while the installation is in progress.
		 * Updates the state based on the success or failure of the installation.
		 * If the installation is successful, it fetches the register and schema options.
		 */
		async installOpenRegister() {
			this.openRegisterLoading = true

			console.info('Installing Open Register')
			const requesttoken = document.querySelector('head[data-requesttoken]').getAttribute('data-requesttoken')

			const forceResponse = await fetch('/index.php/settings/apps/force', {
				headers: {
					accept: 'application/json, text/plain, */*',
					'accept-language': 'en-US,en;q=0.9,nl;q=0.8',
					'cache-control': 'no-cache',
					'content-type': 'application/json',
					pragma: 'no-cache',
					requesttoken,
					'x-requested-with': 'XMLHttpRequest, XMLHttpRequest',
				},
				referrerPolicy: 'no-referrer',
				body: '{"appId":"openregister"}',
				method: 'POST',
				mode: 'cors',
				credentials: 'include',
			})

			if (!forceResponse.ok) {
				console.info('Failed to install Open Register')
				this.openRegisterIsAvailable = false
				this.openRegisterLoading = false
				return
			}

			const response = await fetch('/index.php/settings/apps/enable', {
				headers: {
					accept: '*/*',
					'accept-language': 'en-US,en;q=0.9,nl;q=0.8',
					'cache-control': 'no-cache',
					'content-type': 'application/json',
					pragma: 'no-cache',
					requesttoken,
					'x-requested-with': 'XMLHttpRequest, XMLHttpRequest',
				},
				referrerPolicy: 'no-referrer',
				body: '{"appIds":["openregister"],"groups":[]}',
				method: 'POST',
				mode: 'cors',
				credentials: 'include',
			})

			if (!response.ok) {
				console.info('Failed to install Open Register')
				this.openRegisterIsAvailable = false
			} else {
				console.info('Open Register installed')
				this.openRegisterInstalled = true
				this.getRegister()
				this.getSchema()
			}

			this.openRegisterLoading = false
		},
		/**
		 * Closes the modal and clears the timeout function.
		 */
		closeModal() {
			navigationStore.setModal(false)
			clearTimeout(this.closeTimeoutFunc)
		},
		/**
		 * Edits the synchronization by saving the synchronization item to the store.
		 * Sets the loading state to true while saving and updates the state based on the success or failure of the save operation.
		 * If the save operation is successful, it closes the modal after a timeout.
		 */
		editSynchronization() {
			this.loading = true

			let targetId = null
			if (this.targetTypeOptions.value?.id === 'register/schema') {
				targetId = `${this.registerOptions.value?.id}/${this.schemaOptions.value?.id}`
			} else if (this.targetTypeOptions.value?.id === 'api') {
				targetId = this.sourceOptions.targetValue?.id
			}

			let sourceId = null
			if (this.typeOptions.value?.id === 'register/schema') {
				sourceId = `${this.registerOptions.sourceValue?.id}/${this.schemaOptions.sourceValue?.id}`
			} else {
				sourceId = this.sourceOptions.sourceValue?.id
			}

			const synchronizationItem = new Synchronization({
				...this.synchronizationItem,
				sourceId: sourceId || null,
				sourceType: this.typeOptions.value?.id || null,
				sourceHashMapping: this.sourceTargetMappingOptions.hashValue?.id || null,
				sourceTargetMapping: this.sourceTargetMappingOptions.sourceValue?.id || null,
				conditions: this.synchronizationItem.conditions ? JSON.parse(this.synchronizationItem.conditions) : [],
				targetType: this.targetTypeOptions.value?.id || null,
				targetId: targetId || null,
				targetSourceMapping: this.sourceTargetMappingOptions.targetValue?.id || null,
				actions: this.ruleOptions.value ? this.ruleOptions.value.map(rule => rule.id) : [],
			})

			synchronizationStore.saveSynchronization(synchronizationItem)
				.then(({ response }) => {
					this.success = response.ok
					this.closeTimeoutFunc = setTimeout(this.closeModal, 2000)
				})
				.catch(error => {
					this.success = false
					this.error = error.message || 'Er is een fout opgetreden bij het opslaan van de synchronisatie'
				})
				.finally(() => {
					this.loading = false
				})
		},
	},
}
</script>

<style scoped>
/* Open Register notecard */
.openregister-notecard .notecard {
    position: relative;
}
.close-button {
    position: absolute;
    top: 5px;
    right: 5px;
}
.close-button .button-vue--vue-tertiary:hover:not(:disabled) {
    background-color: rgba(var(--color-info-rgb), 0.1);
}

.css-fix-reg\/schema {
    width: 100%;
    display: grid;
    grid-template-columns: auto 1fr auto;
}
.css-fix-reg\/schema .v-select {
    width: 100%;
}
.css-fix-reg\/schema p {
    align-self: end;
    margin-block-end: 10px;
}
</style>
