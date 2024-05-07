<?php

namespace plugin\netdisk\app\admin\controller;

use plugin\admin\app\controller\UploadController;
use plugin\admin\app\model\Upload;
use plugin\netdisk\app\common\Sort;
use plugin\netdisk\app\model\IoShare;
use support\Request;
use support\Response;
use plugin\netdisk\app\model\IoSource;
use plugin\admin\app\controller\Crud;
use support\exception\BusinessException;

/**
 * 文档管理
 * file_id前后台其实都没有太多作用，可以去掉，暂时先保留用于upload表也存储一份备案
 */
class IoSourceController extends Crud
{
    
    /**
     * @var IoSource
     */
    protected $model = null;

    /**
     * 只返回当前管理员数据
     * @var string
     */
    protected $dataLimit = 'personal';

    /**
     * 构造函数
     * @return void
     */
    public function __construct()
    {
        $this->model = new IoSource;
    }
    
    /**
     * 浏览
     * @return Response
     */
    public function index(): Response
    {
        return raw_view('io-source/index');
    }

    /**
     * 上传
     * @return Response
     */
    public function upload(Request $request): Response
    {
        if ($request->method() === 'POST') {
            $file = current($request->file());
            if (!$file || !$file->isValid()) {
                return $this->json(1, '未找到文件');
            }

            $path='';
            $pid = $request->post('pid',0);
            $pids = [];
            if($pid>0){
                //有父级，需要拼接上父级目录
                [$path_arr,$pids] = IoSource::getPathInfo($pid);
                $paths = implode('/',$path_arr);
                $path = $paths;
            }

            $relative_dir = md5(admin_id()).($pid>0?'/'.$path:'');
            $data = $this->base($request, $relative_dir);
            $file_id = $this->doInserts($data);//附件入库执行

            $add = [
                'admin_id'  => admin_id(),
                'title'     => $data['name'],
                'pid'       => $pid,
                'pids'      => implode(',',$pids),
                'ext'       => $data['ext'],
                'is_folder' => 0,
                'file_id'   => $file_id,
                'file_size' => $data['size'],
            ];
            $id = $this->doInsert($add);//文档入库
            $hash = short_id($id);
            $this->model->where('id',$id)->update(['hash'=>$hash]);

            return $this->json(0, '上传成功', [
                'id' => $id,
                'file_id' => $file_id,
                'url' => $data['url'],
                'name' => $data['name'],
                'size' => $data['size'],
            ]);
        }
        $pid = (int)$request->get('pid',0);
        return raw_view('io-source/upload',['pid'=>$pid]);
    }

    /**
     * 查询
     * @return Response
     */
    public function select(Request $request): Response
    {
        [$where, $format, $limit, $field, $order] = $this->selectInput($request);
        $query = $this->doSelect($where, $field, $order);
        $paginator = $query->paginate($limit);
        $total = $paginator->total();
        $items = $paginator->items();

        $pid = (int)$request->get('pid',0);
        $path = [];
        $pids = [];
        if($pid>0){
            [$path,$pids] = IoSource::getPathInfo($pid);
        }

        //自然排序
        $items = (array)$items;
        foreach($items as $k=>$v){
            $items[$k]['sort'] = $v['is_folder']!=1?1:0;//文件夹优先排序
        }
        $items = Sort::arraySort($items,'sort',false,'title');
        return json(['code' => 0, 'msg' => 'ok', 'count' => $total, 'data' => $items, 'path' => $path, 'pids' => $pids]);
    }

    /**
     * 插入
     * @param Request $request
     * @return Response
     * @throws BusinessException
     */
    public function insert(Request $request): Response
    {
        if ($request->method() === 'POST') {
            return $this->insertDo($request);
        }
        return raw_view('io-source/insert',['pid'=>(int)$request->get('pid',0)]);
    }

