<template>
	<NcContent app-name="openconnector">
		<MainMenu />
		<NcAppContent>
			<template #default>
				<router-view />
			</template>
		</NcAppContent>
		<router-view name="sidebar" />
		<Modals />
		<Dialogs />
	</NcContent>
</template>

<script>

import { NcContent, NcAppContent } from '@nextcloud/vue'
import MainMenu from './navigation/MainMenu.vue'
import Modals from './modals/Modals.vue'
import Dialogs from './dialogs/Dialogs.vue'

export default {
	name: 'App',
	components: {
		NcContent,
		MainMenu,
		Modals,
		Dialogs,
		NcAppContent,
	},
}
</script>
