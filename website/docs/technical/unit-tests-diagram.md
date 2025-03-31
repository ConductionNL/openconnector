# Test Architecture Diagrams

## Test Structure

This diagram shows the structure of our test suite and how it corresponds to the application code:

```mermaid
graph TD
    subgraph "Test Directory Structure"
    T[tests/]
    TU[unit/]
    TS[Service/]
    TSS[SynchronizationServiceTest.php]
    TSO[SynchronizationObjectProcessorTest.php]
    TT[TargetHandler/]
    TTR[TargetHandlerRegistryTest.php]
    TTA[ApiHandlerTest.php]
    
    T --> TU
    TU --> TS
    TS --> TSS
    TS --> TSO
    TU --> TT
    TT --> TTR
    TT --> TTA
    end
    
    subgraph "App Directory Structure"
    L[lib/]
    LS[Service/]
    LSS[SynchronizationService.php]
    LSO[SynchronizationObjectProcessor.php]
    LT[TargetHandler/]
    LTR[TargetHandlerRegistry.php]
    LTA[ApiHandler.php]
    LTI[TargetHandlerInterface.php]
    LTAH[AbstractTargetHandler.php]
    
    L --> LS
    LS --> LSS
    LS --> LSO
    L --> LT
    LT --> LTR
    LT --> LTA
    LT --> LTI
    LT --> LTAH
    end
    
    %% Relationships between tests and app code
    TSS -.tests.-> LSS
    TSO -.tests.-> LSO
    TTR -.tests.-> LTR
    TTA -.tests.-> LTA
```

## Test Dependencies

This diagram illustrates the dependencies of our tests and the challenges with Nextcloud classes:

```mermaid
graph TD
    subgraph "Test Classes"
    TSS[SynchronizationServiceTest]
    TSO[SynchronizationObjectProcessorTest]
    TTR[TargetHandlerRegistryTest]
    TTA[ApiHandlerTest]
    ST[SimpleTest]
    end
    
    subgraph "Framework Dependencies"
    NC[Nextcloud Classes]
    QBM[QBMapper]
    E[Entity]
    P[PHPUnit]
    end
    
    subgraph "App Classes"
    SS[SynchronizationService]
    SO[SynchronizationObjectProcessor]
    TR[TargetHandlerRegistry]
    TA[ApiHandler]
    end
    
    %% Test dependencies
    TSS --> SS
    TSO --> SO
    TTR --> TR
    TTA --> TA
    ST --> P
    
    %% App dependencies
    SS --> QBM
    SO --> QBM
    SS --> E
    SO --> E
    
    %% Framework relationships
    QBM --> NC
    E --> NC
    
    %% Highlighting the problem
    NC -.missing in standalone tests.-> TSS
    NC -.missing in standalone tests.-> TSO
    NC -.missing in standalone tests.-> TTR
    NC -.missing in standalone tests.-> TTA
    
    %% Simple test works
    ST -.works without Nextcloud.-> P
    
    style NC fill:#f77,stroke:#333,stroke-width:2px
    style ST fill:#7f7,stroke:#333,stroke-width:2px
```

## Test Execution Flow

This diagram shows the flow of test execution:

```mermaid
sequenceDiagram
    participant PHPUnit
    participant Bootstrap
    participant Test
    participant SUT as System Under Test
    participant Mock
    
    PHPUnit->>Bootstrap: Load environment
    Bootstrap->>PHPUnit: Environment ready
    PHPUnit->>Test: Run test
    Test->>Mock: Create mocks
    Mock-->>Test: Mocks created
    Test->>SUT: Create instance with mocks
    SUT-->>Test: Instance created
    Test->>SUT: Call method
    SUT->>Mock: Use mock dependencies
    Mock-->>SUT: Return mock data
    SUT-->>Test: Return result
    Test->>Test: Assert result
    Test-->>PHPUnit: Test result
```

## Solution Approaches

This diagram illustrates two possible approaches to solve the dependency issues:

```mermaid
graph TD
    subgraph "Approach 1: Mock Nextcloud Classes"
    M1[Create Mock QBMapper]
    M2[Create Mock Entity]
    M3[Update bootstrap.php]
    M4[Add class_exists checks]
    
    M1 --> M3
    M2 --> M3
    M3 --> M4
    end
    
    subgraph "Approach 2: Docker Test Environment"
    D1[Use Nextcloud Docker Container]
    D2[Mount Test Directory]
    D3[Run PHPUnit in Container]
    D4[Set up CI/CD Pipeline]
    
    D1 --> D2
    D2 --> D3
    D3 --> D4
    end
    
    Problem[Missing Nextcloud Dependencies]
    Solution[Working Test Suite]
    
    Problem --> Approach1
    Problem --> Approach2
    Approach1 --> Solution
    Approach2 --> Solution
    
    Approach1[Approach 1]
    Approach2[Approach 2]
``` 