    /**
     * 更新
     * @param Request $request
     * @return Response
     * @throws BusinessException
    */
    public function update(Request $request): Response
    {
        if ($request->method() === 'POST') {
            return parent::update($request);
        }
        return raw_view('io-source/update');
    }

    /**
     * 文件重命名
     * @param Request $request
     * @return Response
     * @throws BusinessException
     */
    public function rename(Request $request): Response
    {
        if ($request->method() === 'POST') {
            [$id, $data] = $this->updateInput($request);
            if(!$data['title']){
                return $this->json(1,'文件名称不能为空');
            }
//            $oldName = IoSource::where('id',$id)->value('title');
            $oldName = $request->post('oldName');
            $path='/';
            $pid = (int)$data['pid'];
            if($pid>0){
                //有父级，需要拼接上父级目录
                [$path_arr,$pids] = IoSource::getPathInfo($pid);
                $paths = implode('/',$path_arr);
                $newName = $path.$paths.'/'.$data['title'];
                $oldName = $path.$paths.'/'.$oldName;
            }else{
                $newName = $path.$data['title'];
                $oldName = $path.$oldName;
            }
            if(io_rename($newName,$oldName)){
                $this->doUpdate($id, $data);
                return $this->json(0);
            }
            return $this->json(1,'操作失败，文件名已存在');
        }
    }

    //添加方法处理
    protected function insertDo($request){
        $data = $this->insertInput($request);
        if(!isset($data['weight'])){
            $data['weight'] = 0;
        }
        $data['admin_id'] = admin_id();

        if(!$data['title']){
            return $this->json(1,'文件夹名称不能为空');
        }
        $path='/';
        $pid = (int)$data['pid'];
        if($pid>0){
            //有父级，需要拼接上父级目录
            [$path_arr,$pids] = IoSource::getPathInfo($pid);
            $paths = implode('/',$path_arr);
            $path .= $paths.'/';
            $data['pids'] = implode(',',$pids);
        }
        if(io_mkdir($data['title'],$path)){
            $id = $this->doInsert($data);
            $hash = short_id($id);
            $this->model->where('id',$id)->update(['hash'=>$hash]);
            return $this->json(0, 'ok', ['id' => $id]);
        }
        return $this->json(1,'操作失败，文件夹已存在');
    }

    //获取上传数据
    protected function base(Request $request, $relative_dir): array
    {
        $relative_dir = ltrim($relative_dir, '/');
        $file = current($request->file());
        if (!$file || !$file->isValid()) {
            throw new BusinessException('未找到上传文件', 400);
        }

        $base_dir = config('plugin.netdisk.io.base_dir');
        $full_dir = $base_dir . $relative_dir;
        if (!is_dir($full_dir)) {
            mkdir($full_dir, 0777, true);
        }

        $ext = strtolower($file->getUploadExtension());
//        $ext_forbidden_map = ['php', 'php3', 'php5', 'css', 'js', 'html', 'htm', 'asp', 'jsp'];
//        if (in_array($ext, $ext_forbidden_map)) {
//            throw new BusinessException('不支持该格式的文件上传', 400);
//        }

        $file_name = $file->getUploadName();
        if($file_name=="blob"){
            //编辑器word导入
            $relative_path = $relative_dir . '/blob_' . bin2hex(pack('Nn',time(), random_int(1, 65535))) . ".jpg";
        }else{
            $relative_path = $relative_dir . '/' . $file_name;
        }
        $full_path = $base_dir . $relative_path;

        if (file_exists($full_path)) {
            throw new BusinessException('上传失败，文件已存在', 400);
        }

        $file_size = $file->getSize();
        $mime_type = $file->getUploadMimeType();
        $file->move($full_path);
        $image_with = $image_height = 0;
        if ($img_info = getimagesize($full_path)) {
            [$image_with, $image_height] = $img_info;
            $mime_type = $img_info['mime'];
        }
        return [
            'url'     => str_replace(public_path(),'',$full_path),
            'name'     => $file_name,
            'realpath' => $full_path,
            'size'     => $file_size,
            'mime_type' => $mime_type,
            'image_with' => $image_with,
            'image_height' => $image_height,
            'ext' => $ext,
        ];
    }

