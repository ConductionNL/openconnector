<template>
	<div>
		<NcSettingsSection
			name="Open Connector"
			description="A central place for managing your Open Connector"
			doc-url="https://docs.openconnector.nl" />

		<NcSettingsSection
			name="Version Information"
			description="Current application version information">
			<div v-if="!loadingVersionInfo" class="version-info">
				<div class="version-details">
					<div class="version-item">
						<strong>Application:</strong> {{ versionInfo.appName }} v{{ versionInfo.appVersion }}
					</div>
					<div class="version-item">
						<strong>License:</strong> EUPL-1.2
					</div>
					<div class="version-item">
						<strong>Author:</strong> Conduction B.V.
					</div>
					<div class="version-item">
						<strong>Website:</strong>
						<a href="https://github.com/ConductionNL/OpenConnector" target="_blank" rel="noopener noreferrer">
							https://github.com/ConductionNL/OpenConnector
						</a>
					</div>
				</div>
			</div>

			<!-- Loading State -->
			<NcLoadingIcon v-else
				class="loading-icon"
				:size="64"
				appearance="dark" />
		</NcSettingsSection>

		<NcSettingsSection name="System Statistics"
			description="Overview of your Open Connector data and potential issues">
			<div v-if="!loadingStats" class="stats-section">
				<!-- Save and Rebase Buttons -->
				<div class="section-header-inline">
					<span />
					<div class="button-group">
						<NcButton
							type="secondary"
							:disabled="loading || saving || rebasing || loadingStats"
							@click="loadStats">
							<template #icon>
								<NcLoadingIcon v-if="loadingStats" :size="20" />
								<Refresh v-else :size="20" />
							</template>
							Refresh
						</NcButton>
					</div>
				</div>

				<div class="stats-content">
					<div class="stats-grid">
						<!-- Warning Stats -->
						<div class="stats-card warning-stats">
							<h4>⚠️ Items Requiring Attention</h4>
							<div class="stats-table-container">
								<table class="stats-table">
									<thead>
										<tr>
											<th class="stats-table-header">Issue</th>
											<th class="stats-table-header">Count</th>
											<th class="stats-table-header">Size</th>
										</tr>
									</thead>
									<tbody>
										<tr class="stats-table-row">
											<td class="stats-table-label">Call logs without expiry</td>
											<td class="stats-table-value" :class="{ 'danger': (stats.warnings.callLogsWithoutExpiry || 0) > 0 }">{{ stats.warnings.callLogsWithoutExpiry || 0 }}</td>
											<td class="stats-table-value" :class="{ 'danger': (stats.warnings.callLogsWithoutExpirySize || 0) > 0 }">{{ formatBytes(stats.warnings.callLogsWithoutExpirySize || 0) }}</td>
										</tr>
										<tr class="stats-table-row">
											<td class="stats-table-label">Job logs without expiry</td>
											<td class="stats-table-value" :class="{ 'danger': (stats.warnings.jobLogsWithoutExpiry || 0) > 0 }">{{ stats.warnings.jobLogsWithoutExpiry || 0 }}</td>
											<td class="stats-table-value" :class="{ 'danger': (stats.warnings.jobLogsWithoutExpirySize || 0) > 0 }">{{ formatBytes(stats.warnings.jobLogsWithoutExpirySize || 0) }}</td>
										</tr>
										<tr class="stats-table-row">
											<td class="stats-table-label">Sync logs without expiry</td>
											<td class="stats-table-value" :class="{ 'danger': (stats.warnings.syncLogsWithoutExpiry || 0) > 0 }">{{ stats.warnings.syncLogsWithoutExpiry || 0 }}</td>
											<td class="stats-table-value" :class="{ 'danger': (stats.warnings.syncLogsWithoutExpirySize || 0) > 0 }">{{ formatBytes(stats.warnings.syncLogsWithoutExpirySize || 0) }}</td>
										</tr>
										<tr class="stats-table-row">
											<td class="stats-table-label">Contract logs without expiry</td>
											<td class="stats-table-value" :class="{ 'danger': (stats.warnings.contractLogsWithoutExpiry || 0) > 0 }">{{ stats.warnings.contractLogsWithoutExpiry || 0 }}</td>
											<td class="stats-table-value" :class="{ 'danger': (stats.warnings.contractLogsWithoutExpirySize || 0) > 0 }">{{ formatBytes(stats.warnings.contractLogsWithoutExpirySize || 0) }}</td>
										</tr>
										<tr class="stats-table-row">
											<td class="stats-table-label">Expired call logs</td>
											<td class="stats-table-value" :class="{ 'danger': (stats.warnings.expiredCallLogs || 0) > 0 }">{{ stats.warnings.expiredCallLogs || 0 }}</td>
											<td class="stats-table-value" :class="{ 'danger': (stats.warnings.expiredCallLogsSize || 0) > 0 }">{{ formatBytes(stats.warnings.expiredCallLogsSize || 0) }}</td>
										</tr>
										<tr class="stats-table-row">
											<td class="stats-table-label">Expired job logs</td>
											<td class="stats-table-value" :class="{ 'danger': (stats.warnings.expiredJobLogs || 0) > 0 }">{{ stats.warnings.expiredJobLogs || 0 }}</td>
											<td class="stats-table-value" :class="{ 'danger': (stats.warnings.expiredJobLogsSize || 0) > 0 }">{{ formatBytes(stats.warnings.expiredJobLogsSize || 0) }}</td>
										</tr>
										<tr class="stats-table-row">
											<td class="stats-table-label">Expired sync logs</td>
											<td class="stats-table-value" :class="{ 'danger': (stats.warnings.expiredSyncLogs || 0) > 0 }">{{ stats.warnings.expiredSyncLogs || 0 }}</td>
											<td class="stats-table-value" :class="{ 'danger': (stats.warnings.expiredSyncLogsSize || 0) > 0 }">{{ formatBytes(stats.warnings.expiredSyncLogsSize || 0) }}</td>
										</tr>
										<tr class="stats-table-row">
											<td class="stats-table-label">Expired contract logs</td>
											<td class="stats-table-value" :class="{ 'danger': (stats.warnings.expiredContractLogs || 0) > 0 }">{{ stats.warnings.expiredContractLogs || 0 }}</td>
											<td class="stats-table-value" :class="{ 'danger': (stats.warnings.expiredContractLogsSize || 0) > 0 }">{{ formatBytes(stats.warnings.expiredContractLogsSize || 0) }}</td>
										</tr>
									</tbody>
								</table>
							</div>
						</div>

						<!-- Total Stats -->
						<div class="stats-card total-stats">
							<h4>📊 System Totals</h4>
							<div class="stats-table-container">
								<table class="stats-table">
									<thead>
										<tr>
											<th class="stats-table-header">Category</th>
											<th class="stats-table-header">Total</th>
											<th class="stats-table-header">Size</th>
										</tr>
									</thead>
									<tbody>
										<!-- Log Totals -->
										<tr class="stats-table-row">
											<td class="stats-table-label">Call Logs</td>
											<td class="stats-table-value total">{{ (stats.totals.totalCallLogs || 0).toLocaleString() }}</td>
											<td class="stats-table-value total">{{ formatBytes(stats.totals.totalCallLogsSize || 0) }}</td>
										</tr>
										<tr class="stats-table-row">
											<td class="stats-table-label">Job Logs</td>
											<td class="stats-table-value total">{{ (stats.totals.totalJobLogs || 0).toLocaleString() }}</td>
											<td class="stats-table-value total">{{ formatBytes(stats.totals.totalJobLogsSize || 0) }}</td>
										</tr>
										<tr class="stats-table-row">
											<td class="stats-table-label">Synchronization Logs</td>
											<td class="stats-table-value total">{{ (stats.totals.totalSyncLogs || 0).toLocaleString() }}</td>
											<td class="stats-table-value total">{{ formatBytes(stats.totals.totalSyncLogsSize || 0) }}</td>
										</tr>
										<tr class="stats-table-row">
											<td class="stats-table-label">Contract Logs</td>
											<td class="stats-table-value total">{{ (stats.totals.totalContractLogs || 0).toLocaleString() }}</td>
											<td class="stats-table-value total">{{ formatBytes(stats.totals.totalContractLogsSize || 0) }}</td>
										</tr>
										<!-- Entity Totals -->
										<tr class="stats-table-row">
											<td class="stats-table-label">Sources</td>
											<td class="stats-table-value total">{{ (stats.totals.totalSources || 0).toLocaleString() }}</td>
											<td class="stats-table-value total">{{ formatBytes(stats.totals.totalSourcesSize || 0) }}</td>
										</tr>
										<tr class="stats-table-row">
											<td class="stats-table-label">Synchronizations</td>
											<td class="stats-table-value total">{{ (stats.totals.totalSynchronizations || 0).toLocaleString() }}</td>
											<td class="stats-table-value total">{{ formatBytes(stats.totals.totalSynchronizationsSize || 0) }}</td>
										</tr>
										<tr class="stats-table-row">
											<td class="stats-table-label">Mappings</td>
											<td class="stats-table-value total">{{ (stats.totals.totalMappings || 0).toLocaleString() }}</td>
											<td class="stats-table-value total">{{ formatBytes(stats.totals.totalMappingsSize || 0) }}</td>
										</tr>
										<tr class="stats-table-row">
											<td class="stats-table-label">Jobs</td>
											<td class="stats-table-value total">{{ (stats.totals.totalJobs || 0).toLocaleString() }}</td>
											<td class="stats-table-value total">{{ formatBytes(stats.totals.totalJobsSize || 0) }}</td>
										</tr>
										<tr class="stats-table-row">
											<td class="stats-table-label">Rules</td>
											<td class="stats-table-value total">{{ (stats.totals.totalRules || 0).toLocaleString() }}</td>
											<td class="stats-table-value total">{{ formatBytes(stats.totals.totalRulesSize || 0) }}</td>
										</tr>
										<tr class="stats-table-row">
											<td class="stats-table-label">Contracts</td>
											<td class="stats-table-value total">{{ (stats.totals.totalContracts || 0).toLocaleString() }}</td>
											<td class="stats-table-value total">{{ formatBytes(stats.totals.totalContractsSize || 0) }}</td>
										</tr>
									</tbody>
								</table>
							</div>
						</div>
					</div>

					<div class="stats-footer">
						<p class="stats-updated">
							Last updated: {{ formatDate(stats.lastUpdated) }}
						</p>
					</div>
				</div>
			</div>

			<!-- Loading State -->
			<NcLoadingIcon v-else
				class="loading-icon"
				:size="64"
				appearance="dark" />
		</NcSettingsSection>



		<NcSettingsSection name="Retention">
			<template #description>
				Configure data and log retention policies
			</template>

			<div v-if="!loading" class="retention-options">
				<!-- Save Button -->
				<div class="section-header-inline">
					<span />
					<div class="button-group">
						<NcButton
							type="error"
							:disabled="loading || saving || rebasing"
							@click="showRebaseDialog">
							<template #icon>
								<NcLoadingIcon v-if="rebasing" :size="20" />
								<Refresh v-else :size="20" />
							</template>
							Rebase
						</NcButton>
						<NcButton
							type="primary"
							:disabled="loading || saving || rebasing"
							@click="saveSettings">
							<template #icon>
								<NcLoadingIcon v-if="saving" :size="20" />
								<Save v-else :size="20" />
							</template>
							Save
						</NcButton>
					</div>
				</div>

				<!-- Section Description -->
				<div class="section-description-full">
					<p class="main-description">
						Configure retention policies for OpenConnector logs. Log retention manages how long call logs, job logs, synchronization logs, 
						and contract logs are kept for compliance and debugging purposes.
						<strong>Note:</strong> Setting retention to 0 means data is kept forever (not advisable for production).
					</p>
					<p class="toggle-status" :class="retentionStatusClass">
						<span :class="retentionStatusTextClass">{{ retentionStatusMessage }}</span>
					</p>
					<p class="impact-description warning-box">
						<strong>⚠️ Important:</strong> Changes to retention policies only apply to logs that are created after the retention policy was changed.
						Existing logs will retain their previous retention schedules until they expire naturally.
					</p>
				</div>

				<!-- Consolidated Retention Settings -->
				<div class="option-section">
					<h4>Log Retention Policies</h4>
					<p class="option-description">
						Configure retention periods for different types of OpenConnector logs (in milliseconds). These settings control how long logs are kept before automatic cleanup.
					</p>

					<div class="retention-table">
						<div class="retention-row">
							<div class="retention-label">
								<strong>Call Log Retention</strong>
								<p class="retention-description">
									Retention period for API call logs including requests, responses, and errors
								</p>
							</div>
							<div class="retention-input">
								<div class="retention-input-wrapper">
									<input
										v-model.number="retentionOptions.callLogRetention"
										type="number"
										:disabled="loading || saving"
										placeholder="2592000000"
										class="retention-input-field">
									<span class="retention-unit">ms</span>
								</div>
							</div>
							<div class="retention-display">
								{{ formatRetentionPeriod(retentionOptions.callLogRetention) }}
							</div>
						</div>

						<div class="retention-row">
							<div class="retention-label">
								<strong>Job Log Retention</strong>
								<p class="retention-description">
									Retention period for background job execution logs and results
								</p>
							</div>
							<div class="retention-input">
								<div class="retention-input-wrapper">
									<input
										v-model.number="retentionOptions.jobLogRetention"
										type="number"
										:disabled="loading || saving"
										placeholder="2592000000"
										class="retention-input-field">
									<span class="retention-unit">ms</span>
								</div>
							</div>
							<div class="retention-display">
								{{ formatRetentionPeriod(retentionOptions.jobLogRetention) }}
							</div>
						</div>

						<div class="retention-row">
							<div class="retention-label">
								<strong>Synchronization Log Retention</strong>
								<p class="retention-description">
									Retention period for data synchronization process logs
								</p>
							</div>
							<div class="retention-input">
								<div class="retention-input-wrapper">
									<input
										v-model.number="retentionOptions.syncLogRetention"
										type="number"
										:disabled="loading || saving"
										placeholder="2592000000"
										class="retention-input-field">
									<span class="retention-unit">ms</span>
								</div>
							</div>
							<div class="retention-display">
								{{ formatRetentionPeriod(retentionOptions.syncLogRetention) }}
							</div>
						</div>

						<div class="retention-row">
							<div class="retention-label">
								<strong>Contract Log Retention</strong>
								<p class="retention-description">
									Retention period for synchronization contract logs and mapping information
								</p>
							</div>
							<div class="retention-input">
								<div class="retention-input-wrapper">
									<input
										v-model.number="retentionOptions.contractLogRetention"
										type="number"
										:disabled="loading || saving"
										placeholder="2592000000"
										class="retention-input-field">
									<span class="retention-unit">ms</span>
								</div>
							</div>
							<div class="retention-display">
								{{ formatRetentionPeriod(retentionOptions.contractLogRetention) }}
							</div>
						</div>
					</div>
				</div>
			</div>

			<!-- Loading State -->
			<NcLoadingIcon v-else
				class="loading-icon"
				:size="64"
				appearance="dark" />
		</NcSettingsSection>

		<!-- Rebase Confirmation Dialog -->
		<NcDialog
			v-if="showRebaseConfirmation"
			name="Confirm Rebase"
			:can-close="!rebasing"
			@closing="hideRebaseDialog">
			<div class="rebase-dialog">
				<div class="rebase-warning">
					<h3>⚠️ Rebase All Logs</h3>
					<p class="warning-text">
						This action will recalculate expiration times for all logs based on your current retention settings.
						This ensures that all existing logs follow the new retention policies you have configured.
					</p>
					<p class="impact-text">
						<strong>This operation:</strong><br>
						• Will update expiration timestamps for all existing logs<br>
						• Cannot be undone once started<br>
						• May take some time to complete depending on data volume<br>
						• Will apply current retention policies to all log types
					</p>
				</div>
				<div class="dialog-actions">
					<NcButton
						:disabled="rebasing"
						@click="hideRebaseDialog">
						Cancel
					</NcButton>
					<NcButton
						type="error"
						:disabled="rebasing"
						@click="performRebase">
						<template #icon>
							<NcLoadingIcon v-if="rebasing" :size="20" />
							<Refresh v-else :size="20" />
						</template>
						{{ rebasing ? 'Rebasing...' : 'Confirm Rebase' }}
					</NcButton>
				</div>
			</div>
		</NcDialog>
	</div>
