import { Log } from './log'
import { TLog } from './log.types'

export const mockLogData = (): TLog[] => [
	{
		id: 1,
		uuid: '5137a1e5-b54d-43ad-abd1-4b5bff5fcd3f',
		message: 'Synchronization completed successfully',
		synchronizationId: 'sync-123',
		result: [
			{ status: 'success', processed: 10, errors: 0 },
		],
		userId: 'user123',
		sessionId: 'session-abc123',
		test: false,
		force: false,
		executionTime: 1250,
		created: '2023-05-01T12:00:00Z',
		expires: '2023-06-01T12:00:00Z',
	},
	{
		id: 2,
		uuid: '4c3edd34-a90d-4d2a-8894-adb5836ecde8',
		message: 'Synchronization failed due to network timeout',
		synchronizationId: 'sync-456',
		result: [
			{ status: 'error', error: 'Network timeout after 30 seconds', processed: 5, errors: 1 },
		],
		userId: 'user456',
		sessionId: 'session-def456',
		test: false,
		force: true,
		executionTime: 30000,
		created: '2023-05-01T13:00:00Z',
		expires: '2023-06-01T13:00:00Z',
	},
	{
		id: 3,
		uuid: 'a1b2c3d4-e5f6-7890-abcd-ef1234567890',
		message: 'Test synchronization run - validation mode',
		synchronizationId: 'sync-789',
		result: [
			{ status: 'info', message: 'Validation completed', items_validated: 25 },
		],
		userId: 'user789',
		sessionId: 'session-ghi789',
		test: true,
		force: false,
		executionTime: 500,
		created: '2023-05-01T14:00:00Z',
		expires: '2023-06-01T14:00:00Z',
	},
]

export const mockLog = (data: TLog[] = mockLogData()): Log[] => data.map(item => new Log(item))
