import { Synchronization } from './synchronization'
import { TSynchronization } from './synchronization.types'

export const mockSynchronizationData = (): TSynchronization[] => [
	{
		id: 1,
		name: 'Synchronization 1',
		description: 'Synchronization 1',
		sourceId: 'source1',
		sourceType: 'api',
		sourceHash: 'source1',
		sourceTargetMapping: 'source1',
		sourceConfig: {},
		sourceLastChanged: '2023-05-01T12:00:00Z',
		sourceLastChecked: '2023-05-01T12:30:00Z',
		sourceLastSynced: '2023-05-01T12:45:00Z',
		targetId: 'target1',
		targetType: 'api',
		targetHash: 'target1',
		targetSourceMapping: 'target1',
		targetConfig: {},
		targetLastChanged: '2023-05-01T13:00:00Z',
		targetLastChecked: '2023-05-01T13:30:00Z',
		targetLastSynced: '2023-05-01T13:45:00Z',
		created: '2023-05-01T11:00:00Z',
		updated: '2023-05-01T14:00:00Z',
	},
	{
		id: 2,
		name: 'Synchronization 2',
		description: 'Synchronization 2',
		sourceId: 'source2',
		sourceType: 'api',
		sourceHash: 'source2',
		sourceTargetMapping: 'source2',
		sourceConfig: {},
		sourceLastChanged: '2023-05-02T12:00:00Z',
		sourceLastChecked: '2023-05-02T12:30:00Z',
		sourceLastSynced: '2023-05-02T12:45:00Z',
		targetId: 'target2',
		targetType: 'api',
		targetHash: 'target2',
		targetSourceMapping: '',
		targetConfig: {},
		targetLastChanged: '2023-05-02T13:00:00Z',
		targetLastChecked: '2023-05-02T13:30:00Z',
		targetLastSynced: '2023-05-02T13:45:00Z',
		created: '2023-05-02T11:00:00Z',
		updated: '2023-05-02T14:00:00Z',
	},
]

export const mockSynchronization = (data: TSynchronization[] = mockSynchronizationData()): TSynchronization[] => data.map(item => new Synchronization(item))
