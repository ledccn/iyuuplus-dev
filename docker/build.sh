#!/bin/sh
docker build -f Dockerfile -t iyuuplus-dev:latest .
docker run -itd \
    -p 8787:8787 \
    -p 3131:3131 \
    --name IYUUPlus \
    --restart=always \
    iyuuplus-dev:latest

docker exec -it IYUUPlus bash
