<?php

namespace Iyuu\BittorrentClient\Driver\transmission;

trait TraitVersion
{
    /**
     * Transmission RPC version
     * @var int|string
     */
    protected int|string $rpc_version = 0;

    /**
     * 种子状态码 torrent status
     */
    const TR_STATUS_STOPPED = 0;    // Torrent is stopped
    const TR_STATUS_CHECK_WAIT = 1;    // Queued to check files
    const TR_STATUS_CHECK = 2;    // Checking files
    const TR_STATUS_DOWNLOAD_WAIT = 3;    // Queued to download
    const TR_STATUS_DOWNLOAD = 4;    // Downloading
    const TR_STATUS_SEED_WAIT = 5;    // Queued to seed
    const TR_STATUS_SEED = 6;    // Seeding

    const RPC_LT_14_TR_STATUS_CHECK_WAIT = 1;
    const RPC_LT_14_TR_STATUS_CHECK = 2;
    const RPC_LT_14_TR_STATUS_DOWNLOAD = 4;
    const RPC_LT_14_TR_STATUS_SEED = 8;
    const RPC_LT_14_TR_STATUS_STOPPED = 16;

    /**
     * Return the interpretation of the torrent status
     *
     * @param int $intstatus The integer "torrent status"
     * @returns string The translated meaning
     * @return string
     */
    public function getStatusString($intstatus)
    {
        if ($this->rpc_version < 14) {
            if ($intstatus == self::RPC_LT_14_TR_STATUS_CHECK_WAIT) {
                return "Waiting to verify local files";
            }
            if ($intstatus == self::RPC_LT_14_TR_STATUS_CHECK) {
                return "Verifying local files";
            }
            if ($intstatus == self::RPC_LT_14_TR_STATUS_DOWNLOAD) {
                return "Downloading";
            }
            if ($intstatus == self::RPC_LT_14_TR_STATUS_SEED) {
                return "Seeding";
            }
            if ($intstatus == self::RPC_LT_14_TR_STATUS_STOPPED) {
                return "Stopped";
            }
        } else {
            if ($intstatus == self::TR_STATUS_CHECK_WAIT) {
                return "Waiting to verify local files";
            }
            if ($intstatus == self::TR_STATUS_CHECK) {
                return "Verifying local files";
            }
            if ($intstatus == self::TR_STATUS_DOWNLOAD) {
                return "Downloading";
            }
            if ($intstatus == self::TR_STATUS_SEED) {
                return "Seeding";
            }
            if ($intstatus == self::TR_STATUS_STOPPED) {
                return "Stopped";
            }
            if ($intstatus == self::TR_STATUS_SEED_WAIT) {
                return "Queued for seeding";
            }
            if ($intstatus == self::TR_STATUS_DOWNLOAD_WAIT) {
                return "Queued for download";
            }
        }
        return "Unknown";
    }
}