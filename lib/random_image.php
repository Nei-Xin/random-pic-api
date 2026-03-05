<?php

function ri_respond_with_error($statusCode, $message) {
    http_response_code($statusCode);
    header('Content-Type: text/plain; charset=UTF-8');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    echo $message;
    exit;
}

function ri_get_images_from_dir($path) {
    $images = array();
    if ($img_dir = @opendir($path)) {
        while (false !== ($img_file = readdir($img_dir))) {
            if (preg_match("/\.(webp|jpg|jpeg|png|gif)$/i", $img_file)) {
                $images[] = $img_file;
            }
        }
        closedir($img_dir);
    }
    return $images;
}

function ri_generate_image_path($path, $img) {
    return rtrim($path, '/\\') . DIRECTORY_SEPARATOR . $img;
}

function ri_get_mime_type($ext) {
    switch (strtolower($ext)) {
        case 'webp': return 'image/webp';
        case 'jpg':
        case 'jpeg': return 'image/jpeg';
        case 'png': return 'image/png';
        case 'gif': return 'image/gif';
        default: return 'application/octet-stream';
    }
}

function ri_set_html_cache_headers() {
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
}

function ri_set_image_cache_headers($imgPath, $maxAge = 3600) {
    header('Cache-Control: public, max-age=' . (int)$maxAge);

    $mtime = @filemtime($imgPath);
    $size = @filesize($imgPath);
    if ($mtime !== false) {
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $mtime) . ' GMT');
    }

    if ($mtime !== false && $size !== false) {
        $etag = '"' . md5($imgPath . '|' . $size . '|' . $mtime) . '"';
        header('ETag: ' . $etag);

        $ifNoneMatch = $_SERVER['HTTP_IF_NONE_MATCH'] ?? '';
        if ($ifNoneMatch === $etag) {
            http_response_code(304);
            exit;
        }

        $ifModifiedSince = $_SERVER['HTTP_IF_MODIFIED_SINCE'] ?? '';
        if ($ifModifiedSince !== '') {
            $clientTime = strtotime($ifModifiedSince);
            if ($clientTime !== false && $mtime <= $clientTime) {
                http_response_code(304);
                exit;
            }
        }
    }
}

function ri_get_random_or_specific_image($imgList) {
    if (isset($_GET['file'])) {
        $specific = basename((string)$_GET['file']);
        if (in_array($specific, $imgList, true)) {
            return $specific;
        }
        ri_respond_with_error(404, 'Image not found.');
    }

    shuffle($imgList);
    $img = reset($imgList);
    if ($img === false) {
        ri_respond_with_error(503, 'No images available.');
    }
    return $img;
}

function ri_output_image($path, $img) {
    $imgPath = ri_generate_image_path($path, $img);
    if (!is_file($imgPath) || !is_readable($imgPath)) {
        ri_respond_with_error(404, 'Image not found.');
    }

    $img_extension = pathinfo($img, PATHINFO_EXTENSION);
    header('Content-Type: ' . ri_get_mime_type($img_extension));
    ri_set_image_cache_headers($imgPath);
    readfile($imgPath);
    exit;
}

function ri_is_browser_access() {
    $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
    return strpos($accept, 'text/html') !== false;
}

function ri_render_html_page($img) {
    ri_set_html_cache_headers();
    $encodedImg = urlencode($img);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Random Image</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background: #000;
        }
        img {
            max-width: 100%;
            max-height: 100vh;
            object-fit: contain;
        }
    </style>
</head>
<body>
    <img src="?type=img&file=<?= $encodedImg ?>" alt="Random Image">
</body>
</html>
<?php
    exit;
}

function ri_handle_random_image_request($path) {
    $imgList = ri_get_images_from_dir($path);
    if (empty($imgList)) {
        ri_respond_with_error(503, 'No images available.');
    }

    if (isset($_GET['type']) && $_GET['type'] === 'img') {
        $img = ri_get_random_or_specific_image($imgList);
        ri_output_image($path, $img);
    }

    $img = ri_get_random_or_specific_image($imgList);
    if (ri_is_browser_access()) {
        ri_render_html_page($img);
    }

    ri_output_image($path, $img);
}

