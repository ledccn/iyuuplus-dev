<?php
/**
 * Here is your custom functions.
 */


if (!function_exists('io_mkdir')) {
    /**
     * 创建文件夹
     * @param $dir
     * @return bool
     */
    function io_mkdir($dir='',$path=''){
        $base_dir = config('plugin.netdisk.io.base_dir');
        $relative_dir = md5(admin_id()).$path.$dir;
        $full_dir = $base_dir . $relative_dir;
        if (!is_dir($full_dir)) {
            mkdir($full_dir, 0777, true);
            return true;
        }else{
            return false;
        }
    }
}

if (!function_exists('io_rename')) {
    /**
     * 文件或文件夹重命名
     * @param $newName
     * @param $oldName
     * @return bool
     */
    function io_rename($newName,$oldName){
        $base_dir = config('plugin.netdisk.io.base_dir');
        $relative_name_new = $base_dir.md5(admin_id()).$newName;
        $relative_name_old = $base_dir.md5(admin_id()).$oldName;
        // 判断新文件夹名是否已存在
        if (file_exists($relative_name_new)) {
            //文件名文件夹已存在
            return false;
        } else {
            // 重命名文件夹
            if (rename($relative_name_old, $relative_name_new)) {
                return true;
            } else {
                return false;
            }
        }
    }
}

if (!function_exists('short_id')) {
    /**
     * 根据id获取短标识;
     * 加入时间戳避免猜测;id不可逆
     *
     * eg: 234==>4JdQ9Lgw;  100000000=>4LyUC2xQ
     */
    function short_id($id){
        $id = intval($id) + microtime(true)*10000;
        $id = pack('H*',base_convert($id,10,16));
        $base64  = base64_encode($id);
        $replace = array('/'=>'_','+'=>'-','='=>'');
        return strtr($base64, $replace);
    }
}

if (!function_exists('formatBytes')) {
    /**
     * 格式化文件大小
     * @param $file_size
     * @return string
     */
    function formatBytes($file_size): string
    {
        $size = sprintf("%u", $file_size);
        if($size == 0) {
            return("0 B");
        }
        $size_name = array(" B", " KB", " MB", " GB", " TB", " PB", " EB", " ZB", " YB");
        return round($size/pow(1024, ($i = floor(log($size, 1024)))), 2) . $size_name[$i];
    }
}

if (!function_exists('obj2array')) {
    /**
     * 将obj深度转化成array
     *
     * @param object|array $obj 要转换的数据 可能是数组 也可能是个对象 还可能是一般数据类型
     * @return array || 一般数据类型
     */
    function obj2array($obj){
        if (is_array($obj)) {
            foreach($obj as &$value) {
                $value = obj2array($value);
            };unset($value);
            return $obj;
        } elseif (is_object($obj)) {
            $obj = get_object_vars($obj);
            return obj2array($obj);
        } else {
            return $obj;
        }
    }
}

if (!function_exists('deleteDirectory')) {
    /**
     * 遍历删除目录与文件
     * @param string $path
     * @return void
     */
    function deleteDirectory($path) {
        if (is_dir($path)) {
            $files = array_diff(scandir($path), array('.', '..'));
            foreach ($files as $file) {
                $subPath = $path . '/' . $file;
                if (is_dir($subPath)) {
                    deleteDirectory($subPath);
                } else {
                    unlink($subPath);
                }
            }
            rmdir($path);
        } elseif (is_file($path)) {
            unlink($path);
        }
    }
}
