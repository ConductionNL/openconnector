<?php

// CI-specific bootstrap that uses the original bootstrap approach
// but focuses on non-Db tests to avoid MockMapper signature issues

// Use the original bootstrap which has all the necessary mocks
require_once __DIR__ . '/bootstrap.php';
