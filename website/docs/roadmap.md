# OpenConnector Roadmap

This document outlines the planned development roadmap for OpenConnector. Our immediate focus is on expanding file-based data source support to enable more flexible data integration scenarios.

## Current Development Focus

### File-Based Data Sources (Q1 2024)
Our primary focus is adding support for common file formats as data sources:

- Excel (.xlsx, .xls) file support
  - Read capabilities for workbooks and worksheets
  - Column mapping and data type detection
  - Support for multiple sheets
  - Handling of formatted cells and formulas

- CSV file integration
  - Configurable delimiter support
  - Character encoding detection
  - Header row handling
  - Data type inference
  - Support for escaped characters

- YAML file processing
  - Full YAML 1.2 specification support
  - Complex data structure handling
  - Multi-document support
  - Aliasing and anchoring features

These file-based connectors will include:
- Automated file watching for changes
- Scheduled import capabilities
- Data validation and transformation
- Error handling and reporting
- Support for large file processing

## Future Considerations

While we are currently focused on file-based data sources, future roadmap items will be added based on community feedback and business needs. We encourage users to submit feature requests and use cases through our GitHub repository.

## Contributing

We welcome contributions to help implement these roadmap items! Please visit our [GitHub repository](https://github.com/conductionnl/openconnector) to:

- Discuss implementation approaches
- Submit pull requests
- Report bugs or issues
- Suggest new roadmap items

For contribution guidelines, please refer to CONTRIBUTING.md in our repository.
