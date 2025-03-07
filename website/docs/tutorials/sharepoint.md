---
sidebar_position: 1
title: SharePoint Integration
description: Learn how to integrate SharePoint with OpenConnector for WOO document publication
tags:
    - sharepoint
    - woo
    - tutorial
    - integration
---

# SharePoint Integration with OpenConnector

SharePoint is Microsoft's document management and collaboration platform that allows organizations to store, organize, share, and access information securely. Many organizations, especially government institutions, use SharePoint to manage their internal documents and workflows.

## Why Connect to SharePoint?

In the context of Open Data and specifically the Dutch WOO (Wet Open Overheid - Open Government Act), organizations need to make certain documents publicly accessible. However, these documents often reside in internal SharePoint environments. OpenConnector provides a seamless solution to bridge this gap.

## Prerequisites
- A Microsoft SharePoint instance managed with Microsoft Entra.
- A Nextcloud instance with OpenConnector installed.
- A valid X.509 certificate and the private key.
- A Linux command line (or Git Bash on Windows) for generating values later in the process.

## Example: WOO Documents Publication Flow

OpenConnector automates the publication of WOO documents from SharePoint to Open Catalogi through the following steps:

1. **Configuring SharePoint Access (Microsoft Entra Setup)**
	- Register an application in Microsoft Entra.
	- Assign API permissions for SharePoint.
	- Upload and configure certificate-based authentication.

2. **Establishing a Connection in OpenConnector**
	- Set up SharePoint as a source in OpenConnector.
	- Define authentication settings and API endpoints.
	- Validate connection through test requests.

3. **Navigating the Folder Structure in SharePoint**
	- OpenConnector scans designated SharePoint folders for WOO documents.
	- Reads metadata and identifies publication-ready content.

4. **Publishing to Open Register**
	- Maps SharePoint metadata to Open Register properties.
	- Assigns WOO classifications, publication dates, and visibility settings.

5. **Synchronizing Documents**
	- Automatically syncs SharePoint documents with OpenCatalogi.
	- Maintains version history and updates files upon changes.

This automated workflow ensures consistent, secure, and compliant publication of WOO documents.

## Configuring SharePoint Access (Microsoft Entra Setup)

### Configuring Microsoft Entra

#### Step 1: Register an Application
1. Navigate to **Identity → Applications → App registrations**.
2. Create a new application and assign a name.

   ![Entra App Registration](images/Entra_AppRegistration.png)

#### Step 2: Assign SharePoint API Permissions
1. Under the **API Permissions** tab, click ‘Add permissions’.

   ![Entra Add Permission 1](images/Entra_AddPermission_1.png)

   ![Entra Add Permission 2](images/Entra_AddPermission_2.png)

2. Click **Delegated permissions** and enable:
	- `AllSites.Read`

   ![Entra Add Permission 3](images/Entra_AddPermission_3.png)

3. Select **SharePoint → Application permissions** and enable:
	- `Sites.Read.All`

   ![Entra Add Permission 4](images/Entra_AddPermission_4.png)

4. Click **Grant admin consent** to finalize.

   ![Entra Grant Consent](images/Entra_GrantConsent.png)

#### Step 3: Upload Certificate for Authentication
1. Go to the **Certificates & secrets** tab.
2. Under **Certificates**, click **Upload certificate**.
3. Upload the public certificate (`.crt` or `.pem`).

   ![Entra Upload Certificate](images/Entra_UploadCertificate.png)

4. Save the certificate thumbprint for later use.

   ![Entra Certificate Thumbprint](images/Entra_CertificateThumbprint.png)

### Configuring a SharePoint Source in OpenConnector

Next, we need to set up the source in OpenConnector.

#### Step 1: Add a Source in OpenConnector
1. Open Nextcloud and navigate to OpenConnector.
2. Click **Sources → Add source**.
3. Provide the name, description, and SharePoint URL (e.g., `https://{tenant}.sharepoint.com/_api`).
4. Set **Type** to API.

   ![Add Source](images/OpenConnector_AddSource.png)

#### Step 2: Set Up Authentication
1. Navigate to the **Authentication** tab and click **Add Authentication**.
2. Enter the following key-value pairs (italic values can be copied without editing):

