# SimVector Module for phpVMS v7

The official module to integrate the SimVector data service with your phpVMS v7 installation. This module enables the
**Direct Delivery™** feature, allowing you to import flight schedules directly into your virtual airline's 
database without manual file uploads.

## Requirements

*   phpVMS v7.0.4 or later.
*   A SimVector account with an active **Plus Plan** subscription to use the Direct Delivery™ feature.

## Installation

Please follow these steps carefully to ensure the module is installed correctly. If you're familiar with installing
modules in phpVMS v7, you can skip this section.

### Step 1: Download and Upload the Module

1.  Download the latest release of the module (the `.zip` file).
2.  Unzip the file in your hosting/production environment. You should see a folder named `SimVector` containing the module files.
    **➡️ IMPORTANT:** The folder name **must be exactly** `SimVector`. If your unzipped folder has a different name (e.g., `SimVector-main` or `SimVector-v1.0`), please rename it to `SimVector` before uploading.

    The final path on your server should look like this:
    ```
    /path/to/your/phpvms/modules/SimVector/
    ```

### Step 2: Enable the Module in the Admin Center

1.  Log in to your phpVMS v7 Admin Center.
2.  Navigate to the **Add-ons** page from the sidebar menu.
3.  Find "SimVector" in the list of available modules and click the **Enable** button.

### Step 3: Clear the Application Cache

1.  After enabling the module, go to the **Maintenance** page in your Admin Center (usually found under "Tools" or "Settings").
2.  Click the **"Clear Application Cache"** button.

Installation is now complete!

## Support

If you encounter any issues or have questions, please visit our website at [https://simvector.net/](https://simvector.net/) or join our official Discord server
at [https://cardinalhorizon.com/discord](https://cardinalhorizon.com/discord) for assistance.

---
© 2025 Cardinal Horizon. All rights reserved.