</template>

<script>
import { defineComponent } from 'vue'
import {
	NcSettingsSection,
	NcSelect,
	NcButton,
	NcLoadingIcon,
	NcCheckboxRadioSwitch,
	NcDialog,
} from '@nextcloud/vue'
import Save from 'vue-material-design-icons/ContentSave.vue'
import Refresh from 'vue-material-design-icons/Refresh.vue'

/**
 * @class Settings
 * @module Components
 * @package
 * @author Claude AI
 * @copyright 2024 Conduction
 * @license EUPL-1.2
 * @version 1.0.0
 * @see https://github.com/ConductionNL/OpenConnector
 *
 * Settings component for the Open Connector that allows users to configure
 * version information, RBAC, multitenancy, and log retention options.
 */
export default defineComponent({
	name: 'Settings',
	components: {
		NcSettingsSection,
		NcSelect,
		NcButton,
		NcLoadingIcon,
		NcCheckboxRadioSwitch,
		NcDialog,
		Save,
		Refresh,
	},

	/**
	 * Component data
	 *
	 * @return {object} Component data
	 */
	data() {
		return {
			loading: true,
			saving: false,
			rebasing: false,
			showRebaseConfirmation: false,
			loadingVersionInfo: true,
			loadingStats: true,
			stats: {
				warnings: {
					callLogsWithoutExpiry: 0,
					callLogsWithoutExpirySize: 0,
					jobLogsWithoutExpiry: 0,
					jobLogsWithoutExpirySize: 0,
					syncLogsWithoutExpiry: 0,
					syncLogsWithoutExpirySize: 0,
					contractLogsWithoutExpiry: 0,
					contractLogsWithoutExpirySize: 0,
					expiredCallLogs: 0,
					expiredCallLogsSize: 0,
					expiredJobLogs: 0,
					expiredJobLogsSize: 0,
					expiredSyncLogs: 0,
					expiredSyncLogsSize: 0,
					expiredContractLogs: 0,
					expiredContractLogsSize: 0,
				},
				totals: {
					// Log totals
					totalCallLogs: 0,
					totalCallLogsSize: 0,
					totalJobLogs: 0,
					totalJobLogsSize: 0,
					totalSyncLogs: 0,
					totalSyncLogsSize: 0,
					totalContractLogs: 0,
					totalContractLogsSize: 0,
					// Entity totals
					totalSources: 0,
					totalSourcesSize: 0,
					totalSynchronizations: 0,
					totalSynchronizationsSize: 0,
					totalMappings: 0,
					totalMappingsSize: 0,
					totalJobs: 0,
					totalJobsSize: 0,
					totalRules: 0,
					totalRulesSize: 0,
					totalContracts: 0,
					totalContractsSize: 0,
				},
				lastUpdated: new Date(),
			},
			versionInfo: {
				appName: 'Open Connector',
				appVersion: '1.0.0',
			},

			retentionOptions: {
				callLogRetention: 2592000000, // 1 month default
				jobLogRetention: 2592000000, // 1 month default
				syncLogRetention: 2592000000, // 1 month default
				contractLogRetention: 2592000000, // 1 month default
			},

		}
	},

	computed: {

		/**
		 * Retention status message based on zero values
		 *
		 * @return {string} Status message
		 */
		retentionStatusMessage() {
			const zeroRetentions = []

			if (this.retentionOptions.callLogRetention === 0) {
				zeroRetentions.push('Call Logs')
			}
			if (this.retentionOptions.jobLogRetention === 0) {
				zeroRetentions.push('Job Logs')
			}
			if (this.retentionOptions.syncLogRetention === 0) {
				zeroRetentions.push('Sync Logs')
			}
			if (this.retentionOptions.contractLogRetention === 0) {
				zeroRetentions.push('Contract Logs')
			}

			if (zeroRetentions.length === 0) {
				return 'All retention policies configured'
			} else if (zeroRetentions.length === 4) {
				return '⚠️ All retention policies set to forever (not advisable)'
			} else {
				return `⚠️ ${zeroRetentions.join(', ')} set to forever (not advisable)`
			}
		},

		/**
		 * CSS class for retention status
		 *
		 * @return {string} CSS class
		 */
		retentionStatusClass() {
			const zeroCount = [
				this.retentionOptions.callLogRetention,
				this.retentionOptions.jobLogRetention,
				this.retentionOptions.syncLogRetention,
				this.retentionOptions.contractLogRetention,
			].filter(val => val === 0).length

			return zeroCount > 0 ? 'status-warning' : 'status-success'
		},

		/**
		 * CSS class for retention status text
		 *
		 * @return {string} CSS class
		 */
		retentionStatusTextClass() {
			const zeroCount = [
				this.retentionOptions.callLogRetention,
				this.retentionOptions.jobLogRetention,
				this.retentionOptions.syncLogRetention,
				this.retentionOptions.contractLogRetention,
			].filter(val => val === 0).length

			return zeroCount > 0 ? 'status-warning-text' : 'status-enabled'
		},
	},



	/**
	 * Lifecycle hook that loads settings when component is created
	 */
	async created() {
		await this.loadSettings()
		await this.loadStats()
	},

	methods: {
		/**
		 * Loads all settings from the backend
		 *
		 * @async
		 * @return {Promise<void>}
		 */
		async loadSettings() {
			this.loading = true
			this.loadingVersionInfo = true

			try {
				const response = await fetch('/index.php/apps/openconnector/api/settings')
				const data = await response.json()

				if (data.error) {
					console.error('Failed to load settings:', data.error)
					return
				}

				// Version information
				this.versionInfo = data.version

				// Retention settings
				if (data.retention) {
					this.retentionOptions = {
						callLogRetention: data.retention.callLogRetention || 2592000000,
						jobLogRetention: data.retention.jobLogRetention || 2592000000,
						syncLogRetention: data.retention.syncLogRetention || 2592000000,
						contractLogRetention: data.retention.contractLogRetention || 2592000000,
					}
				}

			} catch (error) {
				console.error('Failed to load settings:', error)
			} finally {
				this.loading = false
				this.loadingVersionInfo = false
			}
		},



		/**
		 * Format retention period in milliseconds to human-readable format
		 *
		 * @param {number} ms Milliseconds
		 * @return {string} Formatted time period
		 */
		formatRetentionPeriod(ms) {
			if (!ms || ms === 0) {
				return 'Forever (not advisable)'
			}

			const units = [
				{ name: 'year', ms: 365 * 24 * 60 * 60 * 1000, plural: 'years' },
				{ name: 'month', ms: 30 * 24 * 60 * 60 * 1000, plural: 'months' },
				{ name: 'week', ms: 7 * 24 * 60 * 60 * 1000, plural: 'weeks' },
				{ name: 'day', ms: 24 * 60 * 60 * 1000, plural: 'days' },
				{ name: 'hour', ms: 60 * 60 * 1000, plural: 'hours' },
				{ name: 'minute', ms: 60 * 1000, plural: 'minutes' },
			]

			const parts = []
			let remaining = ms

			for (const unit of units) {
				const count = Math.floor(remaining / unit.ms)
				if (count > 0) {
					parts.push(`${count} ${count === 1 ? unit.name : unit.plural}`)
					remaining -= count * unit.ms
				}
			}

			if (parts.length === 0) {
				return 'Less than 1 minute'
			}

			// Show up to 2 most significant units
			return parts.slice(0, 2).join(', ')
		},

		/**
		 * Saves all settings to the backend
		 *
		 * @async
		 * @return {Promise<void>}
		 */
		async saveSettings() {
			this.saving = true

			try {
				const settingsData = {
					retention: {
						callLogRetention: this.retentionOptions.callLogRetention,
						jobLogRetention: this.retentionOptions.jobLogRetention,
						syncLogRetention: this.retentionOptions.syncLogRetention,
						contractLogRetention: this.retentionOptions.contractLogRetention,
					},
				}

				const response = await fetch('/index.php/apps/openconnector/api/settings', {
					method: 'PUT',
					headers: {
						'Content-Type': 'application/json',
					},
					body: JSON.stringify(settingsData),
				})

				const result = await response.json()

				if (result.error) {
					console.error('Failed to save settings:', result.error)
					return
				}

				// Update Retention settings
				if (result.retention) {
					this.retentionOptions = {
						callLogRetention: result.retention.callLogRetention,
						jobLogRetention: result.retention.jobLogRetention,
						syncLogRetention: result.retention.syncLogRetention,
						contractLogRetention: result.retention.contractLogRetention,
					}
				}

			} catch (error) {
				console.error('Failed to save settings:', error)
			} finally {
				this.saving = false
			}
		},



		/**
		 * Shows the rebase confirmation dialog
		 *
		 * @return {void}
		 */
		showRebaseDialog() {
			this.showRebaseConfirmation = true
		},

		/**
		 * Hides the rebase confirmation dialog
		 *
		 * @return {void}
		 */
		hideRebaseDialog() {
			this.showRebaseConfirmation = false
		},

		/**
		 * Performs the rebase operation
		 *
		 * @async
		 * @return {Promise<void>}
		 */
		async performRebase() {
			this.rebasing = true

			try {
				const response = await fetch('/index.php/apps/openconnector/api/settings/rebase', {
					method: 'POST',
					headers: {
						'Content-Type': 'application/json',
					},
				})

				const result = await response.json()

				if (result.error) {
					console.error('Failed to rebase:', result.error)
					// You could add a toast notification here
					return
				}

				// Hide the dialog and show success
				this.hideRebaseDialog()
				// You could add a success toast notification here

			} catch (error) {
				console.error('Failed to rebase:', error)
				// You could add an error toast notification here
			} finally {
				this.rebasing = false
			}
		},

		/**
		 * Load statistics from the backend
		 *
		 * @async
		 * @return {Promise<void>}
		 */
		async loadStats() {
			this.loadingStats = true

			try {
				const response = await fetch('/index.php/apps/openconnector/api/settings/stats')
				const data = await response.json()

				if (data.error) {
					console.error('Failed to load stats:', data.error)
					return
				}

				this.stats = {
					warnings: data.warnings || {},
					totals: data.totals || {},
					lastUpdated: new Date(data.lastUpdated || Date.now()),
				}

			} catch (error) {
				console.error('Failed to load stats:', error)
			} finally {
				this.loadingStats = false
			}
		},

		/**
		 * Format a date for display
		 *
		 * @param {Date|string} date Date to format
		 * @return {string} Formatted date string
		 */
		formatDate(date) {
			if (!date) return 'Unknown'

			const dateObj = date instanceof Date ? date : new Date(date)
			return dateObj.toLocaleString()
		},

		/**
		 * Format bytes to human readable format
		 *
		 * @param {number} bytes Number of bytes
		 * @return {string} Formatted byte string
		 */
		formatBytes(bytes) {
			if (!bytes || bytes === 0) return '0 Bytes'

			const k = 1024
			const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB', 'PB']
			const i = Math.floor(Math.log(bytes) / Math.log(k))

			return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
		},
	},
})
</script>

