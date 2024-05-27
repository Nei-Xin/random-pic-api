<?php
$pcPath = '../portrait';

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

// 设置图片路径为 portrait
$path = $pcPath;

// 缓存图片列表
$imgList = getImagesFromDir($path);

// 从列表中随机选择一张图片
shuffle($imgList);
$img = reset($imgList);

// 获取图片的格式
$img_extension = pathinfo($img, PATHINFO_EXTENSION);

// 根据图片的格式设置 Content-Type
switch ($img_extension) {
    case 'webp':
        header('Content-Type: image/webp');
        break;
    case 'jpg':
    case 'jpeg':
        header('Content-Type: image/jpeg');
        break;
    case 'png':
        header('Content-Type: image/png');
        break;
    case 'gif':
        header('Content-Type: image/gif');
        break;
    // 添加其他格式的处理方式
    // case 'bmp':
    //     header('Content-Type: image/bmp');
    //     break;
}

// 生成完整的图片路径
$img_path = generateImagePath($path, $img);

// 直接输出所选的随机图片
readfile($img_path);
?>
