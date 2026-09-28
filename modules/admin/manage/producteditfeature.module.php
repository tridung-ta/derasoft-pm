<?php
/*************************************************************************
Editing product feature module
----------------------------------------------------------------
DeraCMS 4.0 Project
**************************************************************************/

# Check permission
    // ini_set('display_errors', 1);
    //     ini_set('display_startup_errors', 1);
    //     error_reporting(E_ALL);
$userInfo->checkPermission('product', 'editfeature');

$templateFile = 'manageproduct.tpl.html';

include_once(ROOT_PATH . 'classes/dao/productfeatures.class.php');
include_once(ROOT_PATH . 'classes/dao/fields.class.php');
include_once(ROOT_PATH . "classes/data/textfilter.class.php");
include_once(ROOT_PATH . "classes/dao/searchs.class.php");
include_once(ROOT_PATH . 'classes/dao/optionstructure.class.php');
include_once(ROOT_PATH . 'classes/dao/optionvalue.class.php');
include_once(ROOT_PATH . "classes/dao/uploadalbums.class.php");
include_once(ROOT_PATH . "classes/dao/uploads.class.php");

$productFeatures = new ProductFeatures($storeId);
$fields = new Fields($storeId);
$search = new Search($storeId);
$optionStructure = new OptionStructure($storeId);
$fieldValue = new OptionValue($storeId);
$uploadAlbums = new UploadAlbums($storeId);
$uploads = new Uploads($storeId);

$template->assign('uploads', $uploads);

# Tabs
$tabLink = '/' . ADMIN_SCRIPT . '?op=manage&act=product';
$listTabs = array(
    $amessages['list_item'] => $tabLink . '&mod=list',
    $amessages['add_new'] => $tabLink . '&mod=add',
    $amessages['list_category'] => $tabLink . '&mod=listcategory',
    $amessages['add_product_category'] => $tabLink . '&mod=addcategory',
    $amessages['list_product_features'] => $tabLink . '&mod=listfeature',
    $amessages['edit_product_features'] => $tabLink . '&mod=editfeature',
    $amessages['clean_trash'] => $tabLink . '&mod=cleantrash'
);
$template->assign('listTabs', $listTabs);
$template->assign('currentTab', 6);

# Get id
$id = (int)$request->element('id');
$template->assign('id', $id);

$itemInfo = $productFeatures->getObject($id);

if (!$itemInfo) {
    $template->assign('validItem', 0);
    return;
}

$template->assign('validItem', 1);
$template->assign('ckEditor', 1);
$template->assign('item', $itemInfo);

#get list of custom fields
$fieldList = $fields->getObjects(1, "`status`='1' AND `module`='productfeatures'", array('position' => 'ASC'));
if ($fieldList) $template->assign('fieldList', $fieldList);

# Custom options
$fieldOptionList = $optionStructure->getObjects(
    1,
    "`status`='1' AND `module`='productfeatures'",
    array('position'=>'ASC')
);

$fieldOptionList = is_array($fieldOptionList) ? $fieldOptionList : [];

$template->assign('fieldOptionList', $fieldOptionList);

# All custom field values
$allFieldValues = $fieldValue->getAllValuesByKeyId($id);
$template->assign('allFieldValues', $allFieldValues);


# Get avatar image
$avatarImg = null;
if ($itemInfo->getAvatar()) {
    $avatarImg = $uploads->getObject($itemInfo->getAvatar());
}
$template->assign('avatarImg', $avatarImg);

# ================= DELETE AVATAR =================
if ($request->element('doo') == 'delAvatar') {
    $avatarId = $itemInfo->getAvatar(); // upload_id

    if ($avatarId) {
        $upload = $uploads->getObject($avatarId);
        if ($upload) {
            $upload->deleteFiles();
            $uploads->DeteImg($avatarId);
            }
            
            $productFeatures->updateData([
                'avatar' => 0
                ], $id);
        $itemInfo = $productFeatures->getObject($id);
    }
}

