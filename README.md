# webman配置模板，一键安装常用组件！

## 安装 Installation

```sh
composer require ledc/webman
```

忽略扩展安装
```shell
composer require ledc/webman --ignore-platform-req=ext-redis --ignore-platform-req=ext-posix -W
```

## 运行环境

PHP版本：>=8.3

## nginx配置

### 方案1，静态文件优先

```conf
location ^~ / {
  proxy_http_version 1.1;
  proxy_set_header Connection "";
  proxy_set_header Host $host;
  proxy_set_header X-Real-IP $remote_addr;
  proxy_set_header X-Forwarded-Proto $scheme;
  # 代理条件:文件不存在&目录内不存在index.html
  set $should_proxy 1;
  if (-f $request_filename) {
    set $should_proxy 0;
  }
  set $index_file "${request_filename}/index.html";
  if (-f $index_file) {
    set $should_proxy 0;
  }
  # 是否执行代理
  if ($should_proxy = 1) {
    proxy_pass http://127.0.0.1:8787;
  }
}
```

### 方案2，静态文件优先

```conf
location ^~ / {
  proxy_http_version 1.1;
  proxy_set_header Connection "";
  proxy_set_header Host $host;
  proxy_set_header X-Real-IP $remote_addr;
  proxy_set_header X-Forwarded-Proto $scheme;
  if (!-f $request_filename){
    proxy_pass http://127.0.0.1:8787;
  }
}
```

### 方案3，静态文件优先

```conf
location ^~ / {
  # 默认访问、index.html 或 /pages 开头的路径，返回 index.html
  location ~* (^/$|^/index\.html$|^/pages/) {
    try_files $uri $uri/ /index.html;
  }
  proxy_http_version 1.1;
  proxy_set_header Connection "";
  proxy_set_header Host $host;
  proxy_set_header X-Real-IP $remote_addr;
  proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
  proxy_set_header X-Forwarded-Proto $scheme;
  if (!-f $request_filename){
    proxy_pass http://127.0.0.1:8787;
  }
}
```

### 长连接

```conf
location = /websocket
{
  proxy_pass http://127.0.0.1:8788;
  proxy_http_version 1.1;
  proxy_read_timeout 300s;
  proxy_set_header Host $host;
  proxy_set_header Upgrade $http_upgrade;
  proxy_set_header Connection "Upgrade";
  proxy_set_header X-Real-IP $remote_addr;
}
```