    //附件入库执行
    protected function doInserts($data){
        $upload = new Upload;
        $upload->admin_id = admin_id();
        $upload->name = $data['name'];
        [
            $upload->url,
            $upload->name,
            $_,
            $upload->file_size,
            $upload->mime_type,
            $upload->image_width,
            $upload->image_height,
            $upload->ext
        ] = array_values($data);
        $upload->category = request()->post('category','');
        $upload->save();
        return $upload->id;
    }

    /**
     * 分享
     * @return Response
     */
    public function share(Request $request): Response
    {
        if ($request->method() === 'POST') {
            $this->model = new IoShare;
            $data = $this->insertInput($request);
            $data['share_hash'] = '';
            $data['expire_at'] = $data['expire_at']==0?null:date("Y-m-d H:i:s",strtotime("+{$data['expire_at']} days"));
            $data['admin_id'] = admin_id();
            if(!$this->model->where('source_id',$data['source_id'])->where('status',1)->exists()){
                //不存在分享
                $id = $this->doInsert($data);
                $share_hash = short_id($id);
                $this->model->where('id',$id)->update(['share_hash'=>$share_hash]);
            }
            return $this->json(0,'操作成功');
        }else{
            $id = (int)$request->get('id',0);
            $info = IoSource::where('id',$id)->first();
            if(IoShare::where('source_id',$id)->where('status',1)->exists()){
                $share = IoShare::where('source_id',$id)->where('status',1)->first()->toArray();
                if($share){
                    if(!is_null($share['expire_at']) && $share['expire_at']<date('Y-m-d H:i:s')){
                        //过期自动清除
                        IoShare::where('id',$share['id'])->update(['status'=>99]);
                    }else{
                        $share['share_hash'] = (request()->header('x-forwarded-proto') ?? 'http').'://'.request()->host().'/'.config('plugin.netdisk.io.route').'/'.$share['share_hash'];
                        //已存在分享
                        return raw_view('io-source/shared',[
                            'info'=>$info,
                            'share'=>$share,
                        ]);
                    }
                }

            }
            return raw_view('io-source/share',[
                'info'=>$info
            ]);
        }
    }

    /**
     * 关闭分享
     * @param Request $request
     * @return Response
     * @throws BusinessException
     */
    public function share_close(Request $request): Response
    {
        $this->model = new IoShare;
        return parent::delete($request);
    }

    /**
     * 删除
     * @param Request $request
     * @return Response
     * @throws BusinessException
     */
    public function delete(Request $request): Response
    {
        $ids = $this->deleteInput($request);

        //同步删除分享
        $share_ids = IoShare::whereIn('source_id',$ids)->where('status',1)->pluck('id');
        if(!$share_ids->isEmpty()){
            $this->model = new IoShare;
            $this->doDelete($share_ids->toArray());
        }

        $base_dir = config('plugin.netdisk.io.base_dir');

        //同步删除文件
        $list = IoSource::whereIn('id',$ids)->get()->toArray();
        foreach($list as $k=>$v){

            $path='/';
            $pid = (int)$v['pid'];
            if($pid>0){
                //有父级，需要拼接上父级目录
                [$path_arr,$pids] = IoSource::getPathInfo($pid);
                $paths = implode('/',$path_arr);
                $path .= $paths.'/';
            }

            $full_dir = $base_dir . md5($v['admin_id']) . $path . $v['title'];
            deleteDirectory($full_dir);
        }

        $this->model = new IoSource;
        $this->doDelete($ids);//删除当前文件

        //删除子目录文件
        foreach($ids as $id){
            $this->model->whereRaw("find_in_set('{$id}',pids)")->delete();//物理删除
        }
        return $this->json(0);

    }


}
