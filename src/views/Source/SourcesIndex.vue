<template>
	<NcAppContent>
		<div class="viewContainer">
			<!-- Header -->
			<div class="viewHeader">
				<h1 class="viewHeaderTitleIndented">
					{{ t('openconnector', 'Sources') }}
				</h1>
				<p>{{ t('openconnector', 'Manage your data sources and their configurations') }}</p>
			</div>

			<!-- Actions Bar -->
			<div class="viewActionsBar">
				<div class="viewInfo">
					<span class="viewTotalCount">
						{{ t('openconnector', 'Showing {showing} of {total} sources', { showing: paginatedSources.length, total: filteredSources.length }) }}
					</span>
					<span v-if="selectedSources.length > 0" class="viewIndicator">
						({{ t('openconnector', '{count} selected', { count: selectedSources.length }) }})
					</span>
				</div>
				<div class="viewActions">
					<div class="viewModeSwitchContainer">
						<NcCheckboxRadioSwitch
							v-tooltip="'See sources as cards'"
							:checked="currentViewMode === 'cards'"
							:button-variant="true"
							value="cards"
							name="view_mode_radio"
							type="radio"
							button-variant-grouped="horizontal"
							@update:checked="checked => checked && setViewMode('cards')">
							Cards
						</NcCheckboxRadioSwitch>
						<NcCheckboxRadioSwitch
							v-tooltip="'See sources as a table'"
							:checked="currentViewMode === 'table'"
							:button-variant="true"
							value="table"
							name="view_mode_radio"
							type="radio"
							button-variant-grouped="horizontal"
							@update:checked="checked => checked && setViewMode('table')">
							Table
						</NcCheckboxRadioSwitch>
					</div>

					<NcActions
						:force-name="true"
						:inline="4"
						menu-name="Actions">
						<NcActionButton
							:primary="true"
							close-after-click
							@click="sourceStore.setSourceItem({}); navigationStore.setModal('editSource')">
							<template #icon>
								<Plus :size="20" />
							</template>
							Add Source
						</NcActionButton>
						<NcActionButton
							close-after-click
							@click="sourceStore.refreshSourceList()">
							<template #icon>
								<Refresh :size="20" />
							</template>
							Refresh
						</NcActionButton>
					</NcActions>
				</div>
			</div>

			<!-- Loading, Error, and Empty States -->
			<NcEmptyContent v-if="sourceStore.loading || sourceStore.error || !filteredSources.length"
				:name="emptyContentName"
				:description="emptyContentDescription">
				<template #icon>
					<NcLoadingIcon v-if="sourceStore.loading" :size="64" />
					<DatabaseArrowLeftOutline v-else :size="64" />
				</template>
				<template v-if="!sourceStore.loading && !sourceStore.error && !sourceStore.sourceList.length" #action>
					<NcButton type="primary" @click="sourceStore.setSourceItem({}); navigationStore.setModal('editSource')">
						{{ t('openconnector', 'Add source') }}
					</NcButton>
				</template>
			</NcEmptyContent>

			<!-- Content -->
			<div v-else>
				<template v-if="currentViewMode === 'cards'">
					<div class="cardGrid">
						<div v-for="source in paginatedSources" :key="source.id" class="card">
							<div class="cardHeader">
								<h2 v-tooltip.bottom="source.description">
									<DatabaseArrowLeftOutline :size="20" />
									{{ source.name }}
								</h2>
								<NcActions :primary="true" menu-name="Actions">
									<template #icon>
										<DotsHorizontal :size="20" />
									</template>
									<NcActionButton close-after-click @click="sourceStore.setSourceItem(source); navigationStore.setModal('viewSource')">
										<template #icon>
											<Eye :size="20" />
										</template>
										View
									</NcActionButton>
									<NcActionButton close-after-click @click="sourceStore.setSourceItem(source); navigationStore.setModal('editSource')">
										<template #icon>
											<Pencil :size="20" />
										</template>
										Edit
									</NcActionButton>
									<NcActionButton close-after-click @click="sourceStore.setSourceItem(source); navigationStore.setModal('testSource')">
										<template #icon>
											<Sync :size="20" />
										</template>
										Test
									</NcActionButton>
									<NcActionButton close-after-click @click="sourceStore.setSourceItem(source); $router.push('/sources/logs?source=' + source.id)">
										<template #icon>
											<TextBoxOutline :size="20" />
										</template>
										View Logs
									</NcActionButton>
									<NcActionButton close-after-click @click="addSourceConfiguration(source)">
										<template #icon>
											<Plus :size="20" />
										</template>
										Add Configuration
									</NcActionButton>
									<NcActionButton close-after-click @click="addSourceAuthentication(source)">
										<template #icon>
											<Plus :size="20" />
										</template>
										Add Authentication
									</NcActionButton>
									<NcActionButton close-after-click @click="sourceStore.setSourceItem(source); navigationStore.setDialog('deleteSource')">
										<template #icon>
											<TrashCanOutline :size="20" />
										</template>
										Delete
									</NcActionButton>
								</NcActions>
							</div>
							<!-- Source Details -->
							<div class="sourceDetails">
								<p v-if="source.description" class="sourceDescription">
									{{ source.description }}
								</p>
								<!-- Toggle between stats, configurations, and authentication -->
								<div v-if="!getSourceViewState(source).showConfigurations && !getSourceViewState(source).showAuthentication">
									<table class="statisticsTable sourceStats">
										<thead>
											<tr>
												<th>{{ t('openconnector', 'Property') }}</th>
												<th>{{ t('openconnector', 'Value') }}</th>
											</tr>
										</thead>
										<tbody>
											<tr>
												<td>{{ t('openconnector', 'Status') }}</td>
												<td>{{ source.status || 'Unknown' }}</td>
											</tr>
											<tr>
												<td>{{ t('openconnector', 'Enabled') }}</td>
												<td>
													{{ source.isEnabled ? 'Enabled' : 'Disabled' }}
												</td>
											</tr>
											<tr>
												<td>{{ t('openconnector', 'Type') }}</td>
												<td>{{ source.type || 'Unknown' }}</td>
											</tr>
											<tr v-if="source.location">
												<td>{{ t('openconnector', 'Location') }}</td>
												<td class="truncatedUrl">
													{{ source.location }}
												</td>
											</tr>
											<tr v-if="source.version">
												<td>{{ t('openconnector', 'Version') }}</td>
												<td>{{ source.version }}</td>
											</tr>
											<tr>
												<td>{{ t('openconnector', 'Configurations') }}</td>
												<td style="display: flex; justify-content: space-between; align-items: center;">
													<span>{{ getConfigurationCount(source) }}</span>
													<NcButton @click="showSourceConfigurations(source)">
														<template #icon>
															<FileCogOutline :size="16" />
														</template>
														Show
													</NcButton>
												</td>
											</tr>
											<tr>
												<td>{{ t('openconnector', 'Authentication') }}</td>
												<td style="display: flex; justify-content: space-between; align-items: center;">
													<span>{{ getAuthenticationCount(source) }}</span>
													<NcButton @click="showSourceAuthentication(source)">
														<template #icon>
															<KeyOutline :size="16" />
														</template>
														Show
													</NcButton>
												</td>
											</tr>
											<tr v-if="source.lastCall">
												<td>{{ t('openconnector', 'Last Call') }}</td>
												<td>{{ new Date(source.lastCall).toLocaleDateString() + ', ' + new Date(source.lastCall).toLocaleTimeString() }}</td>
											</tr>
											<tr v-if="source.lastSync">
												<td>{{ t('openconnector', 'Last Sync') }}</td>
												<td>{{ new Date(source.lastSync).toLocaleDateString() + ', ' + new Date(source.lastSync).toLocaleTimeString() }}</td>
											</tr>
											<tr>
												<td>{{ t('openconnector', 'Created') }}</td>
												<td>{{ source.dateCreated ? new Date(source.dateCreated).toLocaleDateString() : '-' }}</td>
											</tr>
											<tr>
												<td>{{ t('openconnector', 'Updated') }}</td>
												<td>{{ source.dateModified ? new Date(source.dateModified).toLocaleDateString() : '-' }}</td>
											</tr>
										</tbody>
									</table>
								</div>
								<!-- Configurations view -->
								<div v-else-if="getSourceViewState(source).showConfigurations" style="display: flex; flex-direction: column; height: 100%;">
									<div style="flex: 1;">
										<table class="statisticsTable sourceStats">
											<thead>
												<tr>
													<th>{{ t('openconnector', 'Key') }}</th>
													<th>{{ t('openconnector', 'Value') }}</th>
													<th>{{ t('openconnector', 'Actions') }}</th>
												</tr>
											</thead>
											<tbody>
												<tr v-for="(value, key) in source.configuration" :key="key">
													<td>{{ key }}</td>
													<td class="truncatedText">
														{{ value }}
													</td>
													<td>
														<NcActions :primary="false">
															<template #icon>
																<DotsHorizontal :size="16" />
															</template>
															<NcActionButton close-after-click @click="editSourceConfiguration(source, key)">
																<template #icon>
																	<Pencil :size="16" />
																</template>
																Edit
															</NcActionButton>
															<NcActionButton close-after-click @click="deleteSourceConfiguration(source, key)">
																<template #icon>
																	<TrashCanOutline :size="16" />
																</template>
																Delete
															</NcActionButton>
														</NcActions>
													</td>
												</tr>
												<tr v-if="!source.configuration || !Object.keys(source.configuration).length">
													<td colspan="3">
														{{ t('openconnector', 'No configurations found') }}
													</td>
												</tr>
											</tbody>
										</table>
									</div>
									<div style="display: flex; justify-content: flex-end; align-items: center; gap: 8px; margin-top: auto; padding-top: 10px;">
										<NcButton @click="showSourceStats(source)">
											<template #icon>
												<ArrowLeft :size="16" />
											</template>
											Back
										</NcButton>
										<NcButton :primary="true" @click="addSourceConfiguration(source)">
											<template #icon>
												<Plus :size="16" />
											</template>
											Add Configuration
										</NcButton>
									</div>
								</div>

								<!-- Authentication view -->
								<div v-else-if="getSourceViewState(source).showAuthentication" style="display: flex; flex-direction: column; height: 100%;">
									<div style="flex: 1;">
										<table class="statisticsTable sourceStats">
											<thead>
												<tr>
													<th>{{ t('openconnector', 'Property') }}</th>
													<th>{{ t('openconnector', 'Value') }}</th>
													<th>{{ t('openconnector', 'Actions') }}</th>
												</tr>
											</thead>
											<tbody>
												<tr v-if="source.auth">
													<td>{{ t('openconnector', 'Auth Type') }}</td>
													<td>{{ source.auth }}</td>
													<td>
														<NcActions :primary="false">
															<template #icon>
																<DotsHorizontal :size="16" />
															</template>
															<NcActionButton close-after-click @click="editSourceAuthentication(source, 'auth')">
																<template #icon>
																	<Pencil :size="16" />
																</template>
																Edit
															</NcActionButton>
															<NcActionButton close-after-click @click="deleteSourceAuthentication(source, 'auth')">
																<template #icon>
																	<TrashCanOutline :size="16" />
																</template>
																Delete
															</NcActionButton>
														</NcActions>
													</td>
												</tr>
												<tr v-if="source.username">
													<td>{{ t('openconnector', 'Username') }}</td>
													<td>{{ source.username }}</td>
													<td>
														<NcActions :primary="false">
															<template #icon>
																<DotsHorizontal :size="16" />
															</template>
															<NcActionButton close-after-click @click="editSourceAuthentication(source, 'username')">
																<template #icon>
																	<Pencil :size="16" />
																</template>
																Edit
															</NcActionButton>
															<NcActionButton close-after-click @click="deleteSourceAuthentication(source, 'username')">
																<template #icon>
																	<TrashCanOutline :size="16" />
																</template>
																Delete
															</NcActionButton>
														</NcActions>
													</td>
												</tr>
												<tr v-if="source.apikey">
													<td>{{ t('openconnector', 'API Key') }}</td>
													<td class="truncatedText">
														{{ source.apikey }}
													</td>
													<td>
														<NcActions :primary="false">
															<template #icon>
																<DotsHorizontal :size="16" />
															</template>
															<NcActionButton close-after-click @click="editSourceAuthentication(source, 'apikey')">
																<template #icon>
																	<Pencil :size="16" />
																</template>
																Edit
															</NcActionButton>
															<NcActionButton close-after-click @click="deleteSourceAuthentication(source, 'apikey')">
																<template #icon>
																	<TrashCanOutline :size="16" />
																</template>
																Delete
															</NcActionButton>
														</NcActions>
													</td>
												</tr>
												<tr v-if="source.jwt">
													<td>{{ t('openconnector', 'JWT') }}</td>
													<td class="truncatedText">
														{{ source.jwt }}
													</td>
													<td>
														<NcActions :primary="false">
															<template #icon>
																<DotsHorizontal :size="16" />
															</template>
															<NcActionButton close-after-click @click="editSourceAuthentication(source, 'jwt')">
																<template #icon>
																	<Pencil :size="16" />
																</template>
																Edit
															</NcActionButton>
															<NcActionButton close-after-click @click="deleteSourceAuthentication(source, 'jwt')">
																<template #icon>
																	<TrashCanOutline :size="16" />
																</template>
																Delete
															</NcActionButton>
														</NcActions>
													</td>
												</tr>
												<tr v-if="source.secret">
													<td>{{ t('openconnector', 'Secret') }}</td>
													<td class="truncatedText">
														{{ source.secret }}
													</td>
													<td>
														<NcActions :primary="false">
															<template #icon>
																<DotsHorizontal :size="16" />
															</template>
															<NcActionButton close-after-click @click="editSourceAuthentication(source, 'secret')">
																<template #icon>
																	<Pencil :size="16" />
																</template>
																Edit
															</NcActionButton>
															<NcActionButton close-after-click @click="deleteSourceAuthentication(source, 'secret')">
																<template #icon>
																	<TrashCanOutline :size="16" />
																</template>
																Delete
															</NcActionButton>
														</NcActions>
													</td>
												</tr>
												<tr v-if="source.authorizationHeader">
													<td>{{ t('openconnector', 'Authorization Header') }}</td>
													<td class="truncatedText">
														{{ source.authorizationHeader }}
													</td>
													<td>
														<NcActions :primary="false">
															<template #icon>
																<DotsHorizontal :size="16" />
															</template>
															<NcActionButton close-after-click @click="editSourceAuthentication(source, 'authorizationHeader')">
																<template #icon>
																	<Pencil :size="16" />
																</template>
																Edit
															</NcActionButton>
															<NcActionButton close-after-click @click="deleteSourceAuthentication(source, 'authorizationHeader')">
																<template #icon>
																	<TrashCanOutline :size="16" />
																</template>
																Delete
															</NcActionButton>
														</NcActions>
													</td>
												</tr>
												<tr v-for="(config, index) in source.authenticationConfig" :key="`auth-${index}`">
													<td>{{ t('openconnector', 'Auth Config {index}', { index: index + 1 }) }}</td>
													<td class="truncatedText">
														{{ typeof config === 'object' ? JSON.stringify(config) : config }}
													</td>
													<td>
														<NcActions :primary="false">
															<template #icon>
																<DotsHorizontal :size="16" />
															</template>
															<NcActionButton close-after-click @click="editSourceAuthentication(source, `authenticationConfig.${index}`)">
																<template #icon>
																	<Pencil :size="16" />
																</template>
																Edit
															</NcActionButton>
															<NcActionButton close-after-click @click="deleteSourceAuthentication(source, `authenticationConfig.${index}`)">
																<template #icon>
																	<TrashCanOutline :size="16" />
																</template>
																Delete
															</NcActionButton>
														</NcActions>
													</td>
												</tr>
												<tr v-if="!hasAuthenticationData(source)">
													<td colspan="3">
														{{ t('openconnector', 'No authentication configured') }}
													</td>
												</tr>
											</tbody>
										</table>
									</div>
									<div style="display: flex; justify-content: flex-end; align-items: center; gap: 8px; margin-top: auto; padding-top: 10px;">
										<NcButton @click="showSourceStats(source)">
											<template #icon>
												<ArrowLeft :size="16" />
											</template>
											Back
										</NcButton>
										<NcButton :primary="true" @click="addSourceAuthentication(source)">
											<template #icon>
												<Plus :size="16" />
											</template>
											Add Authentication
										</NcButton>
									</div>
								</div>
							</div>
						</div>
					</div>
				</template>
				<template v-else>
					<div class="viewTableContainer">
						<table class="viewTable">
							<thead>
								<tr>
									<th class="tableColumnCheckbox">
										<NcCheckboxRadioSwitch
											:checked="allSelected"
											:indeterminate="someSelected"
											@update:checked="toggleSelectAll" />
									</th>
									<th>{{ t('openconnector', 'Name') }}</th>
									<th>{{ t('openconnector', 'Type') }}</th>
									<th>{{ t('openconnector', 'Location') }}</th>
									<th>{{ t('openconnector', 'Version') }}</th>
									<th>{{ t('openconnector', 'Configurations') }}</th>
									<th>{{ t('openconnector', 'Created') }}</th>
									<th>{{ t('openconnector', 'Updated') }}</th>
									<th class="tableColumnActions">
										{{ t('openconnector', 'Actions') }}
									</th>
								</tr>
							</thead>
							<tbody>
								<tr v-for="source in paginatedSources"
									:key="source.id"
									class="viewTableRow"
									:class="{ viewTableRowSelected: selectedSources.includes(source.id) }">
									<td class="tableColumnCheckbox">
										<NcCheckboxRadioSwitch
											:checked="selectedSources.includes(source.id)"
											@update:checked="(checked) => toggleSourceSelection(source.id, checked)" />
									</td>
									<td class="tableColumnTitle">
										<div class="titleContent">
											<strong>{{ source.name }}</strong>
											<span v-if="source.description" class="textDescription textEllipsis">{{ source.description }}</span>
										</div>
									</td>
									<td>{{ source.type || 'Unknown' }}</td>
									<td class="tableColumnConstrained">
										<span v-if="source.location" class="truncatedUrl">{{ source.location }}</span>
										<span v-else>-</span>
									</td>
									<td>{{ source.version || '-' }}</td>
									<td>{{ getConfigurationCount(source) }}</td>
									<td>{{ source.created ? new Date(source.created).toLocaleDateString() + ', ' + new Date(source.created).toLocaleTimeString() : '-' }}</td>
									<td>{{ source.updated ? new Date(source.updated).toLocaleDateString() + ', ' + new Date(source.updated).toLocaleTimeString() : '-' }}</td>
									<td class="tableColumnActions">
										<NcActions :primary="false">
											<template #icon>
												<DotsHorizontal :size="20" />
											</template>
											<NcActionButton close-after-click @click="sourceStore.setSourceItem(source); navigationStore.setModal('viewSource')">
												<template #icon>
													<Eye :size="20" />
												</template>
												View
											</NcActionButton>
											<NcActionButton close-after-click @click="sourceStore.setSourceItem(source); navigationStore.setModal('editSource')">
												<template #icon>
													<Pencil :size="20" />
												</template>
												Edit
											</NcActionButton>
											<NcActionButton close-after-click @click="sourceStore.setSourceItem(source); navigationStore.setModal('testSource')">
												<template #icon>
													<Sync :size="20" />
												</template>
												Test
											</NcActionButton>
											<NcActionButton close-after-click @click="sourceStore.setSourceItem(source); $router.push('/sources/logs?source=' + source.id)">
												<template #icon>
													<TextBoxOutline :size="20" />
												</template>
												View Logs
											</NcActionButton>
											<NcActionButton close-after-click @click="addSourceConfiguration(source)">
												<template #icon>
													<Plus :size="20" />
												</template>
												Add Configuration
											</NcActionButton>
											<NcActionButton close-after-click @click="addSourceAuthentication(source)">
												<template #icon>
													<Plus :size="20" />
												</template>
												Add Authentication
											</NcActionButton>
											<NcActionButton close-after-click @click="sourceStore.setSourceItem(source); navigationStore.setDialog('deleteSource')">
												<template #icon>
													<TrashCanOutline :size="20" />
												</template>
												Delete
											</NcActionButton>
										</NcActions>
									</td>
								</tr>
							</tbody>
						</table>
					</div>
				</template>
			</div>

			<!-- Pagination -->
			<PaginationComponent
				v-if="filteredSources.length > 0"
				:current-page="pagination.page || 1"
				:total-pages="Math.ceil(filteredSources.length / (pagination.limit || 20))"
				:total-items="filteredSources.length"
				:current-page-size="pagination.limit || 20"
				:min-items-to-show="0"
				@page-changed="onPageChanged"
				@page-size-changed="onPageSizeChanged" />
		</div>
	</NcAppContent>
