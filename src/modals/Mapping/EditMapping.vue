<script setup>
import { mappingStore, navigationStore } from '../../store/store.js'
import { Mapping } from '../../entities/index.js'
</script>

<template>
	<NcModal v-if="navigationStore.modal === 'editMapping'"
		ref="modalRef"
		label-id="editMapping"
		size="large"
		:can-close="true"
		:width="1200"
		:name="mappingStore.mappingItem?.id ? 'Edit Mapping' : 'Create New Mapping'"
		@close="closeModal">
		<div class="modalContent">
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
					<p>Mapping successfully {{ mappingStore.mappingItem?.id ? 'updated' : 'created' }}</p>
				</NcNoteCard>
				<NcNoteCard v-if="error" type="error">
					<p>{{ error || 'An error occurred' }}</p>
				</NcNoteCard>
			</div>

			<!-- ====================== -->
			<!--    Three-Column Layout  -->
			<!-- ====================== -->
			<div v-if="!success" class="mapping-layout">
				<!-- Input Section -->
				<div class="mapping-section input-section">
					<div class="section-header">
						<DatabaseArrowRightOutline :size="24" />
						<h3>Input</h3>
					</div>
					<div class="section-content">
						<div class="info-card">
							<p class="section-description">
								Configure test input data for mapping validation
							</p>
						</div>

						<!-- Test Input -->
						<div class="subsection">
							<h4>Test Input Object</h4>
							<div class="form-group">
								<NcTextArea
									:value.sync="inputObject.value"
									resize="vertical"
									label="Test Input (JSON)"
									placeholder="Enter JSON object to test mapping"
									:error="!validJson(inputObject.value)"
									:helper-text="!validJson(inputObject.value) ? 'Invalid JSON' : ''"
									@input="updateInputObject" />
							</div>
						</div>
					</div>
				</div>

				<!-- Transformation Section -->
				<div class="mapping-section transformation-section">
					<!-- General Card -->
					<div class="center-card">
						<div class="section-header">
							<CogOutline :size="24" />
							<h3>General</h3>
						</div>
						<div class="section-content">
							<form @submit.prevent="handleSubmit">
								<div class="form-group">
									<NcTextField
										:value.sync="mappingItem.name"
										label="Name"
										placeholder="Enter mapping name"
										required />

									<NcTextArea
										resize="vertical"
										:value.sync="mappingItem.description"
										label="Description"
										placeholder="Describe what this mapping does" />
								</div>
							</form>
						</div>
					</div>

					<!-- Data Flow Arrow -->
					<div class="data-flow">
						<div class="flow-step">
							<DatabaseArrowRightOutline :size="20" />
							<span>Input</span>
						</div>
						<ArrowRight :size="20" class="flow-arrow" />
						<div class="flow-step">
							<SwapHorizontal :size="20" />
							<span>Transform</span>
						</div>
						<ArrowRight :size="20" class="flow-arrow" />
						<div class="flow-step">
							<DatabaseArrowLeftOutline :size="20" />
							<span>Output</span>
						</div>
					</div>

					<!-- Transform Card -->
					<div class="center-card">
						<div class="section-header">
							<SwapHorizontal :size="24" />
							<h3>Transform</h3>
						</div>
						<div class="section-content">
							<BTabs content-class="mt-3" justified>
								<!-- Mapping Tab -->
								<BTab title="Mapping">
									<div class="table-container">
										<table class="statisticsTable">
											<thead>
												<tr>
													<th>Target Property</th>
													<th>Source Property/Template</th>
													<th>Actions</th>
												</tr>
											</thead>
											<tbody>
												<tr v-for="(template, property) in mappingRules" :key="property">
													<td>{{ property }}</td>
													<td class="template-cell">
														{{ template }}
													</td>
													<td class="actions-cell">
														<NcActions>
															<NcActionButton @click="editMappingRule(property, template)">
																<template #icon>
																	<Pencil :size="20" />
																</template>
																Edit
															</NcActionButton>
															<NcActionButton @click="deleteMappingRule(property)">
																<template #icon>
																	<Delete :size="20" />
																</template>
																Delete
															</NcActionButton>
														</NcActions>
													</td>
												</tr>
											</tbody>
										</table>
										<NcButton type="primary" @click="addMappingRule">
											<template #icon>
												<Plus :size="20" />
											</template>
											Add Mapping Rule
										</NcButton>
									</div>
								</BTab>

								<!-- Cast Tab -->
								<BTab title="Cast">
									<div class="table-container">
										<table class="statisticsTable">
											<thead>
												<tr>
													<th>Property</th>
													<th>Cast Type</th>
													<th>Actions</th>
												</tr>
											</thead>
											<tbody>
												<tr v-for="(castType, property) in castRules" :key="property">
													<td>{{ property }}</td>
													<td>{{ castType }}</td>
													<td class="actions-cell">
														<NcActions>
															<NcActionButton @click="editCastRule(property, castType)">
																<template #icon>
																	<Pencil :size="20" />
																</template>
																Edit
															</NcActionButton>
															<NcActionButton @click="deleteCastRule(property)">
																<template #icon>
																	<Delete :size="20" />
																</template>
																Delete
															</NcActionButton>
														</NcActions>
													</td>
												</tr>
											</tbody>
										</table>
										<NcButton type="primary" @click="addCastRule">
											<template #icon>
												<Plus :size="20" />
											</template>
											Add Cast Rule
										</NcButton>
									</div>
								</BTab>

								<!-- Unset Tab -->
								<BTab title="Unset">
									<div class="table-container">
										<table class="statisticsTable">
											<thead>
												<tr>
													<th>Property</th>
													<th>Actions</th>
												</tr>
											</thead>
											<tbody>
												<tr v-for="(property, index) in unsetRules" :key="index">
													<td>{{ property }}</td>
													<td class="actions-cell">
														<NcActions>
															<NcActionButton @click="editUnsetRule(index, property)">
																<template #icon>
																	<Pencil :size="20" />
																</template>
																Edit
															</NcActionButton>
															<NcActionButton @click="deleteUnsetRule(index)">
																<template #icon>
																	<Delete :size="20" />
																</template>
																Delete
															</NcActionButton>
														</NcActions>
													</td>
												</tr>
											</tbody>
										</table>
										<NcButton type="primary"
											:disabled="!mappingItem.passThrough"
											@click="addUnsetRule">
											<template #icon>
												<Plus :size="20" />
											</template>
											Add Unset Property
										</NcButton>
										<p v-if="!mappingItem.passThrough" class="unset-disabled-note">
											Unset properties can only be configured when Pass Through is enabled.
										</p>
									</div>
								</BTab>

								<!-- Options Tab -->
								<BTab title="Options">
									<div class="options-container">
										<div class="form-group">
											<span class="flex-container">
												<NcCheckboxRadioSwitch
													:checked.sync="mappingItem.passThrough">
													Pass Through
												</NcCheckboxRadioSwitch>
												<a v-tooltip="'When turning passThrough on, all data from the original object is copied to the new object (passed through the mapper)'"
													href="https://commongateway.github.io/CoreBundle/pages/Features/Mappings"
													target="_blank">
													<HelpCircleOutline :size="20" />
												</a>
											</span>
										</div>
									</div>
								</BTab>
							</BTabs>
						</div>
					</div>

					<!-- Mapping Rule Dialog -->
					<NcModal v-if="showMappingDialog"
						:name="editingMappingRule ? 'Edit Mapping Rule' : 'Add Mapping Rule'"
						@close="closeMappingDialog">
						<div class="dialog-content">
							<h3>{{ editingMappingRule ? 'Edit Mapping Rule' : 'Add Mapping Rule' }}</h3>
							<div class="form-group">
								<NcTextField
									:value.sync="mappingDialogData.property"
									label="Target Property"
									placeholder="Enter target property name"
									required />
								<NcTextArea
									:value.sync="mappingDialogData.template"
									label="Source Property/Template"
									placeholder="Enter Twig template (e.g., values['source.field'] )"
									resize="vertical"
									required />
							</div>
							<div class="dialog-actions">
								<NcButton type="secondary" @click="closeMappingDialog">
									Cancel
								</NcButton>
								<NcButton type="primary" @click="saveMappingRule">
									Save
								</NcButton>
							</div>
						</div>
					</NcModal>

					<!-- Cast Rule Dialog -->
					<NcModal v-if="showCastDialog"
						:name="editingCastRule ? 'Edit Cast Rule' : 'Add Cast Rule'"
						@close="closeCastDialog">
						<div class="dialog-content">
							<h3>{{ editingCastRule ? 'Edit Cast Rule' : 'Add Cast Rule' }}</h3>
							<div class="form-group">
								<NcTextField
									:value.sync="castDialogData.property"
									label="Property"
									placeholder="Enter property name"
									required />
								<NcSelect
									:value.sync="castDialogData.castType"
									:options="castTypeOptions"
									label="Cast Type"
									required />
							</div>
							<div class="dialog-actions">
								<NcButton type="secondary" @click="closeCastDialog">
									Cancel
								</NcButton>
								<NcButton type="primary" @click="saveCastRule">
									Save
								</NcButton>
							</div>
						</div>
					</NcModal>

					<!-- Unset Rule Dialog -->
					<NcModal v-if="showUnsetDialog"
						:name="editingUnsetRule !== null ? 'Edit Unset Property' : 'Add Unset Property'"
						@close="closeUnsetDialog">
						<div class="dialog-content">
							<h3>{{ editingUnsetRule !== null ? 'Edit Unset Property' : 'Add Unset Property' }}</h3>
							<div class="form-group">
								<NcTextField
									:value.sync="unsetDialogData.property"
									label="Property"
									placeholder="Enter property name to unset"
									required />
							</div>
							<div class="dialog-actions">
								<NcButton type="secondary" @click="closeUnsetDialog">
									Cancel
								</NcButton>
								<NcButton type="primary" @click="saveUnsetRule">
									Save
								</NcButton>
							</div>
						</div>
					</NcModal>
				</div>

				<!-- Output Section -->
				<div class="mapping-section output-section">
					<div class="section-header">
						<DatabaseArrowLeftOutline :size="24" />
						<h3>Output</h3>
					</div>
					<div class="section-content">
						<div class="info-card">
							<p class="section-description">
								View mapping test results and validation status
							</p>
						</div>

						<!-- Schema Selection -->
						<div class="subsection">
							<h4>Validation Schema</h4>
							<div class="form-group">
								<NcSelect v-bind="schemaOptions"
									v-model="schemaOptions.value"
									input-label="Schema (Optional)"
									:loading="schemasLoading"
									:disabled="!openRegisterInstalled">
									<template #no-options="{ loading: isLoading }">
										<p v-if="isLoading">
											Loading...
										</p>
										<p v-else-if="!schemaOptions.options?.length">
											No schemas available
										</p>
									</template>
									<template #option="{ label, fullSchema }">
										<div class="schema-option">
											<FileTreeOutline :size="25" />
											<span>
												<h6>{{ label }}</h6>
												{{ fullSchema.summary || 'No description' }}
											</span>
										</div>
									</template>
								</NcSelect>
							</div>
						</div>

						<!-- Test Status -->
						<div v-if="testResult.success !== null" class="test-status">
							<NcNoteCard v-if="testResult.success" type="success">
								<p>Mapping test completed successfully</p>
							</NcNoteCard>
							<NcNoteCard v-if="testResult.success === false" type="error">
								<p>Mapping test failed</p>
							</NcNoteCard>
							<NcNoteCard v-if="testResult.error" type="error">
								<p>{{ testResult.error }}</p>
							</NcNoteCard>
						</div>

						<!-- Validation Status -->
						<div v-if="testResult.result?.isValid !== undefined" class="validation-status">
							<p v-if="testResult.result.isValid" class="valid">
								<NcIconSvgWrapper inline :path="mdiCheckCircle" />
								Result is valid
							</p>
							<p v-if="!testResult.result.isValid" class="invalid">
								<NcIconSvgWrapper inline :path="mdiCloseCircle" />
								Result is invalid
							</p>
						</div>

						<!-- Validation Errors -->
						<div v-if="Object.keys(testResult.result?.validationErrors || {}).length" class="validation-errors">
							<h4>Validation Errors</h4>
							<table>
								<thead>
									<tr>
										<th>Field</th>
										<th>Errors</th>
									</tr>
								</thead>
								<tbody>
									<tr v-for="(errors, field) in testResult.result.validationErrors" :key="field">
										<td>{{ field }}</td>
										<td>
											<ul>
												<li v-for="errorMessage in errors" :key="errorMessage">
													{{ errorMessage }}
												</li>
											</ul>
										</td>
									</tr>
								</tbody>
							</table>
						</div>

						<!-- Result Output -->
						<div v-if="testResult.result?.resultObject" class="result-output">
							<h4>Mapped Result</h4>
							<div class="result-container">
								<pre>{{ JSON.stringify(testResult.result.resultObject, null, 2) }}</pre>
							</div>
						</div>

						<!-- Save Object Section -->
						<div v-if="testResult.result?.resultObject && !Object.keys(testResult.result?.validationErrors || {}).length" class="save-object">
							<h4>Save Result</h4>
							<div class="form-group">
								<NcSelect v-bind="registerOptions"
									v-model="registerOptions.value"
									input-label="Register"
									:loading="registersLoading"
									:disabled="!openRegisterInstalled || saveObjectLoading">
									<template #no-options="{ loading: isLoading }">
										<p v-if="isLoading">
											Loading...
										</p>
										<p v-else-if="!registerOptions.options?.length">
											No registers available
										</p>
									</template>
									<template #option="{ label, fullRegister }">
										<div class="register-option">
											<DatabaseOutline :size="25" />
											<span>
												<h6>{{ label }}</h6>
												{{ fullRegister.description || 'No description' }}
											</span>
										</div>
									</template>
								</NcSelect>

								<NcButton :disabled="saveObjectLoading || !testResult.result.isValid || !schemaOptions.value?.id || !registerOptions.value?.id || !testResult.result?.resultObject"
									type="primary"
									@click="saveObject">
									<template #icon>
										<NcLoadingIcon v-if="saveObjectLoading" :size="20" />
										<ContentSaveOutline v-if="!saveObjectLoading" :size="20" />
									</template>
									Save to Register
								</NcButton>
							</div>

							<div v-if="saveObjectResult.success !== null" class="save-status">
								<NcNoteCard v-if="saveObjectResult.success" type="success">
									<p>Object saved successfully to register</p>
								</NcNoteCard>
								<NcNoteCard v-if="saveObjectResult.success === false" type="error">
									<p>{{ saveObjectResult.error || 'Failed to save object' }}</p>
								</NcNoteCard>
							</div>
						</div>
					</div>
				</div>
			</div>

			<!-- Action Buttons -->
			<div v-if="!success" class="modal-actions">
				<NcButton :disabled="testLoading || !validJson(inputObject.value) || !validJson(mappingItem.mapping) || !validJson(mappingItem.cast, true)"
					type="secondary"
					@click="testMapping">
					<template #icon>
						<NcLoadingIcon v-if="testLoading" :size="20" />
						<TestTube v-if="!testLoading" :size="20" />
					</template>
					Test
				</NcButton>
				<NcButton :disabled="loading || !mappingItem.name || !validJson(mappingItem.mapping) || !validJson(mappingItem.cast, true)"
					type="primary"
					@click="editMapping">
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
	NcTextArea,
	NcCheckboxRadioSwitch,
	NcSelect,
	NcActions,
	NcActionButton,
	NcIconSvgWrapper,
} from '@nextcloud/vue'

