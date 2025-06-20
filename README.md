# OpenConector

Provides gateway and service bus functionality like mapping, translation and synchronisation of data

## Documentation

Documentation is available at [https://conduction.nl/openconnector](https://conduction.nl/openconnector) and created from the website folder of this repository.

## Requirements

- Nextcloud 25 or higher
- PHP 8.1 or higher
- Database: MySQL/MariaDB


## Installation

[Installation instructions](https://conduction.nl/openconnector/installation)

## Support

[Support information](https://conduction.nl/openconnector/support)

## Project Structure

This monorepo is a Nextcloud app, it is based on the following structure:

    /
    ├── app/          # App initialization and bootstrap files
    ├── appinfo/      # Nextcloud app metadata and configuration
    ├── css/          # Stylesheets for the app interface
    ├── docker/       # Docker configuration for development
    ├── img/          # App icons and images
    ├── js/           # JavaScript files for frontend functionality
    ├── lib/          # PHP library files containing core business logic
    ├── src/          # Vue.js frontend application source code
    ├── templates/    # Template files for rendering app views
    └── website/      # Documentation website source files

When running locally, or in development mode the folders nodus_modules and vendor are added. Thes shoudl however not be commited.

## Contributing

Please see our [Contributing Guide](CONTRIBUTING.md) for details on how to contribute to this project.

## License

This project is licensed under the EUPL License - see the [LICENSE](LICENSE) file for details.

## Contact

For more information, please contact [info@conduction.nl](mailto:info@conduction.nl).
