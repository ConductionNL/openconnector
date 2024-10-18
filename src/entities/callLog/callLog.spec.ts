import { CallLog } from './callLog'
import { mockCallLog } from './callLog.mock'

describe('CallLog Entity', () => {
	it('create CallLog entity with full data', () => {
		const callLog = new CallLog(mockCallLog()[0])

		expect(callLog).toBeInstanceOf(CallLog)
		expect(callLog).toEqual(mockCallLog()[0])

		expect(callLog.validate().success).toBe(true)
	})

	it('create CallLog entity with partial data', () => {
		const callLog = new CallLog(mockCallLog()[1])

		expect(callLog).toBeInstanceOf(CallLog)
		expect(callLog).toEqual(mockCallLog()[1])

		expect(callLog.validate().success).toBe(true)
	})
})