<style scoped>
.version-info {
	max-width: none;
}

.version-details {
	margin-bottom: 2rem;
	padding: 1rem;
	background-color: var(--color-background-hover);
	border-radius: var(--border-radius-large);
}

.version-item {
	margin-bottom: 0.5rem;
	display: flex;
	align-items: center;
	gap: 0.5rem;
}

.version-item:last-child {
	margin-bottom: 0;
}

.no-version {
	color: var(--color-text-lighter);
	font-style: italic;
}

.status-ok {
	color: var(--color-success);
	font-weight: bold;
}

.status-warning {
	color: var(--color-warning);
	font-weight: bold;
}

.status-error {
	color: var(--color-error);
	font-weight: bold;
}

.rbac-options,
.multitenancy-options,
.retention-options {
	max-width: none;
}

.option-section {
	margin-bottom: 1.5rem;
	padding: 1rem 0;
	border-bottom: 1px solid var(--color-border);
}

.option-section:last-child {
	border-bottom: none;
}

.option-description {
	margin-top: 0.5rem;
	color: var(--color-text-lighter);
	font-size: 0.9rem;
	line-height: 1.4;
}

.button-container {
	margin-top: 2rem;
}

.loading-icon {
	display: flex;
	justify-content: center;
	margin: 2rem 0;
}

