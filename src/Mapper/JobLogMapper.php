/**
 * Find job logs by job ID with pagination
 *
 * @param int $jobId The ID of the job
 * @param int $limit The number of items per page
 * @param int $offset The offset for pagination
 * @return array<int, array<string, mixed>> The job logs
 */
public function findByJobId(int $jobId, int $limit = 10, int $offset = 0): array
{
    $sql = 'SELECT * FROM `*PREFIX*openconnector_job_logs` 
            WHERE `job_id` = ? 
            ORDER BY `created_at` DESC 
            LIMIT ? OFFSET ?';
    
    return $this->db->executeQuery($sql, [$jobId, $limit, $offset])->fetchAll();
}

/**
 * Count total number of job logs for a job
 *
 * @param int $jobId The ID of the job
 * @return int The total number of logs
 */
public function countByJobId(int $jobId): int
{
    $sql = 'SELECT COUNT(*) FROM `*PREFIX*openconnector_job_logs` WHERE `job_id` = ?';
    return (int) $this->db->executeQuery($sql, [$jobId])->fetchOne();
} 