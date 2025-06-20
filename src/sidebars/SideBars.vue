<script setup>
import { navigationStore } from '../store/store.js'
</script>

<template>
	<div>
		<!-- Source Logs Sidebar -->
		<SourceLogSideBar v-if="navigationStore.selected === 'source-logs'" />

		<!-- Endpoint Logs Sidebar -->
		<EndpointLogSideBar v-if="navigationStore.selected === 'endpoint-logs'" />

		<!-- Job Logs Sidebar -->
		<JobLogSideBar v-if="navigationStore.selected === 'job-logs'" />

		<!-- Contracts Sidebar -->
		<ContractsSideBar v-if="navigationStore.selected === 'contracts'" />

		<!-- Logs Sidebar -->
		<LogsSideBar v-if="navigationStore.selected === 'synchronization-logs'" />

		<!-- Event Logs Sidebar -->
		<EventLogSideBar v-if="navigationStore.selected === 'event-logs'" />
	</div>
</template>

<script>
import SourceLogSideBar from './Source/SourceLogSideBar.vue'
import EndpointLogSideBar from './Endpoint/EndpointLogSideBar.vue'
import JobLogSideBar from './Job/JobLogSideBar.vue'
import ContractsSideBar from './contracts/ContractsSideBar.vue'
import LogsSideBar from './logs/LogsSideBar.vue'
import EventLogSideBar from './event/EventLogSideBar.vue'

export default {
	name: 'SideBars',
	components: {
		SourceLogSideBar,
		EndpointLogSideBar,
		JobLogSideBar,
		ContractsSideBar,
		LogsSideBar,
		EventLogSideBar,
	},
}
</script>
