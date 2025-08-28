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

```conf
location ^~ / {
  proxy_set_header X-Real-IP $remote_addr;
  proxy_set_header Host $host;
  proxy_set_header X-Forwarded-Proto $scheme;
  proxy_http_version 1.1;
  proxy_set_header Connection "";
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
