/**
 * Interface representing a Rule entity
 * Defines the structure and types for rule objects in the system
 */
export interface TRule {
    id: string;
    uuid: string;
    name: string;
    description: string;
    action: 'create' | 'read' | 'update' | 'delete';
    timing: 'before' | 'after';
    conditions: object[];
    type: 'mapping' | 'error' | 'script' | 'synchronization' | 'authentication' | 'download' | 'upload' | 'locking' | 'extend_input' | 'extend_external_input' | 'fetch_file' | 'write_file' | 'fileparts_create' | 'filepart_upload' | 'save_object' | 'javascript';
    configuration: object;
    order: number;
    created: string;
    updated: string;
}
