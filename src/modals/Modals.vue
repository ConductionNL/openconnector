<script setup>
import { navigationStore } from '../store/store.js'
</script>

<template>
	<div>
		<EditEndpoint />
		<DeleteEndpoint />
		<EditConsumer />
		<DeleteConsumer />
		<DeleteSource />
		<EditSource />
		<ViewSource />
		<TestSource />
		<DeleteJob />
		<TestJob />
		<RunJob v-if="navigationStore.modal === 'runJob'" />
		<EditJob />
		<DeleteLog />
		<EditLog />
		<DeleteMapping />
		<EditMapping />
		<TestMapping v-if="navigationStore.modal === 'testMapping'" />
		<EditMappingMapping />
		<DeleteMappingMapping />
		<EditMappingCast />
		<DeleteMappingCast />
		<EditMappingUnset v-if="navigationStore.modal === 'editMappingUnset'" />
		<DeleteMappingUnset v-if="navigationStore.modal === 'deleteMappingUnset'" />
		<DeleteSynchronization />
		<EditSynchronization v-if="navigationStore.modal === 'editSynchronization'" />
		<TestSynchronization v-if="navigationStore.modal === 'testSynchronization'" />
		<RunSynchronization v-if="navigationStore.modal === 'runSynchronization'" />
		<EditJobArgument />
		<DeleteJobArgument />
		<EditSourceConfiguration />
		<DeleteSourceConfiguration />
		<EditSourceConfigurationAuthentication v-if="navigationStore.modal === 'editSourceConfigurationAuthentication'" />
		<DeleteSourceConfigurationAuthentication v-if="navigationStore.modal === 'deleteSourceConfigurationAuthentication'" />
		<ViewSourceLog />
		<ViewJobLog />
		<ViewSynchronizationLog />
		<ViewSynchronizationContract />
		<EditSynchronizationSourceConfig v-if="navigationStore.modal === 'editSynchronizationSourceConfig'" />
		<DeleteSynchronizationSourceConfig v-if="navigationStore.modal === 'deleteSynchronizationSourceConfig'" />
		<EditSynchronizationTargetConfig v-if="navigationStore.modal === 'editSynchronizationTargetConfig'" />
		<DeleteSynchronizationTargetConfig v-if="navigationStore.modal === 'deleteSynchronizationTargetConfig'" />
		<ImportFile v-if="navigationStore.modal === 'importFile'" />
		<EditRule v-if="navigationStore.modal === 'editRule'" />
		<DeleteRule v-if="navigationStore.dialog === 'deleteRule'" />
		<AddEndpointRule v-if="navigationStore.modal === 'addEndpointRule'" />
		<ViewJob v-if="navigationStore.modal === 'viewJob'" />
		<ViewEndpoint v-if="navigationStore.modal === 'viewEndpoint'" />
		<ViewSynchronization v-if="navigationStore.modal === 'viewSynchronization'" />
		<ViewMapping v-if="navigationStore.modal === 'viewMapping'" />
	</div>
</template>

