# Proposal: dso-omgevingsloket

## Summary
Implement a DSO (Digitaal Stelsel Omgevingswet) adapter for OpenConnector to receive verzoeken from the Omgevingsloket and map activiteiten to zaaktypes in OpenRegister.

## Motivation
Municipalities must receive and process permit applications (verzoeken) from the national Omgevingsloket. OpenConnector needs a DSO adapter to bridge the DSO-LV API with the local case management system.

## Scope
- DSO-LV inbound endpoint (STAM) for receiving verzoeken
- Activiteiten-to-zaaktype mapping configuration
- DSO-SWF (Samenwerkfunctionaliteit) integration
- Verzoek validation and transformation
