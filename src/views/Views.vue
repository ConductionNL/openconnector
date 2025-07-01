<script setup>
import { navigationStore } from '../store/store.js'
</script>

<template>
	<!-- Placeholder div for all of the views-->
	<NcAppContent>
		<template #default>
			<Dashboard v-if="navigationStore.selected === 'dashboard'" />
			<SourcesIndex v-if="navigationStore.selected === 'sources'" />
			<SourceLogIndex v-if="navigationStore.selected === 'source-logs'" />
			<EndpointsIndex v-if="navigationStore.selected === 'endpoints'" />
			<EndpointLogIndex v-if="navigationStore.selected === 'endpoint-logs'" />
			<ConsumersIndex v-if="navigationStore.selected === 'consumers'" />
			<WebhooksIndex v-if="navigationStore.selected === 'webhooks'" />
			<JobsIndex v-if="navigationStore.selected === 'jobs'" />
			<JobLogIndex v-if="navigationStore.selected === 'job-logs'" />
			<MappingsIndex v-if="navigationStore.selected === 'mappings'" />
			<SynchronizationsIndex v-if="navigationStore.selected === 'synchronizations'" />
			<ContractsIndex v-if="navigationStore.selected === 'contracts'" />
			<SynchronizationLogIndex v-if="navigationStore.selected === 'synchronization-logs'" />
			<EventsIndex v-if="navigationStore.selected === 'events'" />
			<RulesIndex v-if="navigationStore.selected === 'rules'" />
			<ImportIndex v-if="navigationStore.selected === 'imports'" />
			<EventLogIndex v-if="navigationStore.selected === 'event-logs'" />
		</template>
	</NcAppContent>
</template>

<script>
import { NcAppContent } from '@nextcloud/vue'
import Dashboard from './dashboard/DashboardIndex.vue'
import SourcesIndex from './Source/SourcesIndex.vue'
import SourceLogIndex from './Source/SourceLogIndex.vue'
import EndpointsIndex from './Endpoint/EndpointsIndex.vue'
import EndpointLogIndex from './Endpoint/EndpointLogIndex.vue'
import ConsumersIndex from './Consumer/ConsumersIndex.vue'
import WebhooksIndex from './Webhook/WebhooksIndex.vue'
import JobsIndex from './Job/JobsIndex.vue'
import JobLogIndex from './Job/JobLogIndex.vue'
import MappingsIndex from './Mapping/MappingsIndex.vue'
import SynchronizationsIndex from './Synchronization/SynchronizationsIndex.vue'
import ContractsIndex from './contracts/ContractsIndex.vue'
import SynchronizationLogIndex from './Synchronization/SynchronizationLogIndex.vue'
import EventsIndex from './event/EventIndex.vue'
import RulesIndex from './rule/RuleIndex.vue'
import ImportIndex from './Import/ImportIndex.vue'
import EventLogIndex from './event/EventLogIndex.vue'

export default {
	name: 'Views',
	components: {
		Dashboard,
		NcAppContent,
		SourcesIndex,
		SourceLogIndex,
		EndpointsIndex,
		EndpointLogIndex,
		ConsumersIndex,
		WebhooksIndex,
		JobsIndex,
		JobLogIndex,
		MappingsIndex,
		SynchronizationsIndex,
		ContractsIndex,
		SynchronizationLogIndex,
		EventsIndex,
		RulesIndex,
		ImportIndex,
		EventLogIndex,
	},
}
</script>
