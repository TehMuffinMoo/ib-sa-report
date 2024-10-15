<?php
$SkipCSS = true;
require '/home/site/wwwroot/scripts/inc/inc.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    require 'csp-functions.php';
}

$Rand = rand();

use Label305\PptxExtractor\Basic\BasicExtractor;
use Label305\PptxExtractor\Basic\BasicInjector;

$extractor = new BasicExtractor();
$mapping = $extractor->extractStringsAndCreateMappingFile(
    '../files/template-sept-24.pptx',
    '../files/reports/report-'.$Rand.'-extracted.pptx'
);

// Customer Name
// Get Customer Name from API
$AccountInfo = QueryCSP("get","v2/current_user/accounts");
$AccountName = $AccountInfo->results[0]->name;
// Inject Customer Name into PPTX
$TAG_CustomerName = array_search('#TAG1',$mapping);
$mapping[$TAG_CustomerName] = $AccountName;

$injector = new BasicInjector();
$injector->injectMappingAndCreateNewFile(
    $mapping,
    '../files/reports/report-'.$Rand.'-extracted.pptx',
    '../files/reports/report-'.$Rand.'.pptx'
);

## Generate Response
$response = array(
    'Status' => 'Success',
    'Path' => '/files/reports/report-'.$Rand.'.pptx'
);
$responseJSON = json_encode($response);
header('Content-Type: application/json; charset=utf-8');
echo $responseJSON;