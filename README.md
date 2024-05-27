### 搭建一个简单的随机图片API，支持Docker部署

### 项目地址

[https://github.com/Nei-Xin/random-pic-api](https://github.com/Nei-Xin/random-pic-api)

### 更新

#### 2024.5.27

##### 新增

- /pc路径，显示横屏图片，例如：https://api.zzii.de/random/pc

![https://api.zzii.de/random/pc](https://api.zzii.de/random/pc)

- /mobile，显示竖屏图片，例如：https://api.zzii.de/random/mobile

![https://api.zzii.de/random/mobile](https://api.zzii.de/random/mobile)

- 镜像大小减小了

#### 简介

随机图片 API 是一种允许开发者从一个图片库或者指定的目录中获取随机图片的接口。这种 API 通常用于网站、移动应用程序或其他软件中，以便动态地展示随机图片，例如用作背景图片、占位图、或者其他需要随机化内容的场景。

### 在线体验

[https://api.zzii.de/](https://api.zzii.de/random)

![https://api.zzii.de/](https://api.zzii.de/random)

### 特性

- 图片随机展示
- 设备适配：通过检测用户代理字符串，判断访问设备是手机还是电脑，并根据设备类型选择对应的图片文件夹路径。
- 图片格式支持：web,jpg,jpeg,png,gif

### 部署

#### PHP

直接丢到有PHP和Nginx的环境中就行

#### Docker

```yml
version: '3.9'
services:
    random-api:
        image: 'neixin/random-pic-api'
        volumes:
			# 竖屏图片
            - './portrait:/var/www/html/portrait'
            # 横屏图片
            - './landscape:/var/www/html/landscape'
        ports:
            - '8080:80'
```

### 图片处理

#### 代码

```py
from PIL import Image
import os

# 检查图片方向
def get_image_orientation(image_path):
    with Image.open(image_path) as img:
        width, height = img.size
        return "landscape" if width > height else "portrait"

# 转换图片为 WebP 格式
def convert_to_webp(image_path, output_folder, max_pixels=178956970):
    try:
        with Image.open(image_path) as img:
            # Check image size
            width, height = img.size
            if width * height > max_pixels:
                print(f"Skipping {image_path} because it exceeds the size limit.")
                return
            
            # Save the image as WebP
            output_path = os.path.join(output_folder, os.path.splitext(os.path.basename(image_path))[0] + ".webp")
            img.save(output_path, "webp")
    except Exception as e:
        print(f"Failed to convert {image_path}: {e}")

# 遍历文件夹中的图片
def process_images(input_folder, output_folder_landscape, output_folder_portrait):
    for filename in os.listdir(input_folder):
        if filename.endswith(('.jpg', '.jpeg', '.png')):
            image_path = os.path.join(input_folder, filename)
            orientation = get_image_orientation(image_path)
            try:
                if orientation == "landscape":
                    convert_to_webp(image_path, output_folder_landscape)
                else:
                    convert_to_webp(image_path, output_folder_portrait)
            except Exception as e:
                print(f"Error processing {image_path}: {e}. Skipping this image.")

# 指定输入和输出文件夹
input_folder = "/root/photos"
output_folder_landscape = "/root/landscape"
output_folder_portrait = "/root/portrait"

# 执行转换
process_images(input_folder, output_folder_landscape, output_folder_portrait)
```

#### 作用

将横屏和竖屏的图片分开，并转化为webp格式，使用时注意修改文件路径

### 最后

如果觉得还不错的话，可以点个star
