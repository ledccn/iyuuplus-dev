<?php

namespace app\admin\services\rss;

use JsonSerializable;

/**
 * 种子数据结构
 */
class TorrentItem implements JsonSerializable
{
    /**
     * 标题
     * @var string
     */
    protected string $title;
    /**
     * 下载链接
     * @var string
     */
    protected string $download;
    /**
     * 时间戳
     * @var int
     */
    protected int $time;
    /**
     * GUID
     * @var string
     */
    protected string $guid;
    /**
     * 种子体积
     * @var string|null
     */
    protected ?string $size;
    /**
     * 种子体积
     * @var int|null
     */
    protected ?int $length;

    /**
     * @param array $item
     */
    final public function __construct(array $item = [])
    {
        foreach ($item as $property => $value) {
            if (property_exists($this, $property)) {
                $this->{$property} = $value;
            }
        }
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @param string $title
     * @return TorrentItem
     */
    public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    /**
     * @return string
     */
    public function getDownload(): string
    {
        return $this->download;
    }

    /**
     * @param string $download
     * @return TorrentItem
     */
    public function setDownload(string $download): self
    {
        $this->download = $download;
        return $this;
    }

    /**
     * @return int
     */
    public function getTime(): int
    {
        return $this->time;
    }

    /**
     * @param int $time
     * @return TorrentItem
     */
    public function setTime(int $time): self
    {
        $this->time = $time;
        return $this;
    }

    /**
     * @return string
     */
    public function getGuid(): string
    {
        return $this->guid;
    }

    /**
     * @param string $guid
     * @return TorrentItem
     */
    public function setGuid(string $guid): self
    {
        $this->guid = $guid;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getSize(): ?string
    {
        return $this->size;
    }

    /**
     * @return int|null
     */
    public function getLength(): ?int
    {
        return $this->length;
    }

    /**
     * @param int|null $length
     * @return TorrentItem
     */
    public function setLength(?int $length): self
    {
        $this->length = $length;
        return $this;
    }

    /**
     * @param string|null $size
     * @return TorrentItem
     */
    public function setSize(?string $size): self
    {
        $this->size = $size;
        return $this;
    }

    /**
     * 转数组
     * @return array
     */
    final public function toArray(): array
    {
        return get_object_vars($this);
    }

    /**
     * 转数组
     * @return array
     */
    final public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }

    /**
     * 转数组
     * @return string
     */
    final public function __toString(): string
    {
        return json_encode($this->jsonSerialize(), JSON_UNESCAPED_UNICODE);
    }
}
