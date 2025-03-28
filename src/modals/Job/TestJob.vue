<script setup>
import { jobStore, navigationStore } from '../../store/store.js'
</script>

<template>
	<NcModal v-if="navigationStore.modal === 'testJob'"
		ref="modalRef"
		label-id="testJob"
		@close="closeModal">
		<div class="modalContent">
			<h2>Test job</h2>

			<NcButton
				:disabled="loading"
				type="primary"
				@click="testJob()">
				<template #icon>
					<NcLoadingIcon v-if="loading" :size="20" />
					<Sync v-if="!loading" :size="20" />
				</template>
				Test job
			</NcButton>

			<div v-if="jobStore.jobTest">
				<NcNoteCard v-if="jobStore.jobTest?.level === 'INFO'" type="success">
					<p>The job test was successful. {{ jobStore.jobTest?.message }}</p>
				</NcNoteCard>
				<NcNoteCard v-if="(jobStore.jobTest?.level !== 'INFO') || error" type="error">
					<p>An error occurred while testing the job test: {{ jobStore.jobTest ? jobStore.jobTest.message : error }}</p>
				</NcNoteCard>
			</div>

			<div v-if="jobStore.jobTest" class="jobTestTable">
				<table>
					<tr>
						<th>UUID</th>
						<td>{{ jobStore.jobTest.uuid }}</td>
					</tr>
					<tr>
						<th>Level</th>
						<td>{{ jobStore.jobTest.level }}</td>
					</tr>
					<tr>
						<th>Message</th>
						<td>{{ jobStore.jobTest.message }}</td>
					</tr>
					<tr>
						<th>Job ID</th>
						<td>{{ jobStore.jobTest.jobId }}</td>
					</tr>
					<tr>
						<th>Job List ID</th>
						<td>{{ jobStore.jobTest.jobListId }}</td>
					</tr>
					<tr>
						<th>Job Class</th>
						<td>{{ jobStore.jobTest.jobClass || 'N/A' }}</td>
					</tr>
					<tr>
						<th>Arguments</th>
						<td>
							<ul>
								<li v-for="(value, key) in jobStore.jobTest.arguments" :key="key">
									{{ key }}: {{ value }}
								</li>
							</ul>
						</td>
					</tr>
					<tr>
						<th>Execution Time</th>
						<td>{{ jobStore.jobTest.executionTime }} ms</td>
					</tr>
					<tr>
						<th>User ID</th>
						<td>{{ jobStore.jobTest.userId || 'N/A' }}</td>
					</tr>
					<tr>
						<th>Session ID</th>
						<td>{{ jobStore.jobTest.sessionId || 'N/A' }}</td>
					</tr>
					<tr>
						<th>Stack Trace</th>
						<td>
							<ol>
								<li v-for="(step, index) in jobStore.jobTest.stackTrace" :key="index">
									{{ step }}
								</li>
							</ol>
						</td>
					</tr>
				</table>
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
} from '@nextcloud/vue'
import Sync from 'vue-material-design-icons/Sync.vue'

export default {
	name: 'TestJob',
	components: {
		NcModal,
		NcButton,
		NcLoadingIcon,
		NcNoteCard,
	},
	data() {
		return {
			success: false,
			loading: false,
			error: false,
		}
	},
	methods: {
		closeModal() {
			navigationStore.setModal(false)
			this.success = false
			this.loading = false
			this.error = false
		},
		async testJob() {
			this.loading = true

			try {
				await jobStore.testJob(jobStore.jobItem.id)
				this.success = true
				this.loading = false
				this.error = false
			} catch (error) {
				this.loading = false
				this.success = false
				this.error = error.message || 'An error occurred while testing the job'
				jobStore.setJobTest(false)
			}
		},
	},
}
</script>
<style>
.testJobDetailGrid {
	display: grid;
	grid-template-columns: 1fr;
	gap: 5px;
}

.jobTestTable th,
.jobTestTable td {
  padding: 4px;
}
.jobTestTable th {
    font-weight: bold
}
.jobTestTable ol {
    margin-left: 1rem;
}
</style>