</template>

<script>
import { NcAppContent, NcEmptyContent, NcLoadingIcon, NcActions, NcActionButton, NcCheckboxRadioSwitch, NcButton } from '@nextcloud/vue'
import { translate as t } from '@nextcloud/l10n'
import DatabaseArrowLeftOutline from 'vue-material-design-icons/DatabaseArrowLeftOutline.vue'
import DotsHorizontal from 'vue-material-design-icons/DotsHorizontal.vue'
import Pencil from 'vue-material-design-icons/Pencil.vue'
import TrashCanOutline from 'vue-material-design-icons/TrashCanOutline.vue'
import Refresh from 'vue-material-design-icons/Refresh.vue'
import Plus from 'vue-material-design-icons/Plus.vue'
import Eye from 'vue-material-design-icons/Eye.vue'
import Sync from 'vue-material-design-icons/Sync.vue'
import TextBoxOutline from 'vue-material-design-icons/TextBoxOutline.vue'
import ArrowLeft from 'vue-material-design-icons/ArrowLeft.vue'
import FileCogOutline from 'vue-material-design-icons/FileCogOutline.vue'
import KeyOutline from 'vue-material-design-icons/KeyOutline.vue'

import PaginationComponent from '../../components/PaginationComponent.vue'
import { sourceStore, navigationStore } from '../../store/store.js'

