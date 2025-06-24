<?php
require_once  'src/utils/ApiResourceBase.php';
require_once 'src/classes/Model.php';
require_once 'src/database/connection.php';

require_once 'src/classes/Role.php';
require_once 'src/classes/User.php';
require_once 'src/classes/Chdm.php';
require_once 'src/classes/Inventory_Item.php';
require_once 'src/classes/Utilities.php';
require_once 'src/classes/Stock.php';
require_once 'src/classes/Teller_Scanner.php';
require_once 'src/classes/Routine.php';

require_once 'src/api/auth/roleApi.php';
require_once 'src/api/auth/userApi.php';
require_once 'src/api/auth/profileApi.php';
require_once 'src/api/routine/routineApi.php';
require_once 'src/api/chdm/chdmApi.php';
require_once 'src/api/inventory_item/Inventory_ItemApi.php';
require_once 'src/api/utilities/UtilitiesApi.php';
require_once 'src/api/Stock/StockApi.php';
require_once 'src/api/Teller_Scanner/Teller_ScannerApi.php';

require_once 'src/utils/router.php';
require_once 'src/utils/JwtHandler.php';

require_once 'vendor/autoload.php'; 
