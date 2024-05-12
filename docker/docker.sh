#!/bin/sh
docker run -itd \
    -v /root/iyuu:/iyuu \
    -v /root/data:/data \
    -p 8780:8780 \
    --name IYUUPlus \
    --restart=always \
    iyuucn/iyuuplus-dev:latest