h3 {
	font-size: 13px;
}

h4 {
	margin-bottom: 0.5rem;
	font-weight: bold;
}

.section-header-inline {
	display: flex;
	justify-content: space-between;
	align-items: center;
	gap: 1rem;
	position: relative;
	top: -45px;
	margin-bottom: -40px;
	z-index: 10;
}

.section-subtitle {
	color: var(--color-text-lighter);
	font-size: 0.9rem;
}

.section-header {
	display: flex;
	justify-content: space-between;
	align-items: flex-start;
	gap: 2rem;
	margin-bottom: 1rem;
}

.section-description {
	flex: 1;
	max-width: 70%;
}

.section-controls {
	flex-shrink: 0;
	align-self: flex-start;
}

.main-description {
	margin-bottom: 1rem;
	line-height: 1.5;
	color: var(--color-text-light);
}

.toggle-status {
	margin-bottom: 1rem;
	padding: 0.75rem;
	background-color: var(--color-background-hover);
	border-radius: var(--border-radius);
}

.toggle-status:has(.status-enabled) {
	border-left: 4px solid var(--color-success);
}

.toggle-status:has(.status-disabled) {
	border-left: 4px solid var(--color-error);
}

.status-enabled {
	color: var(--color-success);
	font-weight: bold;
}

