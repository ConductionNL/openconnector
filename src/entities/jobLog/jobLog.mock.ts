import { JobLog } from './jobLog'
import { TJobLog } from './jobLog.types'

export const mockJobLogData = (): TJobLog[] => [
	{
		id: '5137a1e5-b54d-43ad-abd1-4b5bff5fcd3f',
		jobId: 'job-001',
		jobListId: 'list-001',
		jobClass: 'OCA\\OpenConnector\\Action\\PingAction',
		arguments: { url: 'https://example.com' },
		executionTime: 3600,
		userId: 'user-001',
		lastRun: '2023-05-01T12:00:00Z',
		nextRun: '2023-05-02T12:00:00Z',
		created: '2023-05-01T00:00:00Z',
		uuid: '',
		level: '',
		message: '',
		sessionId: '',
		stackTrace: [],
		expires: '',
	},
	{
		id: '4c3edd34-a90d-4d2a-8894-adb5836ecde8',
		jobId: 'job-002',
		jobListId: 'list-002',
		jobClass: 'OCA\\OpenConnector\\Action\\BackupAction',
		arguments: { destination: '/backup' },
		executionTime: 7200,
		userId: 'user-002',
		lastRun: '2023-05-01T00:00:00Z',
		nextRun: '2023-05-08T00:00:00Z',
		created: '2023-04-30T00:00:00Z',
		uuid: '',
		level: '',
		message: '',
		sessionId: '',
		stackTrace: [],
		expires: '',
	},
]

export const mockJobLog = (data: TJobLog[] = mockJobLogData()): JobLog[] => data.map(item => new JobLog(item))
