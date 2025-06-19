/**
 * Get job logs with pagination
 *
 * @param int $jobId The ID of the job
 * @return DataResponse The paginated job logs
 */
public function index(int $jobId): DataResponse
{
    try {
        // Get pagination parameters from request
        $page = (int) ($this->request->getParam('page') ?? 1);
        $limit = (int) ($this->request->getParam('limit') ?? 10);

        // Validate pagination parameters
        if ($page < 1) {
            $page = 1;
        }
        if ($limit < 1 || $limit > 100) {
            $limit = 10;
        }

        // Get paginated logs
        $result = $this->jobService->getJobLogs($jobId, $page, $limit);

        return new DataResponse([
            'data' => $result['data'],
            'pagination' => [
                'total' => $result['total'],
                'pages' => $result['pages'],
                'currentPage' => $page,
                'perPage' => $limit
            ]
        ]);
    } catch (\Exception $e) {
        return new DataResponse(['error' => $e->getMessage()], Http::STATUS_BAD_REQUEST);
    }
} 