export default {
	name: 'SourcesIndex',
	components: {
		NcAppContent,
		NcEmptyContent,
		NcLoadingIcon,
		NcActions,
		NcActionButton,
		NcCheckboxRadioSwitch,
		NcButton,
		DatabaseArrowLeftOutline,
		DotsHorizontal,
		Pencil,
		TrashCanOutline,
		Refresh,
		Plus,
		Eye,
		Sync,
		TextBoxOutline,
		ArrowLeft,
		FileCogOutline,
		KeyOutline,
		PaginationComponent,
	},
	data() {
		return {
			sourceStore,
			navigationStore,
			selectedSources: [],
			pagination: {
				page: 1,
				limit: 20,
			},
			sourceViewStates: {}, // Track view states for each source
		}
	},
	computed: {
		currentViewMode() {
			return this.sourceStore.viewMode
		},
		filteredSources() {
			if (!this.sourceStore.sourceList) return []
			return this.sourceStore.sourceList
		},
		paginatedSources() {
			const start = ((this.pagination.page || 1) - 1) * (this.pagination.limit || 20)
			const end = start + (this.pagination.limit || 20)
			return this.filteredSources.slice(start, end)
		},
		allSelected() {
			return this.filteredSources.length > 0 && this.filteredSources.every(source => this.selectedSources.includes(source.id))
		},
		someSelected() {
			return this.selectedSources.length > 0 && !this.allSelected
		},
		emptyContentName() {
			if (this.sourceStore.loading) {
				return t('openconnector', 'Loading sources...')
			} else if (this.sourceStore.error) {
				return this.sourceStore.error
			} else if (!this.sourceStore.sourceList?.length) {
				return t('openconnector', 'No sources found')
			}
			return ''
		},
		emptyContentDescription() {
			if (this.sourceStore.loading) {
				return t('openconnector', 'Please wait while we fetch your sources.')
			} else if (this.sourceStore.error) {
				return t('openconnector', 'Please try again later.')
			} else if (!this.sourceStore.sourceList?.length) {
				return t('openconnector', 'No sources are available.')
			}
			return ''
		},
	},
	mounted() {
		this.sourceStore.refreshSourceList()
	},
	methods: {
		setViewMode(mode) {
			if (mode === 'cards' || mode === 'table') {
				this.sourceStore.setViewMode(mode)
			}
		},
		toggleSelectAll(checked) {
			if (checked) {
				this.selectedSources = this.filteredSources.map(source => source.id)
			} else {
				this.selectedSources = []
			}
		},
		toggleSourceSelection(sourceId, checked) {
			if (checked) {
				this.selectedSources.push(sourceId)
			} else {
				this.selectedSources = this.selectedSources.filter(id => id !== sourceId)
			}
		},
		onPageChanged(page) {
			this.pagination.page = page
		},
		onPageSizeChanged(pageSize) {
			this.pagination.page = 1
			this.pagination.limit = pageSize
		},
		getConfigurationCount(source) {
			const config = source.configuration || {}
			const { authentication, ...configWithoutAuth } = config
			return Object.keys(configWithoutAuth).length
		},
		getAuthenticationCount(source) {
			let count = 0
			if (source.auth) count++
			if (source.username) count++
			if (source.apikey) count++
			if (source.jwt) count++
			if (source.secret) count++
			if (source.authorizationHeader) count++
			if (source.authenticationConfig && source.authenticationConfig.length > 0) {
				count += source.authenticationConfig.length
			}
			return count
		},
		addSourceConfiguration(source) {
			this.sourceStore.setSourceItem(source)
			this.sourceStore.setSourceConfigurationKey(null)
			this.navigationStore.setModal('editSourceConfiguration')
		},
		addSourceAuthentication(source) {
			this.sourceStore.setSourceItem(source)
			this.sourceStore.setSourceConfigurationKey(null)
			this.navigationStore.setModal('editSourceConfigurationAuthentication')
		},
		/**
		 * Check if source has any authentication data to display
		 * @param {object} source - The source to check for authentication data
		 * @return {boolean} True if source has authentication data
		 */
		hasAuthenticationData(source) {
			return !!(source.auth || source.username || source.apikey || source.jwt
				|| source.secret || source.authorizationHeader
				|| (source.authenticationConfig && source.authenticationConfig.length > 0))
		},

		/**
		 * Get view state for a source
		 * @param {object} source - The source object
		 * @return {object} View state object
		 */
		getSourceViewState(source) {
			if (!this.sourceViewStates[source.id]) {
				this.$set(this.sourceViewStates, source.id, {
					showConfigurations: false,
					showAuthentication: false,
				})
			}
			return this.sourceViewStates[source.id]
		},

		/**
		 * Show configurations for a source
		 * @param {object} source - The source object
		 */
		showSourceConfigurations(source) {
			const viewState = this.getSourceViewState(source)
			viewState.showConfigurations = true
			viewState.showAuthentication = false
		},

		/**
		 * Show authentication for a source
		 * @param {object} source - The source object
		 */
		showSourceAuthentication(source) {
			const viewState = this.getSourceViewState(source)
			viewState.showAuthentication = true
			viewState.showConfigurations = false
		},

		/**
		 * Show stats for a source (hide configurations and authentication)
		 * @param {object} source - The source object
		 */
		showSourceStats(source) {
			const viewState = this.getSourceViewState(source)
			viewState.showConfigurations = false
			viewState.showAuthentication = false
		},

		/**
		 * Edit source authentication
		 * @param {object} source - The source object
		 * @param {string} field - The authentication field to edit
		 */
		editSourceAuthentication(source, field) {
			this.sourceStore.setSourceItem(source)
			this.sourceStore.setSourceConfigurationKey(field)
			this.navigationStore.setModal('editSourceConfigurationAuthentication')
		},

		/**
		 * Delete source authentication
		 * @param {object} source - The source object
		 * @param {string} field - The authentication field to delete
		 */
		deleteSourceAuthentication(source, field) {
			this.sourceStore.setSourceItem(source)
			this.sourceStore.setSourceConfigurationKey(field)
			this.navigationStore.setDialog('deleteSourceConfigurationAuthentication')
		},

		/**
		 * Edit source configuration
		 * @param {object} source - The source object
		 * @param {string} key - The configuration key to edit
		 */
		editSourceConfiguration(source, key) {
			this.sourceStore.setSourceItem(source)
			this.sourceStore.setSourceConfigurationKey(key)
			this.navigationStore.setModal('editSourceConfiguration')
		},

		/**
		 * Delete source configuration
		 * @param {object} source - The source object
		 * @param {string} key - The configuration key to delete
		 */
		deleteSourceConfiguration(source, key) {
			this.sourceStore.setSourceItem(source)
			this.sourceStore.setSourceConfigurationKey(key)
			this.navigationStore.setModal('deleteSourceConfiguration')
		},
	},
}
</script>

<style scoped>
/* All CSS is provided by main.css */
</style>
