import { Job } from './job'
import { TJob } from './job.types'

export const mockJobData = (): TJob[] => [
	{
		id: '5137a1e5-b54d-43ad-abd1-4b5bff5fcd3f',
		name: 'Daily Backup',
		description: 'Performs a daily backup of the system',
		jobClass: 'OCA\\OpenConnector\\Action\\BackupAction',
		arguments: { backupType: 'full' },
		interval: 86400, // 24 hours in seconds
		executionTime: 7200, // 2 hours in seconds
		timeSensitive: true,
		allowParallelRuns: false,
		isEnabled: true,
		singleRun: false,
		scheduleAfter: null,
		userId: 'admin',
		jobListId: 'daily-jobs',
		logRetention: 0,
		errorRetention: 0,
		lastRun: '',
		nextRun: '',
		created: '',
		updated: '',
	},
	{
		id: '4c3edd34-a90d-4d2a-8894-adb5836ecde8',
		name: 'Weekly Report',
		description: 'Generates and sends weekly reports',
		jobClass: 'OCA\\OpenConnector\\Action\\ReportAction',
		arguments: { reportType: 'weekly' },
		interval: 604800, // 7 days in seconds
		executionTime: 3600, // 1 hour in seconds
		timeSensitive: false,
		allowParallelRuns: false,
		isEnabled: true,
		singleRun: false,
		scheduleAfter: null,
		userId: 'reporter',
		jobListId: 'weekly-jobs',
		logRetention: 0,
		errorRetention: 0,
		lastRun: '',
		nextRun: '',
		created: '',
		updated: '',
	},
]

export const mockJob = (data: TJob[] = mockJobData()): TJob[] => data.map(item => new Job(item))
