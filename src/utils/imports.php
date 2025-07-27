<?php
require_once  'src/utils/ApiResourceBase.php';
require_once 'src/classes/Model.php';
require_once 'src/database/connection.php';

// Common Classes
require_once 'src/classes/Role.php';
require_once 'src/classes/User.php';
require_once 'src/classes/Chdm.php';

// Himali Branch Classes
require_once 'src/classes/Installation.php';
require_once 'src/classes/Qc_Reporting.php';
require_once 'src/classes/service.php';

// Dev Branch Classes
require_once 'src/classes/Inventory_Item.php';
require_once 'src/classes/Utilities.php';
require_once 'src/classes/Stock.php';
require_once 'src/classes/Teller_Scanner.php';
require_once 'src/classes/Routine.php';
require_once 'src/classes/Repair.php';
require_once 'src/classes/Branch.php';
require_once 'src/classes/Client.php';
require_once 'src/classes/ClusterTechnician.php';

// Common APIs
require_once 'src/api/auth/roleApi.php';
require_once 'src/api/auth/userApi.php';
require_once 'src/api/auth/profileApi.php';

// Himali Branch APIs
require_once 'src/api/Installation/InstallationApi.php';
require_once 'src/api/Qc_Reporting/Qc_ReportingApi.php';
require_once 'src/api/Service_Reporting/Service_ReportingApi.php';

// Dev Branch APIs
require_once 'src/api/routine/routineApi.php';
require_once 'src/api/branch/branchApi.php';
require_once 'src/api/repair/repairApi.php';
require_once 'src/api/Inventory_Item/Inventory_ItemApi.php';
require_once 'src/api/Utilities/UtilitiesApi.php';
require_once 'src/api/Stock/StockApi.php';
require_once 'src/api/Teller_Scanner/Teller_ScannerApi.php';
require_once 'src/api/client/ClientApi.php';

require_once 'src/api/chdm/chdmApi.php';
require_once 'src/utils/router.php';
require_once 'src/utils/JwtHandler.php';

require_once 'vendor/autoload.php';
