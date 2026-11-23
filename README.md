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
## Schedule Cloud / Live Flights

The **SimVector Flights** page (`/simvector/flights`) browses Schedule Cloud data from the SimVector API
(`https://api.simvector.net/v1`, overridable with `SV_API_URL`; legacy `.../api` values are normalised to `.../v1`).
The community API key (Admin → SimVector) is sent server-side in the `x-api-key` header, so it never reaches the browser.

**Browsing**
- Airline cards → departure-airport cards (with "Flies from"/"You are here" based on the pilot's current/home airport).
- From/To pickers limited to routes that actually exist, with swap, popular-destination and recent-pair chips.
- Operator filter, sortable results, infinite scroll, and a multi-leg trip bar (bids placed are chained, with connection warnings).
- Shallow search first; for a full airport pair a deep search runs behind it (server-throttled, costs credits).

**Module API** (all behind phpVMS `x-api-key` auth, prefix `/api/simvector`)

| Endpoint | Purpose |
|---|---|
| `GET /operators` | Operators selected by the community |
| `GET /routes` | Static airports + routes index (supports `ETag` / `If-None-Match`, `operators`, `departure_icao`, `arrival_icao`) |
| `GET /flights` | Search: `departure_icao`, `arrival_icao`, `operators`, `depth=shallow\|deep`, `limit`, `cursor`; returns `{data, meta, now}` |
| `POST /bids`, `DELETE /bids` | Add / remove a bid (creates the local `SV_*` flight) |
| `GET /token` | Exchange the VA key for a SimVector JWT (used by the smartCARS Flight Center plugin) |

Errors are returned as `{error: {code, message}}` with the upstream status (e.g. 402 `subscription_required`, 422 `invalid_cursor`).

**Building the frontend** (Laravel Mix 6; use Node 22 or older — Node 25/26 break Mix's `yargs`):

```bash
cd modules/SimVector && npm install && npm run prod
```
