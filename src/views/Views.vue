<script setup>
import { navigationStore } from '../store/store.js'
</script>

<template>
	<!-- Placeholder div for all of the views-->
	<NcAppContent>
		<template #default>
			<Dashboard v-if="navigationStore.selected === 'dashboard'" />
			<SourcesIndex v-if="navigationStore.selected === 'sources'" />
			<EndpointsIndex v-if="navigationStore.selected === 'endpoints'" />
			<WebhooksIndex v-if="navigationStore.selected === 'webhooks'" />
			<JobsIndex v-if="navigationStore.selected === 'jobs'" />
			<LogsIndex v-if="navigationStore.selected === 'logs'" />
			<MappingsIndex v-if="navigationStore.selected === 'mappings'" />
			<SynchronizationsIndex v-if="navigationStore.selected === 'synchronizations'" />
		</template>
	</NcAppContent>
</template>

<script>
import { NcAppContent } from '@nextcloud/vue'
import Dashboard from './dashboard/DashboardIndex.vue'
import SourcesIndex from './Source/SourcesIndex.vue'
import EndpointsIndex from './Endpoint/EndpointsIndex.vue'
import WebhooksIndex from './Webhook/WebhooksIndex.vue'
import JobsIndex from './Job/JobsIndex.vue'
import LogsIndex from './Log/LogsIndex.vue'
import MappingsIndex from './Mapping/MappingsIndex.vue'
import SynchronizationsIndex from './Synchronization/SynchronizationsIndex.vue'

export default {
	name: 'Views',
	components: {
		Dashboard,
		NcAppContent,
		SourcesIndex,
		EndpointsIndex,
		WebhooksIndex,
		JobsIndex,
		LogsIndex,
		MappingsIndex,
		SynchronizationsIndex,
	},
}
</script>
