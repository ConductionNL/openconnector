import { Webhook } from './webhook'
import { TWebhook } from './webhook.types'

export const mockWebhookData = (): TWebhook[] => [
	{
		id: 1,
		name: 'Payment Notification',
		description: 'Receives payment notifications from payment gateway',
		version: '1.0.0',
		url: 'https://api.example.com/payment-webhook',
		isEnabled: true,
		dateCreated: '',
		dateModified: '',
		headers: undefined,
		events: [],
		retryPolicy: undefined,
		timeout: 0,
		lastTriggered: '',
		lastResponse: {
			status: 0,
			body: '',
		},
		secretKey: '',
		payloadFormat: 'json',
		active: false,
	},
	{
		id: 2,
		name: 'Inventory Update',
		description: 'Receives inventory updates from supplier',
		version: '1.1.0',
		url: 'https://api.example.com/inventory-webhook',
		isEnabled: true,
		dateCreated: '',
		dateModified: '',
		headers: undefined,
		events: [],
		retryPolicy: undefined,
		timeout: 0,
		lastTriggered: '',
		lastResponse: {
			status: 0,
			body: '',
		},
		secretKey: '',
		payloadFormat: 'json',
		active: false,
	},
]

export const mockWebhook = (data: TWebhook[] = mockWebhookData()): TWebhook[] => data.map(item => new Webhook(item))
