import { Log } from './log'
import { mockLogData } from './log.mock'

describe('Log Entity', () => {
	it('create Log entity with full data', () => {
		const log = new Log(mockLogData()[0])

		expect(log).toBeInstanceOf(Log)
		expect(log.id).toBe(1)
		expect(log.uuid).toBe('5137a1e5-b54d-43ad-abd1-4b5bff5fcd3f')
		expect(log.message).toBe('Synchronization completed successfully')
		expect(log.synchronizationId).toBe('sync-123')

		expect(log.validate().success).toBe(true)
	})

	it('create Log entity with partial data', () => {
		const log = new Log(mockLogData()[1])

		expect(log).toBeInstanceOf(Log)
		expect(log.id).toBe(2)
		expect(log.getLevel()).toBe('error')
		expect(log.isError()).toBe(true)

		expect(log.validate().success).toBe(true)
	})

	it('should handle snake_case API fields', () => {
		const apiData = {
			id: 1,
			uuid: 'test-uuid',
			message: 'Test message',
			synchronization_id: 'sync-123',
			user_id: 'user-456',
			session_id: 'session-789',
			execution_time: 1500,
			created: '2023-05-01T12:00:00Z',
		}

		const log = new Log(apiData)

		expect(log.synchronizationId).toBe('sync-123')
		expect(log.userId).toBe('user-456')
		expect(log.sessionId).toBe('session-789')
		expect(log.executionTime).toBe(1500)
	})

	it('should generate correct display name', () => {
		const log1 = new Log({ id: 1, message: 'Short message' })
		expect(log1.getDisplayName()).toBe('Short message')

		const log2 = new Log({ id: 2, message: 'This is a very long message that should be truncated because it exceeds the limit' })
		expect(log2.getDisplayName()).toBe('This is a very long message that should be truncat...')

		const log3 = new Log({ id: 3, uuid: 'test-uuid-123' })
		expect(log3.getDisplayName()).toBe('Log test-uui')

		const log4 = new Log({ id: 4 })
		expect(log4.getDisplayName()).toBe('Log 4')
	})

	it('should determine correct log level', () => {
		const successLog = new Log({ id: 1, message: 'Success message', result: [{ status: 'success' }] })
		expect(successLog.getLevel()).toBe('success')

		const errorLog = new Log({ id: 2, message: 'Error occurred', result: [{ error: 'Something failed' }] })
		expect(errorLog.getLevel()).toBe('error')

		const testLog = new Log({ id: 3, message: 'Test run', test: true })
		expect(testLog.getLevel()).toBe('info')

		const warningLog = new Log({ id: 4, message: 'Warning: something might be wrong' })
		expect(warningLog.getLevel()).toBe('warning')
	})

	it('should format execution duration correctly', () => {
		const log1 = new Log({ id: 1, executionTime: 500 })
		expect(log1.getFormattedDuration()).toBe('500ms')

		const log2 = new Log({ id: 2, executionTime: 2500 })
		expect(log2.getFormattedDuration()).toBe('2.5s')

		const log3 = new Log({ id: 3, executionTime: 125000 })
		expect(log3.getFormattedDuration()).toBe('2m 5s')

		const log4 = new Log({ id: 4 })
		expect(log4.getFormattedDuration()).toBe('-')
	})
})
