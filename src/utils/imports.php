<?php
require_once  'src/utils/ApiResourceBase.php';
require_once 'src/classes/Model.php';
require_once 'src/database/connection.php';
//add your classes her 
require_once 'src/classes/Role.php';
require_once 'src/classes/User.php';
require_once 'src/classes/Chdm.php';
require_once 'src/classes/Installation.php';
require_once 'src/classes/Qc_Reporting.php';
require_once 'src/classes/service.php';
//add you API resources here
require_once 'src/api/auth/roleApi.php';
require_once 'src/api/auth/userApi.php';
require_once 'src/api/auth/profileApi.php';
require_once 'src/api/installation/installationApi.php';
require_once 'src/api/chdm/chdmApi.php';
require_once 'src/api/Qc_Reporting/Qc_ReportingApi.php';
require_once 'src/api/Service_Reporting/Service_ReportingApi.php';

require_once 'src/utils/router.php';


require_once 'src/utils/JwtHandler.php';

require_once 'vendor/autoload.php'; 