.status-disabled {
	color: var(--color-error);
	font-weight: bold;
}

.status-warning {
	border-left: 4px solid var(--color-warning);
}

.status-warning-text {
	color: var(--color-warning);
	font-weight: bold;
}

.status-success {
	border-left: 4px solid var(--color-success);
}

.warning-box {
	background-color: var(--color-warning-light, #fff3cd);
	border: 1px solid var(--color-warning, #ffc107);
	border-radius: var(--border-radius);
	padding: 0.75rem;
	margin-bottom: 1rem;
	color: var(--color-warning-dark, #856404);
}

.impact-description {
	margin-bottom: 0;
	padding: 0.75rem;
	background-color: var(--color-background-dark);
	border-radius: var(--border-radius);
	line-height: 1.4;
	font-size: 0.9rem;
}

.impact-description strong {
	color: var(--color-text-dark);
}

@media (max-width: 768px) {
	.section-header {
		flex-direction: column;
		align-items: stretch;
		gap: 1rem;
	}

	.section-description {
		max-width: 100%;
	}

	.section-controls {
		align-self: stretch;
	}
}

.groups-table {
	margin-top: 1rem;
	border: 1px solid var(--color-border);
	border-radius: var(--border-radius);
	overflow: hidden;
}

.retention-table {
	margin-top: 1rem;
	display: flex;
	flex-direction: column;
	gap: 0.5rem;
}

.groups-row {
	display: flex;
	align-items: flex-start;
	padding: 1rem;
	border-bottom: 1px solid var(--color-border);
	background-color: var(--color-background-hover);
}

.retention-row {
	display: grid;
	grid-template-columns: 1fr auto 1fr;
	gap: 1.5rem;
	align-items: center;
	padding: 1rem;
	background-color: var(--color-background-hover);
	border-radius: var(--border-radius);
	border: 1px solid var(--color-border);
}

.groups-row:last-child {
	border-bottom: none;
}

.groups-row:nth-child(even) {
	background-color: var(--color-background-dark);
}

.group-label {
	flex: 1;
	padding-right: 1rem;
}

.group-label strong {
	display: block;
	margin-bottom: 0.5rem;
	color: var(--color-text-dark);
}

.user-type-description {
	margin: 0;
	font-size: 0.9rem;
	color: var(--color-text-lighter);
	line-height: 1.4;
}

.group-select {
	flex: 0 0 250px;
	min-width: 250px;
	display: flex;
	flex-direction: column;
	gap: 0.5rem;
}

.retention-input-wrapper {
	display: flex;
	align-items: center;
	gap: 0.5rem;
}

.retention-input {
	flex: 1;
	padding: 0.5rem;
	border: 1px solid var(--color-border);
	border-radius: var(--border-radius);
	background-color: var(--color-main-background);
	color: var(--color-main-text);
}

.retention-input:focus {
	outline: none;
	border-color: var(--color-primary);
	box-shadow: 0 0 0 2px var(--color-primary-light);
}

.retention-label {
	display: flex;
	flex-direction: column;
	gap: 0.5rem;
}

.retention-label strong {
	font-size: 1rem;
	color: var(--color-text-dark);
}

.retention-description {
	margin: 0;
	color: var(--color-text-light);
	font-size: 0.9rem;
	line-height: 1.4;
}

.retention-input-field {
	width: 140px;
	padding: 0.5rem;
	border: 1px solid var(--color-border);
	border-radius: var(--border-radius);
	text-align: right;
	background-color: var(--color-main-background);
}

.retention-input-field:focus {
	outline: none;
	border-color: var(--color-primary);
	box-shadow: 0 0 0 2px var(--color-primary-light);
}

.retention-unit {
	color: var(--color-text-lighter);
	font-size: 0.9rem;
	font-weight: bold;
	min-width: 20px;
}

.retention-display {
	font-size: 0.8rem;
	color: var(--color-text-lighter);
	font-style: italic;
	padding: 0.25rem 0.5rem;
	background-color: var(--color-background-hover);
	border-radius: var(--border-radius-small);
	border-left: 3px solid var(--color-primary);
}

.retention-category-header {
	background-color: var(--color-background-dark) !important;
	border-bottom: 2px solid var(--color-border-dark) !important;
	font-weight: bold;
}

.retention-category-header .group-label strong {
	font-size: 1rem;
	color: var(--color-text-dark);
}

.category-indicator {
	padding: 0.25rem 0.75rem;
	background-color: var(--color-primary);
	color: white;
	border-radius: var(--border-radius);
	font-size: 0.8rem;
	font-weight: bold;
	text-transform: uppercase;
	letter-spacing: 0.5px;
}

.button-group {
	display: flex;
	gap: 0.5rem;
	align-items: center;
}

.rebase-dialog {
	padding: 1.5rem;
	max-width: 700px;
	width: 100%;
}

.rebase-warning {
	margin-bottom: 1.5rem;
}

.rebase-warning h3 {
	color: var(--color-error);
	margin-bottom: 1rem;
	font-size: 1.1rem;
}

.warning-text {
	margin-bottom: 1rem;
	line-height: 1.5;
	color: var(--color-text-light);
}

.impact-text {
	padding: 1rem;
	background-color: var(--color-warning-light, #fff3cd);
	border: 1px solid var(--color-warning, #ffc107);
	border-radius: var(--border-radius);
	margin-bottom: 1rem;
	color: var(--color-warning-dark, #856404);
	line-height: 1.4;
	font-size: 0.9rem;
}

.dialog-actions {
	display: flex;
	justify-content: flex-end;
	gap: 0.5rem;
	margin-top: 1rem;
}

.stats-section {
	max-width: none;
}

.stats-content {
	margin-top: 1rem;
}

.stats-grid {
	display: grid;
	grid-template-columns: 1fr 1fr;
	gap: 1.5rem;
	margin-bottom: 1.5rem;
}

.stats-card {
	padding: 1.5rem;
	border-radius: var(--border-radius-large);
	border: 1px solid var(--color-border);
	background-color: var(--color-background-hover);
}

.stats-card h4 {
	margin: 0 0 1rem 0;
	font-size: 1rem;
	font-weight: bold;
	color: var(--color-text-dark);
}

.stats-items {
	display: flex;
	flex-direction: column;
	gap: 0.75rem;
}

.stats-item {
	display: flex;
	justify-content: space-between;
	align-items: center;
	padding: 0.5rem 0;
	border-bottom: 1px solid var(--color-border-light);
}

.stats-item:last-child {
	border-bottom: none;
}

.stats-label {
	color: var(--color-text-light);
	font-size: 0.9rem;
}

.stats-value {
	font-weight: bold;
	font-size: 1rem;
}

.stats-value.warning {
	color: var(--color-warning);
}

.stats-value.total {
	color: var(--color-primary);
}

.stats-footer {
	text-align: center;
	padding-top: 1rem;
	border-top: 1px solid var(--color-border);
}

.stats-updated {
	margin: 0;
	color: var(--color-text-lighter);
	font-size: 0.8rem;
}

/* Stats Table Styles */
.stats-table-container {
	margin-top: 1rem;
	overflow-x: auto;
}

.stats-table {
	width: 100%;
	border-collapse: collapse;
	font-size: 0.9rem;
}

.stats-table-header {
	padding: 0.75rem 1rem;
	text-align: left;
	font-weight: 600;
	color: var(--color-text-maxcontrast);
	background-color: var(--color-background-dark);
	border-bottom: 1px solid var(--color-border);
}

.stats-table-header:first-child {
	border-top-left-radius: var(--border-radius);
}

.stats-table-header:last-child {
	border-top-right-radius: var(--border-radius);
}

.stats-table-row {
	border-bottom: 1px solid var(--color-border-dark);
}

.stats-table-row:hover {
	background-color: var(--color-background-hover);
}

.stats-table-label {
	padding: 0.75rem 1rem;
	font-weight: 500;
	color: var(--color-text-light);
}

.stats-table-value {
	padding: 0.75rem 1rem;
	text-align: right;
	font-weight: 600;
}

.stats-table-value.total {
	color: var(--color-primary-element);
}

.stats-table-value.danger {
	color: var(--color-error);
	font-weight: bold;
}

@media (max-width: 768px) {
	.stats-grid {
		grid-template-columns: 1fr;
		gap: 1rem;
	}
}

@media (max-width: 768px) {
	.groups-row {
		flex-direction: column;
		gap: 1rem;
	}

	.group-label {
		padding-right: 0;
	}

	.group-select {
		flex: 1;
		min-width: auto;
	}
}
</style>
