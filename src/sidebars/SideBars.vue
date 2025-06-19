<script setup>
import { navigationStore } from '../store/store.js'
</script>

<template>
	<div>
		<!-- Contracts Sidebar -->
		<ContractsSideBar v-if="navigationStore.selected === 'contracts'" />

		<!-- Logs Sidebar -->
		<LogsSideBar v-if="navigationStore.selected === 'logs'" />
	</div>
</template>

<script>
import ContractsSideBar from './contracts/ContractsSideBar.vue'
import LogsSideBar from './logs/LogsSideBar.vue'

export default {
	name: 'SideBars',
	components: {
		ContractsSideBar,
		LogsSideBar,
	},
}
</script>