import { BTabs, BTab } from 'bootstrap-vue'

import { mdiCheckCircle, mdiCloseCircle } from '@mdi/js'

import ContentSaveOutline from 'vue-material-design-icons/ContentSaveOutline.vue'
import HelpCircleOutline from 'vue-material-design-icons/HelpCircleOutline.vue'
import CogOutline from 'vue-material-design-icons/CogOutline.vue'
import TestTube from 'vue-material-design-icons/TestTube.vue'
import FileTreeOutline from 'vue-material-design-icons/FileTreeOutline.vue'
import DatabaseOutline from 'vue-material-design-icons/DatabaseOutline.vue'
import DatabaseArrowRightOutline from 'vue-material-design-icons/DatabaseArrowRightOutline.vue'
import DatabaseArrowLeftOutline from 'vue-material-design-icons/DatabaseArrowLeftOutline.vue'
import ArrowRight from 'vue-material-design-icons/ArrowRight.vue'
import SwapHorizontal from 'vue-material-design-icons/SwapHorizontal.vue'
import CloudDownload from 'vue-material-design-icons/CloudDownload.vue'
import OpenInNew from 'vue-material-design-icons/OpenInNew.vue'
import Close from 'vue-material-design-icons/Close.vue'
import Pencil from 'vue-material-design-icons/Pencil.vue'
import Delete from 'vue-material-design-icons/Delete.vue'
import Plus from 'vue-material-design-icons/Plus.vue'

