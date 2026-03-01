<?php
$pcPath = 'landscape';
$mobilePath = 'portrait';

// 函数：从目录中获取图片列表
function getImagesFromDir($path) {
    $images = array();
    if ($img_dir = @opendir($path)) {
        while (false !== ($img_file = readdir($img_dir))) {
            // 匹配 webp、jpg、jpeg、png、gif 格式的图片
            if (preg_match("/\.(webp|jpg|jpeg|png|gif)$/i", $img_file)) {
                $images[] = $img_file;
            }
        }
        closedir($img_dir);
    }
    return $images;
}

// 函数：生成完整的图片路径
function generateImagePath($path, $img) {
    return $path . '/' . $img;
}

// 获取MIME类型
function getMimeType($ext) {
    switch ($ext) {
        case 'webp': return 'image/webp';
        case 'jpg':
        case 'jpeg': return 'image/jpeg';
        case 'png':  return 'image/png';
        case 'gif':  return 'image/gif';
        default:     return 'image/jpeg';
    }
}

// 检测用户代理以区分手机和电脑访问
$userAgent = $_SERVER['HTTP_USER_AGENT'];
$isMobile = preg_match('/(android|iphone|ipad|ipod|blackberry|windows phone)/i', $userAgent);

// 根据访问设备设置图片路径
if ($isMobile) {
    $path = $mobilePath;
} else {
    $path = $pcPath;
}

// 缓存图片列表
$imgList = getImagesFromDir($path);

// 如果指定了type=img参数，返回原始图片
if (isset($_GET['type']) && $_GET['type'] === 'img') {
    if (isset($_GET['file'])) {
        $specific = basename($_GET['file']);
        if (in_array($specific, $imgList)) {
            $img = $specific;
        } else {
            http_response_code(404);
            exit;
        }
    } else {
        shuffle($imgList);
        $img = reset($imgList);
    }
    $img_extension = pathinfo($img, PATHINFO_EXTENSION);
    header('Content-Type: ' . getMimeType($img_extension));
    readfile(generateImagePath($path, $img));
    exit;
}

// 从列表中随机选择一张图片
shuffle($imgList);
$img = reset($imgList);
$img_extension = pathinfo($img, PATHINFO_EXTENSION);

// 检测是否为浏览器直接访问
$accept = isset($_SERVER['HTTP_ACCEPT']) ? $_SERVER['HTTP_ACCEPT'] : '';
$isBrowserAccess = strpos($accept, 'text/html') !== false;

if ($isBrowserAccess) {
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
} else {
    header('Content-Type: ' . getMimeType($img_extension));
    readfile(generateImagePath($path, $img));
}
?>