| Key                   | Value                                                                                                                                                                                                                                | Note                                                                                                                        |
|-----------------------|--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|-----------------------------------------------------------------------------------------------------------------------------|
| grant_type            | *client_credentials*                                                                                                                                                                                                                 |                                                                                                                             |
| scope                 | `https://{sharepoint_url}/.default`                                                                                                                                                                                                  | See above for the default URL                                                                                               |
| authentication        | *body*                                                                                                                                                                                                                               |                                                                                                                             |
| client_id             | (The client ID of the app registration)                                                                                                                                                                                              | See [Obtain client ID and tenant ID](#obtain-client-id-and-tenant-id)                                                       |
| client_secret         | (Empty string)                                                                                                                                                                                                                       | Due to OAuth boundaries, this is a mandatory key but should be left empty                                                   |
| client_assertion_type | *urn:ietf:params:oauth:client-assertion-type:jwt-bearer*                                                                                                                                                                             |                                                                                                                             |
| private_key           | (Base64 encoded private key of the certificate)                                                                                                                                                                                      | Highly recommended to use a secret manager for storing the private key                                                      |
| x5t                   | (SHA1 thumbprint of the certificate)                                                                                                                                                                                                 | See [Obtain x5t value](#obtain-x5t-value)                                                                                   |
| payload               | `{"aud": "{tokenUrl}","exp": {{ 'now'\|date_modify('+15 minutes')\|date('U') }},"iss": "{client_id}","jti":"dfdaa67d-d76e-48c4-a349-58861983869e","nbf": {{ 'now'\|date('U') }},"sub": "{client_id}","iat": {{ 'now'\|date('U') }}}` | Replace `{tokenUrl}` with the same value as ‘tokenUrl’ (see below) and `{client_id}` with the client_id (see above)         |
| tokenUrl              | `https://login.microsoftonline.com/{tenant_id}/oauth2/v2.0/token`                                                                                                                                                                    | Replace `{tenant_id}` with your Microsoft Tenant ID (see [Obtain client ID and tenant ID](#obtain-client-id-and-tenant-id)) |

##### <h5 style="font-size: 1.1em; color: gray; border-bottom: 2px solid black; padding-bottom: 5px;">Obtain client ID and tenant ID</h5>
As seen in the table above in step 2 ([Set Up Authentication](#step-2-set-up-authentication)), there are two locations where the client ID has to be inserted into the source, and likewise two locations where the Tenant ID must be inserted (both times in the token URL).

The client ID can be obtained from the Microsoft Entra dashboard by clicking on ‘Overview’ in the App registration, and copying the value ‘Application (client) ID’ in essentials.

The Tenant ID can be found in the same list, as ‘Directory (tenant) ID’.

![Entra_Client_Id](images/Entra_ClientId.png)

##### <h5 style="font-size: 1.1em; color: gray; border-bottom: 2px solid black; padding-bottom: 5px;">Obtain x5t value</h5>
The x5t value is an encoded version of the SHA1 thumbprint we obtained in the [Upload Certificate for Authentication](#step-3-upload-certificate-for-authentication) section.
To encode this value we can use the command line.

In a Linux (or Git Bash) command line, use the following command:

```bash
echo '{thumbprint}' | xxd -r -p | base64
```

The resulting string can be entered into the x5t value.

#### Step 3: Configure Headers
Now that we have the authentication values, we can set the configuration fields.
1. Click the tab ‘Configurations’.
2. In the Actions menu select ‘add configuration’ & add these two:
	- `headers.Authorization` → `Bearer {{ oauthToken(source) }}`
	- `headers.Accept` → `application/json;odata=verbose`

![Configurations](images/OpenConnector_Configurations.png)

#### Step 4: Test the Source
Now we should be done setting up the source.
1. Click **Test** in the Actions menu.
2. Enter the endpoint `/Web/lists` and press 'Test connection'.
3. A successful response (Status: OK (200)), the source is configured correctly.

## Configuring SharePoint for WOO Document Storage

SharePoint can be used as a source for publishing Woo requests and decisions. For this to work SharePoint must be structured appropriately.

### Creating a SharePoint Site

In order to store documents and acces them with api calls you first need to create a site in SharePoint. Follow these steps to create your SharePoint site:

1. **Access SharePoint**: Sign in to your Microsoft 365 account, click the App Launcher (grid icon) in the top-left corner, and select "SharePoint."
2. **Initiate Site Creation**: On the SharePoint homepage, click "+ Create site."
3. **Select a Template**: Choose a Communication Site with a standard template.
4. **Configure Site Details & Finalize Creation**: Enter a name, a description and select a language and click on finish.

Now you have configured a SharePoint site where you can add and manage documents.

![Create Site](images/SharePoint_CreateSite.png)

### Structure of the Publication Folders

Each publication is represented by a separate folder within the main publication directory. All associated documents and attachments are stored within this folder.

Example API endpoint for retrieving publications: `/_api/Web/GetFolderByServerRelativePath(decodedurl='/WOO/Woo-verzoeken en -besluiten')/folders`

It is recommended to store all publications in one central folder. (you dont have to make separate folders for each Woo category as in the example, just put them all in one big folder)

**Folder structure:**

![Category Folder Structure](images/overzicht-categorie-folders.png)

Each folder within the parent directory represents a single publication:

![Publication Example](images/overzicht-woo-verzoeken.png)

Where within you can store the documents that belong to this publication.

![Voorbeeld publicatie](./images/voorbeeld-publicatie.png)

### Configuring Metadata in SharePoint

To ensure compliance with [OpenWOO](https://openwoo.app/Techniek/Configuratie/), metadata fields must be configured in SharePoint.

1. Open settings.

   ![Go to settings](./images/go-to-settings.png)

2. Navigate to **Library settings** → **More library settings**.

   ![Go to library settings](./images/go-to-library-settings.png)
   ![More library settings](./images/more-library-settings.png)

3. Navigate to **Enterprise Metadata and Keywords settings**.

   ![Go to enterprise settings](./images/go-to-enterprise-settings.png)

4. Enable the Enterprise Keywords option and press OK.

	![Turn on metadata](images/turn-on-metadata.png)

5. Create a column for each configuration field from [OpenWOO](https://openwoo.app/Techniek/Configuratie/).

   ![Create column](./images/create-column.png)

6. Here you can configure the columns, make sure to enable default view.

   ![Column config](./images/column-config.png)

#### Example of minimal configuration.
![Minimum columns](./images/minimum-columns.png)

#### Example of an extended configuration including all optional fields.
![All columns](./images/all-columns.png)

### Modifying publication metadata
To update the metadata of a publication:
1. Open the document library and locate the publication folder.
2. Click **Grid edit** to modify metadata.
3. Save changes by clicking **Exit grid** in the upper-left corner.

![Edit Grid](images/edit-grid.png)

## Configuring a SharePoint Synchronization in OpenConnector

Now we can set up the Synchronization in OpenConnector.

### Step 1: Add Synchronization Mapping in OpenConnector

Go to [this link](https://github.com/ConductionNL/openconnector/blob/main/configurations/sharepoint-woo/mappings/sharepoint-woo-verzoek-to-publications.json) and download the file.
![Download mapping](images/download-mapping.png)

Go to the mappings page. 
![Go to mappings page](images/go-to-mappings-page.png)

Upload the downloaded file.
![Small add mapping menu](images/small-add-mapping-menu.png)

Choose the just download mapping file here and press import.
![Add file](images/add-file.png)

Make sure to add or edit the publicationType and catalog keys to the rights id's from OpenCatalogi
![Edit catalog and publicationType](images/edit-catalog-and-publication-type.png)

### Step 2: Add Synchronization config in OpenConnector

Go to [this link](https://github.com/ConductionNL/openconnector/blob/main/configurations/sharepoint-woo/synchronizations/sharepoint-publications.json) and download the file.
![Download synchronization](images/download-synchronization.png)

Go to the synchronizations page and choose the import option.
![Import sync menu](images/import-sync-menu.png)

Import the just downloaded file.
![import-synchronization](images/import-synchronization.png)

Check in the sourceConfig tab of the just added synchronization if the following configuration is added:
| Key                                                   | Value                                                              |
|-------------------------------------------------------|--------------------------------------------------------------------|
| idPosition                                           | UniqueId                                                          |
| resultsPosition                                      | d.results                                                         |
| endpoint                                            | /Web/GetFolderByServerRelativePath(decodedurl='/WOO/Publications')/folders |
| extraDataConfigs.0.dynamicEndpointLocation         | Properties.__deferred.uri                                         |
| extraDataConfigs.0.mergeExtraData                   | true                                                              |
| extraDataConfigs.1.dynamicEndpointLocation         | Files.__deferred.uri                                              |
| extraDataConfigs.1.mergeExtraData                   | true                                                              |
| extraDataConfigs.1.keyToSetExtraData               | fileUrls                                                          |
| extraDataConfigs.1.resultsLocation                 | d.results                                                         |
| extraDataConfigs.1.extraDataConfigPerResult.dynamicEndpointLocation | Properties.__deferred.uri                          |
| extraDataConfigs.1.extraDataConfigPerResult.mergeExtraData | true                                              |
| usesPagination                                      | false                                                             |

![Source config](images/source-config.png)



### Step 3: Add Synchronization file rule 

Download this file so we can upload the configuration.
![Download rule](images/download-rule.png)

Import the just downloaded rule file
![Import rule](images/import-rule.png)

Make sure the auto share is enabled so the publication documents are publically available
![Enable share url](images/enable-share-url.png)

### Step 4: Check and correct the synchronization

View the sharepoint synchronization we created earlier and check if we attached all other Sharepoint config objects.
If some are not connected to sharepoint objects you can correct them.
![Check synchronization](images/check-synchronization.png)

Then we can run the synchronization
![Test synchronization](images/test-synchronization.png)

You will see a result table with how many successful objects were synchronized. If you see a error some config might still be wrong or there could be something else going on.