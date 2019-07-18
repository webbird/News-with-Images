<?php


header('Content-type:application/json;charset=utf-8');

require_once('../../../config.php');
// check if user has permissions to access the news_img module
require_once(WB_PATH.'/framework/class.admin.php');
$admin = new admin('Pages', 'pages_modify', false, false);
if (!($admin->is_authenticated() && $admin->get_permission('news_img', 'module'))) {
    throw new RuntimeException('insuficcient rights');
}

if (!isset($_GET['post_id'])) {
    throw new RuntimeException('missing parameters');
}

$post_id = $admin->checkIDKEY('post_id', false, 'GET', true);
if (defined('WB_VERSION') && (version_compare(WB_VERSION, '2.8.3', '>'))) {
    $post_id = intval($_GET['post_id']);
}
if (! is_numeric($post_id) || (intval($post_id)<=0)) {
    throw new RuntimeException('wrong parameter value');
}

require_once __DIR__.'/../functions.inc.php';

try {
    if (
        !isset($_FILES['file']['error']) ||
        is_array($_FILES['file']['error'])
    ) {
        throw new RuntimeException('Invalid parameters.');
    }

    switch ($_FILES['file']['error']) {
        case UPLOAD_ERR_OK:
            break;
        case UPLOAD_ERR_NO_FILE:
            throw new RuntimeException('No file sent.');
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            throw new RuntimeException('Exceeded filesize limit.');
        default:
            throw new RuntimeException('Unknown errors.');
    }

    $imageErrorMessage = '';

    if (!defined('ORDERING_CLASS_LOADED')) {
        require WB_PATH.'/framework/class.order.php';
    }

    $imageErrorMessage = mod_nwi_img_upload($post_id);

    if ($imageErrorMessage!="") {
        throw new RuntimeException($imageErrorMessage);
    }


    // All good, send the response
    echo json_encode([
        'status' => 'ok',
        'path' => $filepath
    ]);
} catch (RuntimeException $e) {
    // Something went wrong, send the err message as JSON
    http_response_code(400);

    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
