<?php
function jp_upload_error_message($code) {
    $messages = [
        UPLOAD_ERR_INI_SIZE => 'The file is larger than the server upload limit.',
        UPLOAD_ERR_FORM_SIZE => 'The file is larger than the allowed form limit.',
        UPLOAD_ERR_PARTIAL => 'The file was only partially uploaded.',
        UPLOAD_ERR_NO_FILE => 'No file was uploaded.',
        UPLOAD_ERR_NO_TMP_DIR => 'The server is missing a temporary upload folder.',
        UPLOAD_ERR_CANT_WRITE => 'The server could not write the uploaded file.',
        UPLOAD_ERR_EXTENSION => 'A server extension blocked the upload.',
    ];

    return $messages[$code] ?? 'The upload failed. Please try again.';
}

function jp_upload_image($fieldName, $relativeDirectory, $maxBytes = 2097152) {
    if (!isset($_FILES[$fieldName]) || $_FILES[$fieldName]['error'] === UPLOAD_ERR_NO_FILE) {
        return ['ok' => true, 'path' => null, 'message' => ''];
    }

    $file = $_FILES[$fieldName];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'path' => null, 'message' => jp_upload_error_message($file['error'])];
    }

    if ($file['size'] <= 0 || $file['size'] > $maxBytes) {
        return ['ok' => false, 'path' => null, 'message' => 'Upload an image that is 2MB or smaller.'];
    }

    if (!is_uploaded_file($file['tmp_name'])) {
        return ['ok' => false, 'path' => null, 'message' => 'The upload could not be verified by the server.'];
    }

    $imageInfo = @getimagesize($file['tmp_name']);
    $allowedTypes = [
        IMAGETYPE_JPEG => 'jpg',
        IMAGETYPE_PNG => 'png',
        IMAGETYPE_GIF => 'gif',
        IMAGETYPE_WEBP => 'webp',
    ];

    if (!$imageInfo || !isset($allowedTypes[$imageInfo[2]])) {
        return ['ok' => false, 'path' => null, 'message' => 'Only JPG, PNG, GIF, or WebP images are allowed.'];
    }

    $cleanRelativeDirectory = trim(str_replace('\\', '/', $relativeDirectory), '/');
    if ($cleanRelativeDirectory === '' || strpos($cleanRelativeDirectory, '..') !== false) {
        return ['ok' => false, 'path' => null, 'message' => 'Upload directory is not valid.'];
    }

    $absoluteDirectory = __DIR__ . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $cleanRelativeDirectory) . DIRECTORY_SEPARATOR;
    if (!is_dir($absoluteDirectory) && !mkdir($absoluteDirectory, 0755, true)) {
        return ['ok' => false, 'path' => null, 'message' => 'The upload folder could not be created.'];
    }

    try {
        $token = bin2hex(random_bytes(8));
    } catch (Exception $e) {
        $token = uniqid('img_', true);
    }

    $extension = $allowedTypes[$imageInfo[2]];
    $fileName = date('YmdHis') . '-' . $token . '.' . $extension;
    $absolutePath = $absoluteDirectory . $fileName;
    $relativePath = $cleanRelativeDirectory . '/' . $fileName;

    if (!move_uploaded_file($file['tmp_name'], $absolutePath)) {
        return ['ok' => false, 'path' => null, 'message' => 'The server could not save the uploaded image.'];
    }

    return ['ok' => true, 'path' => $relativePath, 'message' => ''];
}
