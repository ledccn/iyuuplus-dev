#!/bin/sh
docker build -f Dockerfile -t iyuuplus-dev:latest .
docker run -itd \
    -p 8780:8780 \
    --name IYUUPlus \
    --restart=always \
    iyuuplus-dev:latest

docker exec -it IYUUPlus bash
