<?php

return [
	'resources' => [
		'Endpoints' => ['url' => 'api/endpoints'],
		'Sources' => ['url' => 'api/sources'],
		'Mappings' => ['url' => 'api/mappings'],
		'Jobs' => ['url' => 'api/jobs'],
		'Synchronizations' => ['url' => 'api/synchronizations'],
		'Consumers' => ['url' => 'api/consumers'],
		'Rules' => ['url' => 'api/rules'],
		'Events' => ['url' => 'api/events'],
		'Logs' => ['url' => 'api/logs'],
		'SynchronizationContracts' => ['url' => 'api/synchronization-contracts'],
	],
	'routes' => [
		['name' => 'dashboard#page', 'url' => '/', 'verb' => 'GET'],
		['name' => 'dashboard#index', 'url' => '/api/dashboard', 'verb' => 'GET'],
		['name' => 'dashboard#getCallStats', 'url' => '/api/dashboard/callstats', 'verb' => 'GET'],
		['name' => 'dashboard#getJobStats', 'url' => '/api/dashboard/jobstats', 'verb' => 'GET'],
		['name' => 'dashboard#getSyncStats', 'url' => '/api/dashboard/syncstats', 'verb' => 'GET'],
		['name' => 'sources#test', 'url' => '/api/source-test/{id}', 'verb' => 'POST'],
		['name' => 'sources#logs', 'url' => '/api/sources-logs/{id}', 'verb' => 'GET'],
		['name' => 'jobs#run', 'url' => '/api/jobs-test/{id}', 'verb' => 'POST'],
		['name' => 'jobs#logs', 'url' => '/api/jobs-logs/{id}', 'verb' => 'GET'],
		['name' => 'endpoints#test', 'url' => '/api/endpoints-test/{id}', 'verb' => 'POST'],
		['name' => 'endpoints#logs', 'url' => '/api/endpoints-logs/{id}', 'verb' => 'GET'],

		// Synchronization endpoints
		['name' => 'synchronizations#contracts', 'url' => '/api/synchronizations-contracts/{id}', 'verb' => 'GET'],
		['name' => 'synchronizations#logs', 'url' => '/api/synchronizations-logs/{uuid}', 'verb' => 'GET'],
		['name' => 'synchronizations#test', 'url' => '/api/synchronizations-test/{id}', 'verb' => 'POST'],
		['name' => 'synchronizations#run', 'url' => '/api/synchronizations-run/{id}', 'verb' => 'POST'],

		// Mapping endpoints
		['name' => 'mappings#test', 'url' => '/api/mappings/test', 'verb' => 'POST'],
		['name' => 'mappings#saveObject', 'url' => '/api/mappings/objects', 'verb' => 'POST'],
		['name' => 'mappings#getObjects', 'url' => '/api/mappings/objects', 'verb' => 'GET'],

		// Running endpoints - allow any path after /api/endpoints/
        ['name' => 'endpoints#preflighted_cors', 'url' => '/api/endpoint/{_path}', 'verb' => 'OPTIONS', 'requirements' => ['path' => '.+']],
		['name' => 'endpoints#handlePath', 'postfix' => 'read', 'url' => '/api/endpoint/{_path}', 'verb' => 'GET', 'requirements' => ['_path' => '.+']],
		['name' => 'endpoints#handlePath', 'postfix' => 'update', 'url' => '/api/endpoint/{_path}', 'verb' => 'PUT', 'requirements' => ['_path' => '.+']],
		['name' => 'endpoints#handlePath', 'postfix' => 'partialupdate', 'url' => '/api/endpoint/{_path}', 'verb' => 'PATCH', 'requirements' => ['_path' => '.+']],
		['name' => 'endpoints#handlePath', 'postfix' => 'create', 'url' => '/api/endpoint/{_path}', 'verb' => 'POST', 'requirements' => ['_path' => '.+']],
		['name' => 'endpoints#handlePath', 'postfix' => 'destroy', 'url' => '/api/endpoint/{_path}', 'verb' => 'DELETE', 'requirements' => ['_path' => '.+']],

		// Import & Export
		['name' => 'import#import', 'url' => '/api/import', 'verb' => 'POST'],
		['name' => 'export#export', 'url' => '/api/export/{type}/{id}', 'verb' => 'GET'],

		// Event messages
		['name' => 'events#messages', 'url' => '/api/events/{id}/messages', 'verb' => 'GET'],

		// Subscription management
		['name' => 'events#subscriptions', 'url' => '/api/events/subscriptions', 'verb' => 'GET'],
		['name' => 'events#subscriptionMessages', 'url' => '/api/events/subscriptions/{subscriptionId}/messages', 'verb' => 'GET'],
		['name' => 'events#subscribe', 'url' => '/api/events/subscriptions', 'verb' => 'POST'],
		['name' => 'events#updateSubscription', 'url' => '/api/events/subscriptions/{subscriptionId}', 'verb' => 'PUT'],
		['name' => 'events#unsubscribe', 'url' => '/api/events/subscriptions/{subscriptionId}', 'verb' => 'DELETE'],

		// Pull-based delivery
		['name' => 'events#pull', 'url' => '/api/events/subscriptions/{subscriptionId}/pull', 'verb' => 'GET'],

		// Logs endpoints
		['name' => 'logs#statistics', 'url' => '/api/logs/statistics', 'verb' => 'GET'],
		['name' => 'logs#export', 'url' => '/api/logs/export', 'verb' => 'GET'],

		// Synchronization Contracts endpoints  
		['name' => 'synchronizationContracts#statistics', 'url' => '/api/synchronization-contracts/statistics', 'verb' => 'GET'],
		['name' => 'synchronizationContracts#performance', 'url' => '/api/synchronization-contracts/performance', 'verb' => 'GET'],
		['name' => 'synchronizationContracts#export', 'url' => '/api/synchronization-contracts/export', 'verb' => 'GET'],
		['name' => 'synchronizationContracts#activate', 'url' => '/api/synchronization-contracts/{id}/activate', 'verb' => 'POST'],
		['name' => 'synchronizationContracts#deactivate', 'url' => '/api/synchronization-contracts/{id}/deactivate', 'verb' => 'POST'],
		['name' => 'synchronizationContracts#execute', 'url' => '/api/synchronization-contracts/{id}/execute', 'verb' => 'POST'],
	],
];
