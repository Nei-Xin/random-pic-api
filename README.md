### 搭建一个简单的随机图片API，支持Docker部署

### 更新

#### 2026.10.22

- Docker 基础镜像固定为 `php:8.5.12-cli-alpine`，包含 CVE-2026-109618、CVE-2026-109619 的官方修复。
- 关闭本项目不需要的文件上传和 POST 表单自动解析；构建时验证 PHP 版本、配置和 PHP 文件语法，CI 构建时拉取基础镜像。
- 已运行的容器需要重新构建或拉取修复后的镜像并重建容器，更新源码本身不会升级正在运行的 PHP。

#### 2026.8.8

##### 新增

- 支持递归遍历图片目录，可以在横屏和竖屏目录中使用任意层级的子目录组织图片。
- 使用相对路径识别图片，不同子目录中可以存在同名图片。

#### 2024.5.27

##### 新增

- /pc路径，显示横屏图片，例如：[https://api.neix.in/random/pc](https://api.neix.in/random/pc)

![https://api.neix.in/random/pc](https://api.neix.in/random/pc)

- /mobile，显示竖屏图片，例如：[https://api.neix.in/random/mobile](https://api.neix.in/random/mobile)

![https://api.neix.in/random/mobile](https://api.neix.in/random/mobile)

- 镜像大小减小了

#### 简介

随机图片 API 是一种允许开发者从一个图片库或者指定的目录中获取随机图片的接口。这种 API 通常用于网站、移动应用程序或其他软件中，以便动态地展示随机图片，例如用作背景图片、占位图、或者其他需要随机化内容的场景。

### 在线体验

[https://api.neix.in/random](https://api.zzii.de/random)

![https://api.neix.in/random](https://api.neix.in/random)

### 特性

- 图片随机展示
- 设备适配：通过检测用户代理字符串，判断访问设备是手机还是电脑，并根据设备类型选择对应的图片文件夹路径。
- 递归目录：自动遍历横屏和竖屏目录下的所有子目录，无需将图片全部放在目录第一层。
- 图片格式支持：webp,jpg,jpeg,png,gif

### 部署

#### PHP

将程序放到 PHP 和 Nginx 环境中，并配置横屏和竖屏图片目录。

根据 [PHP 官方安全公告](https://www.php.net/archive/2026.php#2026-10-21-5)，PHP 8.2、8.3、8.4、8.5 分支分别至少需要 **8.2.35、8.3.36、8.4.27、8.5.12**（或发行商明确包含这两个漏洞修复的补丁包）。推荐使用 PHP 8.5.12 或同分支更新的安全版本。升级后重启所有 PHP-FPM / Web 服务进程，并确认处理 Web 请求的 PHP 已更新；命令行 PHP 可能与 Web 服务使用不同版本。

可将 `docker/php-security.ini` 中的配置加入该站点实际使用的 PHP 配置后重启服务。关闭上传和 POST 解析是额外加固，不能替代 PHP 升级。

#### Docker

```yml
services:
  random-api:
    image: neixin/random-pic-api:latest
    restart: unless-stopped
    ports:
      - "8080:80"
    volumes:
      - ./photos/portrait:/var/www/html/portrait:ro
      - ./photos/landscape:/var/www/html/landscape:ro
```

上面的配置会直接使用 Docker Hub 中的镜像，启动命令：

```bash
docker compose up -d
```

项目内提供的 `docker-compose.yml` 使用当前源码和 `Dockerfile` 构建本地镜像，适合开发或自行修改代码后部署：

```bash
docker compose up -d --build
```

#### 更新已有部署以应用安全修复

使用本仓库源码构建（项目内的 `docker-compose.yml`）：

```bash
docker compose build --pull --no-cache
docker compose up -d --force-recreate
docker compose exec random-api php -v
docker compose exec random-api php -r 'var_export(["file_uploads" => ini_get("file_uploads"), "enable_post_data_reading" => ini_get("enable_post_data_reading")]);'
```

确认 PHP 为 8.5.12，两个配置均为关闭状态（空字符串或 `"0"`）。后续安全更新需要同步更新 `Dockerfile` 中固定的 PHP 镜像版本并重新构建。

使用 Docker Hub 镜像的部署，在本次修改构建并发布到 Docker Hub 后执行：

```bash
docker compose pull
docker compose up -d --force-recreate
docker compose exec random-api php -v
```

同样确认运行中的 PHP 为已修复版本；只重启旧容器不会更新镜像中的 PHP。

图片可以按分类存放在任意层级的子目录中，例如：

```text
photos/
├─ landscape/
│  ├─ nature/
│  │  ├─ mountain.jpg
│  │  └─ lake.webp
│  └─ city/
│     └─ night.png
└─ portrait/
   ├─ people/
   │  └─ portrait.jpg
   └─ wallpaper/
      └─ phone.webp
```

程序会递归扫描 `/var/www/html/portrait` 和 `/var/www/html/landscape`。不要将整个图片主目录挂载到 `/var/www/html`，否则会覆盖容器内的程序文件。

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

### 赞助
感谢 [YXVM](https://yxvm.com/) 对本项目的支持！
### 最后

如果觉得还不错的话，可以点个star

个人博客地址：[极客与乐趣](https://blog.gckjoy.com/)


## Star History

[![Star History Chart](https://api.star-history.com/svg?repos=Nei-Xin/random-pic-api&type=Date)](https://www.star-history.com/#Nei-Xin/random-pic-api&Date)

