<?php

$SkipCSS = true;
require_once(__DIR__.'/../inc/inc.php');
header('Content-Type: application/json; charset=utf-8');

if (!($_REQUEST['function'])) {
    echo json_encode(array(
        'Error' => 'Function not specified.',
        'Request' => $_REQUEST
    ),JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
    die();
} else {
    switch ($_REQUEST['function']) {
        case 'login':
            echo json_encode(NewAuth($_POST['un'],$_POST['pw']),JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
            break;
        case 'logout':
            echo json_encode(InvalidateAuth(),JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
            break;
        case 'heartbeat':
            if (GetAuth()['Authenticated'] == true) {
                http_response_code(200);
            } else {
                http_response_code(301);
                echo "Timed out.";
                die();
            }
            break;
        case 'whoami':
            if (isset(GetAuth()['Authenticated'])) {
                $AuthContent = GetAuth();
                $AuthContent['headers'] = getallheaders();
                $UnsetHeaders = array(
                    "Remote-Email",
                    "Remote-Groups",
                    "Remote-Name",
                    "Remote-User"
                );
                foreach ($UnsetHeaders as $UnsetHeader) {
                    unset($AuthContent['headers'][$UnsetHeader]);
                }
                echo json_encode($AuthContent,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
            }
            break;
        case 'CheckAccess':
            if (isset($_REQUEST['node']) && GetAuth()['Authenticated'] == true) {
                $Result = array(
                    "node" => $_REQUEST['node']
                );
                if (CheckAccess(null,$_REQUEST['node'])) {
                    $Result['permitted'] = true;
                } else {
                    $Result['permitted'] = false;
                }
                echo json_encode($Result,JSON_PRETTY_PRINT);
            }
            break;
        case 'GetLog':
            if (CheckAccess(null,"ADMIN-LOGS")) {
                if (isset($_REQUEST['date'])) {
                    $Date = $_REQUEST['date'];
                } else {
                    $Date = "";
                }
                echo json_encode(getLog($Date), JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
            } else {
                return false;
            }
            break;
        case 'GetRBAC':
            if (CheckAccess(null,"ADMIN-RBAC")) {
                if (isset($_REQUEST['group'])) {
                    $Group = $_REQUEST['group'];
                } else {
                    $Group = null;
                }
                if (isset($_REQUEST['action'])) {
                    $Action = $_REQUEST['action'];
                } else {
                    $Action = null;
                }
                echo json_encode(getRBAC($Group,$Action), JSON_PRETTY_PRINT);
            } else {
                return false;
            }
            break;
        case 'SetRBAC':
            if (CheckAccess(null,"ADMIN-RBAC")) {
                if (isset($_REQUEST['group'])) {
                    $Group = $_REQUEST['group'];
                } else {
                    $Group = null;
                }
                if (isset($_REQUEST['description'])) {
                    $Description = $_REQUEST['description'];
                } else {
                    $Description = null;
                }
                if (isset($_REQUEST['key'])) {
                    $Key = $_REQUEST['key'];
                } else {
                    $Key = null;
                }
                if (isset($_REQUEST['value'])) {
                    $Value = $_REQUEST['value'];
                } else {
                    $Value = null;
                }
                 echo json_encode(setRBAC($Group,$Description,$Key,$Value), JSON_PRETTY_PRINT);
            }
            break;
        case 'DeleteRBAC':
            if (CheckAccess(null,"ADMIN-RBAC")) {
                if (isset($_REQUEST['group'])) {
                    $Group = $_REQUEST['group'];
                } else {
                    $Group = null;
                }
                echo json_encode(deleteRBAC($Group), JSON_PRETTY_PRINT);
            }
            break;
        case 'GetConfig':
            if (CheckAccess(null,"ADMIN-CONFIG")) {
                $config = getConfig();
                $config['Security']['salt'] = "********";
                echo json_encode($config,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
                writeLog("Config","Queried Configuration","info",$_REQUEST);
            }
            break;
        case 'SetConfig':
            if (CheckAccess(null,"ADMIN-CONFIG")) {
                array_shift($_REQUEST);
                $config = getConfig();
                $config['Security']['salt'] = "********";
                if (isset($_REQUEST['systemLogFileName'])) { setConfig("System","logfilename",$_REQUEST['systemLogFileName']); }
                if (isset($_REQUEST['systemLogDirectory'])) { setConfig("System","logdirectory",$_REQUEST['systemLogDirectory']); }
                if (isset($_REQUEST['systemLogLevel'])) { setConfig("System","loglevel",$_REQUEST['systemLogLevel']); }
                if (isset($_REQUEST['systemLogRetention'])) { setConfig("System","logretention",$_REQUEST['systemLogRetention']); }
                if (isset($_REQUEST['systemCURLTimeout'])) { setConfig("System","CURL-Timeout",$_REQUEST['systemCURLTimeout']); }
                if (isset($_REQUEST['systemCURLTimeoutConnect'])) { setConfig("System","CURL-ConnectTimeout",$_REQUEST['systemCURLTimeoutConnect']); }
                if (isset($_REQUEST['systemRBACFile'])) { setConfig("System","rbacjson",$_REQUEST['systemRBACFile']); }
                if (isset($_REQUEST['systemRBACInfoFile'])) { setConfig("System","rbacinfo",$_REQUEST['systemRBACInfoFile']); }
                if (isset($_REQUEST['securitySalt'])) { setConfig("Security","salt",$_REQUEST['securitySalt']); }
                $newConfig = getConfig();
                $newConfig['Security']['salt'] = "********";
                echo json_encode($newConfig,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
                $logArr = array(
                    "Old Configuration" => $config,
                    "New Configuration" => $newConfig
                );
                writeLog("Config","Updated configuration","warning",$logArr);
            }
            break;
        case 'getChangelog':
            if ($method = checkRequestMethod('GET')) {
                $MD = generate_markdown(__DIR__.'/../CHANGELOG.md');
                header('Content-Type: text/html; charset=utf-8');
                echo '<link href="/assets/css/changelog.css" rel="stylesheet">';
                echo '<h1><center>Change Log</center></h1>';
                print_r($MD);
            }
            break;
        case 'getUUID':
            header('Content-type: text/plain');
            echo \Ramsey\Uuid\Uuid::uuid4();
            break;
        case 'createSecurityReport':
            if ($method = checkRequestMethod('POST')) {
                if ((isset($_POST['APIKey']) OR isset($_COOKIE['crypt'])) AND isset($_POST['StartDateTime']) AND isset($_POST['EndDateTime']) AND isset($_POST['Realm']) AND isset($_POST['id'])) {
                    if (isValidUuid($_POST['id'])) {
                        $response = generateSecurityReport($_POST['StartDateTime'],$_POST['EndDateTime'],$_POST['Realm'],$_POST['id']);
                        echo json_encode($response,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
                    }
                }
            }
            break;
        case 'downloadSecurityReport':
            if ($method = checkRequestMethod('GET')) {
                if (isset($_REQUEST['id']) AND isValidUuid($_REQUEST['id'])) {
                    $id = $_REQUEST['id'];
                    $File = __DIR__.'/../files/reports/report-'.$id.'.pptx';
                    if (file_exists($File)) {
                        header('Content-type: application/pptx');
                        header('Content-Disposition: inline; filename="report-'.$id.'.pptx"');
                        header('Content-Transfer-Encoding: binary');
                        header('Accept-Ranges: bytes');
                        readfile($File);
                    } else {
                        echo 'Invalid ID';
                    }
                }
            }
            break;
        case 'getSecurityReportProgress':
            if ($method = checkRequestMethod('GET')) {
                if (isset($_REQUEST['id']) AND isValidUuid($_REQUEST['id'])) {
                    $id = $_REQUEST['id'];
                    echo getProgress($id,41); // Produces percentage for use on progress bar
                }
            }
            break;
        case 'createLicenseReport':
            if ($method = checkRequestMethod('POST')) {
                if ((isset($_POST['APIKey']) OR isset($_COOKIE['crypt'])) AND isset($_POST['StartDateTime']) AND isset($_POST['EndDateTime']) AND isset($_POST['Realm'])) {
                    $response = getLicenseCount($_POST['StartDateTime'],$_POST['EndDateTime'],$_POST['Realm']);
                    echo json_encode($response,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
                }
            }
            break;
        case 'crypt':
            if ($method = checkRequestMethod('POST')) {
                if (isset($_POST['key'])) {
                    echo json_encode(array(encrypt($_POST['key'],getConfig("Security","salt"))));
                }
            }
            break;
        case 'getThreatActors':
            if ($method = checkRequestMethod('POST')) {
                if ((isset($_POST['APIKey']) OR isset($_COOKIE['crypt'])) AND isset($_POST['StartDateTime']) AND isset($_POST['EndDateTime']) AND isset($_POST['Realm'])) {
                    $Actors = GetB1ThreatActors($_POST['StartDateTime'],$_POST['EndDateTime']);
                    if (!isset($Actors['Error'])) {
                        echo json_encode(GetB1ThreatActorsById($Actors),JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
                    } else {
                        echo json_encode($Actors);
                    };
                }
            }
            break;
    }
}