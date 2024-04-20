#!/bin/sh
docker run -itd \
    -v /root/iyuu:/iyuu \
    -v /root/data:/data \
    -p 8787:8787 \
    -p 3131:3131 \
    --name IYUUPlus \
    --restart=always \
    iyuucn/iyuuplus-dev:latest