import openLink from '../../services/openLink.js'

export default {
	name: 'EditMapping',
	components: {
		NcModal,
		NcButton,
		NcLoadingIcon,
		NcNoteCard,
		NcTextField,
		NcTextArea,
		NcCheckboxRadioSwitch,
		NcSelect,
		NcActions,
		NcActionButton,
		NcIconSvgWrapper,
		BTabs,
		BTab,
		// Icons
		ContentSaveOutline,
		HelpCircleOutline,
		CogOutline,
		TestTube,
		FileTreeOutline,
		DatabaseOutline,
		DatabaseArrowRightOutline,
		DatabaseArrowLeftOutline,
		ArrowRight,
		SwapHorizontal,
		CloudDownload,
		OpenInNew,
		Close,
		Pencil,
		Delete,
		Plus,
	},
	setup() {
		return {
			mdiCheckCircle,
			mdiCloseCircle,
		}
	},
	data() {
		return {
			// Basic mapping data
			mappingItem: {
				name: '',
				description: '',
				mapping: '{}',
				cast: '{}',
				unset: '',
				passThrough: false,
			},
			success: null,
			loading: false,
			error: false,
			hasUpdated: false,
			closeTimeoutFunc: null,

			// Test functionality
			inputObject: {
				value: '',
				isValid: false,
			},
			testLoading: false,
			testResult: {
				result: {},
				success: null,
				error: false,
			},

			// Schema options
			schemasLoading: false,
			schemaOptions: {
				options: [],
				value: null,
			},

			// Register options for saving
			registersLoading: false,
			registerOptions: {
				options: [],
				value: null,
			},

			// Save object functionality
			saveObjectLoading: false,
			saveObjectResult: {
				success: null,
				error: '',
			},

			// OpenRegister status
			openRegisterInstalled: true,
			openRegisterLoading: false,
			openRegisterIsAvailable: true,
			openRegisterCloseAlert: false,

			// Dialog states
			showMappingDialog: false,
			showCastDialog: false,
			showUnsetDialog: false,

			// Dialog data
			mappingDialogData: { property: '', template: '' },
			castDialogData: { property: '', castType: '' },
			unsetDialogData: { property: '' },

			// Editing states
			editingMappingRule: false,
			editingCastRule: false,
			editingUnsetRule: null,

			// Cast type options
			castTypeOptions: [
				{ id: 'string', label: 'String' },
				{ id: 'integer', label: 'Integer' },
				{ id: 'float', label: 'Float' },
				{ id: 'boolean', label: 'Boolean' },
				{ id: 'array', label: 'Array' },
				{ id: 'jsonToArray', label: 'JSON to Array' },
				{ id: 'htmlDecode', label: 'HTML Decode' },
			],
		}
	},
	computed: {
		/**
		 * Parse mapping JSON into object for table display
		 */
		mappingRules() {
			try {
				return JSON.parse(this.mappingItem.mapping || '{}')
			} catch {
				return {}
			}
		},

		/**
		 * Parse cast JSON into object for table display
		 */
		castRules() {
			try {
				return JSON.parse(this.mappingItem.cast || '{}')
			} catch {
				return {}
			}
		},

		/**
		 * Parse unset string into array for table display
		 */
		unsetRules() {
			if (!this.mappingItem.unset) return []
			return this.mappingItem.unset.split(',').map(item => item.trim()).filter(item => item)
		},
	},
	mounted() {
		this.initializeMappingItem()
		this.fetchSchemas()
		this.fetchRegisters()
	},
	updated() {
		if (navigationStore.modal === 'editMapping' && !this.hasUpdated) {
			this.initializeMappingItem()
			this.hasUpdated = true
		}
	},
	methods: {
		/**
		 * Initialize the mapping item with data from the store or default values
		 */
		initializeMappingItem() {
			if (mappingStore.mappingItem?.id) {
				this.mappingItem = {
					...mappingStore.mappingItem,
					mapping: JSON.stringify(mappingStore.mappingItem.mapping || {}, null, 2),
					cast: JSON.stringify(mappingStore.mappingItem.cast || {}, null, 2),
					unset: Array.isArray(mappingStore.mappingItem.unset)
						? mappingStore.mappingItem.unset.join(', ')
						: (mappingStore.mappingItem.unset || ''),
				}
			} else {
				this.mappingItem = {
					name: '',
					description: '',
					mapping: '{}',
					cast: '{}',
					unset: '',
					passThrough: false,
				}
			}
		},

		/**
		 * Fetch available schemas from OpenRegister
		 */
		async fetchSchemas() {
			this.schemasLoading = true

			try {
				const response = await fetch('/index.php/apps/openregister/api/schemas', {
					headers: {
						accept: '*/*',
						'accept-language': 'en-US,en;q=0.9,nl;q=0.8',
						'cache-control': 'no-cache',
						pragma: 'no-cache',
						'x-requested-with': 'XMLHttpRequest',
					},
					referrerPolicy: 'no-referrer',
					method: 'GET',
					mode: 'cors',
					credentials: 'include',
				})

				if (!response.ok) {
					this.openRegisterInstalled = false
					return
				}

				const responseData = (await response.json()).results

				this.schemaOptions = {
					options: responseData.map((schema) => ({
						id: schema.id,
						label: schema.title,
						fullSchema: schema,
					})),
					value: null,
				}
			} catch (error) {
				console.error('Failed to fetch schemas:', error)
				this.openRegisterInstalled = false
			} finally {
				this.schemasLoading = false
			}
		},

		/**
		 * Fetch available registers for saving objects
		 */
		async fetchRegisters() {
			this.registersLoading = true

			try {
				const { data } = await mappingStore.getMappingObjects()

				this.openRegisterInstalled = data.openRegisters
				if (!data.openRegisters) return

				this.registerOptions = {
					options: data.availableRegisters.map((register) => ({
						id: register.id,
						label: register.title,
						fullRegister: register,
					})),
					value: null,
				}
			} catch (error) {
				console.error('Failed to fetch registers:', error)
				this.openRegisterInstalled = false
			} finally {
				this.registersLoading = false
			}
		},

		/**
		 * Install OpenRegister
		 */
		async installOpenRegister() {
			this.openRegisterLoading = true

			try {
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
					this.openRegisterIsAvailable = false
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
					this.openRegisterIsAvailable = false
				} else {
					this.openRegisterInstalled = true
					this.fetchSchemas()
					this.fetchRegisters()
				}
			} catch (error) {
				console.error('Failed to install OpenRegister:', error)
				this.openRegisterIsAvailable = false
			} finally {
				this.openRegisterLoading = false
			}
		},

		/**
		 * Update input object validation status
		 * @param {Event} event - The input event object
		 */
		updateInputObject(event) {
			this.inputObject = {
				value: event.target.value,
				isValid: this.validJson(event.target.value),
			}
		},

		/**
		 * Test the mapping with the provided input
		 */
		async testMapping() {
			this.testLoading = true
			this.testResult = {
				result: {},
				success: null,
				error: false,
			}

			try {
				const mappingData = {
					...mappingStore.mappingItem,
					name: this.mappingItem.name,
					description: this.mappingItem.description,
					mapping: JSON.parse(this.mappingItem.mapping),
					cast: this.mappingItem.cast ? JSON.parse(this.mappingItem.cast) : null,
					unset: this.mappingItem.unset.split(/ *, */g).filter(Boolean),
					passThrough: this.mappingItem.passThrough,
				}

				const { response, data } = await mappingStore.testMapping({
					mapping: mappingData,
					inputObject: JSON.parse(this.inputObject.value),
					schema: this.schemaOptions.value?.id,
				})

				this.testResult.success = response.ok
				this.testResult.result = data
			} catch (error) {
				this.testResult.error = error.message || 'An error occurred while testing the mapping'
			} finally {
				this.testLoading = false
			}
		},

		/**
		 * Save the test result object to a register
		 */
		async saveObject() {
			this.saveObjectLoading = true
			this.saveObjectResult = {
				success: null,
				error: '',
			}

			try {
				const { response } = await mappingStore.saveMappingObject({
					object: this.testResult.result.resultObject,
					register: this.registerOptions.value.id,
					schema: this.schemaOptions.value.id,
				})

				this.saveObjectResult.success = response.ok
			} catch (error) {
				console.error('Failed to save object:', error)
				this.saveObjectResult.success = false
				this.saveObjectResult.error = error.message || 'An error occurred'
			} finally {
				this.saveObjectLoading = false

				// Clear status after 3 seconds
				setTimeout(() => {
					this.saveObjectResult = {
						success: null,
						error: '',
					}
				}, 3000)
			}
		},

		/**
		 * Reset mapping to original values
		 */
		resetMapping() {
			this.initializeMappingItem()
			this.testResult = {
				result: {},
				success: null,
				error: false,
			}
			this.inputObject = {
				value: '',
				isValid: false,
			}
		},

		/**
		 * Close the modal and reset state
		 */
		closeModal() {
			navigationStore.setModal(false)
			clearTimeout(this.closeTimeoutFunc)
			this.success = null
			this.loading = false
			this.error = false
			this.hasUpdated = false
			this.resetMapping()
		},

		/**
		 * Save the mapping
		 */
		async editMapping() {
			this.loading = true

			try {
				const mappingItem = new Mapping({
					...this.mappingItem,
					mapping: JSON.parse(this.mappingItem.mapping),
					cast: JSON.parse(this.mappingItem.cast),
					unset: this.mappingItem.unset.split(/ *, */g).filter(Boolean),
				})

				const { response } = await mappingStore.saveMapping(mappingItem)

				this.success = response.ok
				if (this.success) {
					this.closeTimeoutFunc = setTimeout(this.closeModal, 2000)
				}
			} catch (error) {
				this.error = error.message || 'An error occurred while saving the mapping'
			} finally {
				this.loading = false
			}
		},

		/**
		 * Validate JSON string
		 * @param {string} jsonString - The JSON string to validate
		 * @param {boolean} optional - Whether the field is optional
		 * @return {boolean} - Whether the JSON is valid
		 */
		validJson(jsonString, optional = false) {
			if (optional && !jsonString) {
				return true
			}

			try {
				JSON.parse(jsonString)
				return true
			} catch (e) {
				return false
			}
		},

		// Mapping Rule Dialog Methods
		/**
		 * Open dialog to add a new mapping rule
		 */
		addMappingRule() {
			this.mappingDialogData = { property: '', template: '' }
			this.editingMappingRule = false
			this.showMappingDialog = true
		},

		/**
		 * Open dialog to edit an existing mapping rule
		 * @param {string} property - The property name
		 * @param {string} template - The template value
		 */
		editMappingRule(property, template) {
			this.mappingDialogData = { property, template }
			this.editingMappingRule = true
			this.showMappingDialog = true
		},

		/**
		 * Save mapping rule and update the mapping JSON
		 */
		saveMappingRule() {
			if (!this.mappingDialogData.property || !this.mappingDialogData.template) {
				return
			}

			const mappingRules = { ...this.mappingRules }
			mappingRules[this.mappingDialogData.property] = this.mappingDialogData.template
			this.mappingItem.mapping = JSON.stringify(mappingRules, null, 2)
			this.closeMappingDialog()
		},

		/**
		 * Delete a mapping rule
		 * @param {string} property - The property to delete
		 */
		deleteMappingRule(property) {
			const mappingRules = { ...this.mappingRules }
			delete mappingRules[property]
			this.mappingItem.mapping = JSON.stringify(mappingRules, null, 2)
		},

		/**
		 * Close mapping rule dialog
		 */
		closeMappingDialog() {
			this.showMappingDialog = false
			this.mappingDialogData = { property: '', template: '' }
			this.editingMappingRule = false
		},

		// Cast Rule Dialog Methods
		/**
		 * Open dialog to add a new cast rule
		 */
		addCastRule() {
			this.castDialogData = { property: '', castType: '' }
			this.editingCastRule = false
			this.showCastDialog = true
		},

		/**
		 * Open dialog to edit an existing cast rule
		 * @param {string} property - The property name
		 * @param {string} castType - The cast type
		 */
		editCastRule(property, castType) {
			this.castDialogData = { property, castType }
			this.editingCastRule = true
			this.showCastDialog = true
		},

		/**
		 * Save cast rule and update the cast JSON
		 */
		saveCastRule() {
			if (!this.castDialogData.property || !this.castDialogData.castType) {
				return
			}

			const castRules = { ...this.castRules }
			castRules[this.castDialogData.property] = this.castDialogData.castType
			this.mappingItem.cast = JSON.stringify(castRules, null, 2)
			this.closeCastDialog()
		},

		/**
		 * Delete a cast rule
		 * @param {string} property - The property to delete
		 */
		deleteCastRule(property) {
			const castRules = { ...this.castRules }
			delete castRules[property]
			this.mappingItem.cast = JSON.stringify(castRules, null, 2)
		},

		/**
		 * Close cast rule dialog
		 */
		closeCastDialog() {
			this.showCastDialog = false
			this.castDialogData = { property: '', castType: '' }
			this.editingCastRule = false
		},

		// Unset Rule Dialog Methods
		/**
		 * Open dialog to add a new unset property
		 */
		addUnsetRule() {
			this.unsetDialogData = { property: '' }
			this.editingUnsetRule = null
			this.showUnsetDialog = true
		},

		/**
		 * Open dialog to edit an existing unset property
		 * @param {number} index - The index of the property
		 * @param {string} property - The property name
		 */
		editUnsetRule(index, property) {
			this.unsetDialogData = { property }
			this.editingUnsetRule = index
			this.showUnsetDialog = true
		},

		/**
		 * Save unset rule and update the unset string
		 */
		saveUnsetRule() {
			if (!this.unsetDialogData.property) {
				return
			}

			const unsetRules = [...this.unsetRules]
			if (this.editingUnsetRule !== null) {
				unsetRules[this.editingUnsetRule] = this.unsetDialogData.property
			} else {
				unsetRules.push(this.unsetDialogData.property)
			}
			this.mappingItem.unset = unsetRules.join(', ')
			this.closeUnsetDialog()
		},

		/**
		 * Delete an unset property
		 * @param {number} index - The index to delete
		 */
		deleteUnsetRule(index) {
			const unsetRules = [...this.unsetRules]
			unsetRules.splice(index, 1)
			this.mappingItem.unset = unsetRules.join(', ')
		},

		/**
		 * Close unset rule dialog
		 */
		closeUnsetDialog() {
			this.showUnsetDialog = false
			this.unsetDialogData = { property: '' }
			this.editingUnsetRule = null
		},
	},
}
</script>

<!-- All CSS is provided by main.css -->
