<script setup>
import { navigationStore } from '../store/store.js'
</script>

<template>
	<NcAppNavigation>
		<NcAppNavigationList>
			<NcAppNavigationItem :active="navigationStore.selected === 'dashboard'" name="Dashboard" @click="navigationStore.setSelected('dashboard')">
				<template #icon>
					<Finance :size="20" />
				</template>
			</NcAppNavigationItem>
			<NcAppNavigationItem :active="navigationStore.selected === 'sources'" name="Sources" @click="navigationStore.setSelected('sources')">
				<template #icon>
					<DatabaseArrowLeftOutline :size="20" />
				</template>
			</NcAppNavigationItem>
			<NcAppNavigationItem :active="navigationStore.selected === 'endpoints'" name="Endpoints" @click="navigationStore.setSelected('endpoints')">
				<template #icon>
					<Api :size="20" />
				</template>
			</NcAppNavigationItem>
			<NcAppNavigationItem :active="navigationStore.selected === 'consumers'" name="Consumers" @click="navigationStore.setSelected('consumers')">
				<template #icon>
					<Webhook :size="20" />
				</template>
			</NcAppNavigationItem>
			<NcAppNavigationItem :active="navigationStore.selected === 'mappings'" name="Mappings" @click="navigationStore.setSelected('mappings')">
				<template #icon>
					<SitemapOutline :size="20" />
				</template>
			</NcAppNavigationItem>
			<NcAppNavigationItem :active="navigationStore.selected === 'jobs'"
				name="Jobs"
				:allow-collapse="true"
				:open="true"
				@click="navigationStore.setSelected('jobs')">
				<template #icon>
					<Update :size="20" />
				</template>
			</NcAppNavigationItem>
			<NcAppNavigationItem :active="navigationStore.selected === 'notifications'" name="Cloud Events" @click="navigationStore.setSelected('notifications')">
				<template #icon>
					<CloudUploadOutline :size="20" />
				</template>
				<!-- This is correct according to the documentation, thats why there is a disable comment -->
				<!-- eslint-disable-next-line vue/no-lone-template -->
				<template>
					<NcAppNavigationItem :active="navigationStore.selected === 'events'" name="Events" @click="navigationStore.setSelected('events')">
						<template #icon>
							<MessageTextFastOutline :size="20" />
						</template>
					</NcAppNavigationItem>
				</template>
			</NcAppNavigationItem>
			<NcAppNavigationItem :active="navigationStore.selected === 'synchronizations'"
				name="Synchronization"
				:allow-collapse="true"
				:open="true"
				@click="navigationStore.setSelected('synchronizations')">
				<template #icon>
					<VectorPolylinePlus :size="20" />
				</template>
				<!-- This is correct according to the documentation, thats why there is a disable comment -->
				<!-- eslint-disable-next-line vue/no-lone-template -->
				<template>
					<NcAppNavigationItem :active="navigationStore.selected === 'contracts'" name="Contracts" @click="navigationStore.setSelected('contracts')">
						<template #icon>
							<FileDocumentOutline :size="20" />
						</template>
					</NcAppNavigationItem>
					<NcAppNavigationItem :active="navigationStore.selected === 'logs'" name="Logs" @click="navigationStore.setSelected('logs')">
						<template #icon>
							<TextBoxOutline :size="20" />
						</template>
					</NcAppNavigationItem>
				</template>
			</NcAppNavigationItem>
			<NcAppNavigationItem :active="navigationStore.selected === 'rules'" name="Rules" @click="navigationStore.setSelected('rules')">
				<template #icon>
					<SitemapOutline :size="20" />
				</template>
			</NcAppNavigationItem>
		</NcAppNavigationList>
		<NcAppNavigationSettings>
			<NcAppNavigationItem :active="navigationStore.selected === 'imports'" name="Import" @click="navigationStore.setSelected('imports')">
				<template #icon>
					<FileImportOutline :size="20" />
				</template>
			</NcAppNavigationItem>
		</NcAppNavigationSettings>
	</NcAppNavigation>
</template>

<script>
import {
	NcAppNavigation,
	NcAppNavigationList,
	NcAppNavigationItem,
	NcAppNavigationSettings,
} from '@nextcloud/vue'

// Icons
import Finance from 'vue-material-design-icons/Finance.vue'
import DatabaseArrowLeftOutline from 'vue-material-design-icons/DatabaseArrowLeftOutline.vue'
import Api from 'vue-material-design-icons/Api.vue'
import Webhook from 'vue-material-design-icons/Webhook.vue'
import SitemapOutline from 'vue-material-design-icons/SitemapOutline.vue'
import Update from 'vue-material-design-icons/Update.vue'
import VectorPolylinePlus from 'vue-material-design-icons/VectorPolylinePlus.vue'
import CloudUploadOutline from 'vue-material-design-icons/CloudUploadOutline.vue'
import MessageTextFastOutline from 'vue-material-design-icons/MessageTextFastOutline.vue'
import FileImportOutline from 'vue-material-design-icons/FileImportOutline.vue'
import FileDocumentOutline from 'vue-material-design-icons/FileDocumentOutline.vue'
import TextBoxOutline from 'vue-material-design-icons/TextBoxOutline.vue'

export default {
	name: 'MainMenu',
	components: {
		// components
		NcAppNavigation,
		NcAppNavigationList,
		NcAppNavigationItem,
		NcAppNavigationSettings,
		// icons
		Finance,
		DatabaseArrowLeftOutline,
		Api,
		Webhook,
		SitemapOutline,
		Update,
		VectorPolylinePlus,
		CloudUploadOutline,
		MessageTextFastOutline,
		FileImportOutline,
		FileDocumentOutline,
		TextBoxOutline,
	},
	methods: {
		openLink(url, type = '') {
			window.open(url, type)
		},
	},
}
</script>