# ================= SUBMIT =================
if ($_POST && $request->element('doo') == 'submit') {

    $validate = validateData($request);

    if ($validate['invalid']) {
        $template->assign('error', $validate);
    } else {

        # Generate slug
        $textFilter = new TextFilter();
        $slug = $textFilter->urlize($request->element('name'), false, '-');

        $i = 0;
        $dup = 1;
        while ($dup) {
            $dup = $productFeatures->checkDuplicate(
                $slug . ($i ? '-' . $i : ''),
                'slug',
                "`id` <> '$id'"
            );
            if ($dup) $i++;
        }
        $slug .= $i ? '-' . $i : '';

        # Upload album
        $thisYearAlbum = getOrCreateYearUploadAlbum($storeId, $uploadAlbums);

        # Avatar upload
        $avatarUploadId = uploadAvatar(
            $thisYearAlbum,
            $uploads,
            $userInfo,
            'avatar',
            'productfeature'
        );
        #User update
        $properties['user_upload'] = $userInfo->getId();
        # Custom fields
        foreach ($fieldList as $field) {
            $properties[$field->getName()] = stripslashes($request->element($field->getName()));
        }
        $updateData = array(
            'name' => $request->element('name'),
            'slug' => $slug,
            'status' => (int)$request->element('status'),
            'description' => $request->element('description'),
            'pid' => (int)$request->element('pid') ?: NULL,
            'date_updated' => date("Y-m-d H:i:s"),
            'properties' => serialize($properties)
        );

        if ($avatarUploadId) {
            $updateData['avatar'] = $avatarUploadId;
        }

        $result = $productFeatures->updateData($updateData, $id);

        # Custom Options
        if ($result) {
            foreach ($fieldOptionList as $field) {

                $valueType = $request->element($field->getFieldName());

                if ($field->getFieldType() == 4 || $field->getFieldType() == 7) {
                    $selectedKeys = (array)$valueType;
                    $options = $field->getValue();
                    $selectedValues = array_map(function ($key) use ($options) {
                        return isset($options[$key]) ? $options[$key] : $key;
                    }, $selectedKeys);
                    $valueType = implode(", ", $selectedValues);
                }

                if ($field->getFieldType() == 5 || $field->getFieldType() == 6) {
                    $options = $field->getValue();
                    $valueType = isset($options[$valueType]) ? $options[$valueType] : $valueType;
                }

                $fieldValue->updateOrInsertFieldValue(
                    html_entity_decode($valueType),
                    $field->getId(),
                    $id,
                    $storeId
                );
            }
        }

        # Update search
        $search->updateData(
            array(
                "slug" => $slug,
                "title" => Filter($request->element('name')),
                "sapo" => addslashes($request->element('description')),
                "keyword" => Filter($request->element('keyword')),
                "url" => $slug
            ),
            $id
        );

        # Tracking
        $trackings->addData(array(
            'store_id' => $storeId,
            'username' => $userInfo->getUsername(),
            'action' => sprintf($amessages['tracking']['edit_productfeature'], $request->element('name')),
            'date_created' => date("Y-m-d H:i:s"),
            'ip' => $_SERVER['REMOTE_ADDR']
        ));

        header('location:/' . ADMIN_SCRIPT . "?op=manage&act=product&mod=editfeature&id=$id&rcode=7");
        exit;
    }
}

# ================= VALIDATE =================
function validateData($request)
{
    global $amessages, $fieldOptionList;

    include_once(ROOT_PATH . 'classes/data/validate.class.php');
    $validate = new Validate();
    $error = array();
    $error['invalid'] = 0;

    $error['INPUT']['name'] = $validate->validString($request->element('name'), $amessages['name']);
    $error['INPUT']['status'] = $validate->pasteString($request->element('status'));
    $error['INPUT']['description'] = $validate->pasteString($request->element('description'));

    if ($error['INPUT']['name']['error']) {
        $error['invalid'] = 1;
    }

    foreach ($fieldOptionList as $field) {

        $fieldName = $field->getFieldName();
        $fieldValue = $request->element($fieldName);

        if ((is_null($fieldValue) || $fieldValue === '') && $field->getRequired() == 1) {
            $error['INPUT'][$fieldName] = [
                'value' => $fieldValue,
                'error' => 1,
                'message' => $amessages["field"] . " - " . $amessages['invalid_field']
            ];
            $error['invalid'] = 1;
        }
    }

    return $error;
}