<script>
import EditEndpoint from './Endpoint/EditEndpoint.vue'
import DeleteEndpoint from './Endpoint/DeleteEndpoint.vue'
import EditConsumer from './Consumer/EditConsumer.vue'
import DeleteConsumer from './Consumer/DeleteConsumer.vue'
import EditSource from './Source/EditSource.vue'
import ViewSource from './Source/ViewSource.vue'
import TestSource from './TestSource/TestSource.vue'
import DeleteSource from './Source/DeleteSource.vue'
import TestJob from './Job/TestJob.vue'
import RunJob from './Job/RunJob.vue'
import EditJob from './Job/EditJob.vue'
import DeleteJob from './Job/DeleteJob.vue'
import EditLog from './Log/EditLog.vue'
import DeleteLog from './Log/DeleteLog.vue'
import EditMapping from './Mapping/EditMapping.vue'
import DeleteMapping from './Mapping/DeleteMapping.vue'
import TestMapping from './MappingTest/TestMapping.vue'
import EditSynchronization from './Synchronization/EditSynchronization.vue'
import DeleteSynchronization from './Synchronization/DeleteSynchronization.vue'
import TestSynchronization from './Synchronization/TestSynchronization.vue'
import RunSynchronization from './Synchronization/RunSynchronization.vue'
import EditJobArgument from './JobArgument/EditJobArgument.vue'
import DeleteJobArgument from './JobArgument/DeleteJobArgument.vue'
import EditSourceConfiguration from './SourceConfiguration/EditSourceConfiguration.vue'
import DeleteSourceConfiguration from './SourceConfiguration/DeleteSourceConfiguration.vue'
import EditSourceConfigurationAuthentication from './SourceConfigurationAuthentication/EditSourceConfigurationAuthentication.vue'
import DeleteSourceConfigurationAuthentication from './SourceConfigurationAuthentication/DeleteSourceConfigurationAuthentication.vue'
import ViewSourceLog from './Log/ViewSourceLog.vue'
import EditMappingMapping from './mappingMapping/EditMappingMapping.vue'
import DeleteMappingMapping from './mappingMapping/DeleteMappingMapping.vue'
import EditMappingCast from './mappingCast/EditMappingCast.vue'
import DeleteMappingCast from './mappingCast/DeleteMappingCast.vue'
import ViewJobLog from './Log/ViewJobLog.vue'
import ViewSynchronizationLog from './Log/ViewSynchronizationLog.vue'
import ViewSynchronizationContract from './Log/ViewSynchronizationContract.vue'
import EditMappingUnset from './mappingUnset/EditMappingUnset.vue'
import DeleteMappingUnset from './mappingUnset/DeleteMappingUnset.vue'
import EditSynchronizationSourceConfig from './SynchronizationSourceConfig/EditSynchronizationSourceConfig.vue'
import DeleteSynchronizationSourceConfig from './SynchronizationSourceConfig/DeleteSynchronizationSourceConfig.vue'
import EditSynchronizationTargetConfig from './SynchronizationTargetConfig/EditSynchronizationTargetConfig.vue'
import DeleteSynchronizationTargetConfig from './SynchronizationTargetConfig/DeleteSynchronizationTargetConfig.vue'
import ImportFile from './Import/ImportFile.vue'
import EditRule from './Rule/EditRule.vue'
import DeleteRule from './Rule/DeleteRule.vue'
import AddEndpointRule from './Endpoint/AddEndpointRule.vue'
import ViewJob from './Job/ViewJob.vue'
import ViewEndpoint from './Endpoint/ViewEndpoint.vue'
import ViewSynchronization from './Synchronization/ViewSynchronization.vue'
import ViewMapping from './Mapping/ViewMapping.vue'

export default {
	name: 'Modals',
	components: {
		EditEndpoint,
		DeleteEndpoint,
		EditConsumer,
		DeleteConsumer,
		DeleteSource,
		EditSource,
		ViewSource,
		TestSource,
		DeleteJob,
		EditJob,
		RunJob,
		TestJob,
		DeleteLog,
		EditLog,
		DeleteMapping,
		EditMapping,
		TestMapping,
		DeleteSynchronization,
		EditSynchronization,
		TestSynchronization,
		RunSynchronization,
		EditJobArgument,
		DeleteJobArgument,
		EditSourceConfiguration,
		DeleteSourceConfiguration,
		EditSourceConfigurationAuthentication,
		DeleteSourceConfigurationAuthentication,
		ViewSourceLog,
		EditMappingMapping,
		DeleteMappingMapping,
		EditMappingCast,
		DeleteMappingCast,
		ViewJobLog,
		ViewSynchronizationLog,
		ViewSynchronizationContract,
		EditMappingUnset,
		DeleteMappingUnset,
		EditSynchronizationSourceConfig,
		DeleteSynchronizationSourceConfig,
		EditSynchronizationTargetConfig,
		DeleteSynchronizationTargetConfig,
		ImportFile,
		EditRule,
		DeleteRule,
		AddEndpointRule,
		ViewJob,
		ViewEndpoint,
		ViewSynchronization,
		ViewMapping,
	},
	setup() {
		return {
			navigationStore,
		}
	},
}
</script>
