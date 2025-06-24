<?php
require_once  'src/utils/ApiResourceBase.php';
require_once 'src/classes/Model.php';
require_once 'src/database/connection.php';

require_once 'src/classes/Role.php';
require_once 'src/classes/User.php';
require_once 'src/classes/Chdm.php';
require_once 'src/classes/Routine.php';
require_once 'src/classes/Repair.php';
require_once 'src/classes/Branch.php';

require_once 'src/api/auth/roleApi.php';
require_once 'src/api/auth/userApi.php';
require_once 'src/api/auth/profileApi.php';
require_once 'src/api/routine/routineApi.php';
require_once 'src/api/branch/branchApi.php';
require_once 'src/api/repair/repairApi.php';
require_once 'src/utils/router.php';

require_once 'src/api/chdm/chdmApi.php';

require_once 'src/utils/JwtHandler.php';

require_once 'vendor/autoload.php'; 

