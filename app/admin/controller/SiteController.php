<?php

namespace app\admin\controller;

use app\admin\services\client\ClientServices;
use app\admin\services\download\DownloaderServices;
use app\admin\services\site\LayuiTemplate;
use app\admin\services\SitesServices;
use app\common\HasBackupRecovery;
use app\common\HasDelete;
use app\common\HasValidate;
use app\model\Site;
use Iyuu\ReseedClient\Client;
use Ledc\Container\App;
use plugin\admin\app\controller\Crud;
use support\Cache;
use support\exception\BusinessException;
use support\Log;
use support\Request;
use support\Response;
use Throwable;

/**
 * 站点设置
 */
class SiteController extends Crud
{
    use HasDelete, HasValidate, HasBackupRecovery;

    /**
     * 无需登录及鉴权的方法
     * @var array
     */
    protected $noNeedLogin = ['helper'];

    /**
     * @var Site
     */
    protected $model = null;

    /**
     * 构造函数
     * @return void
     */
    public function __construct()
    {
        $this->model = new Site;
    }

    /**
     * IYUU浏览器助手
     * @param Request $request
     * @return Response
     */
    public function helper(Request $request): Response
    {
        try {
            $system_iyuu_helper = SitesServices::getIyuuHelper();
            $iyuu_helper = $request->header('x-iyuu-helper', '');
            if (empty($iyuu_helper) || Cache::has(SitesServices::SYSTEM_IYUU_HELPER)) {
                return $this->fail('非法请求');
            }

            if (!hash_equals($system_iyuu_helper, $iyuu_helper)) {
                Cache::set(SitesServices::SYSTEM_IYUU_HELPER, time(), 600);
                return $this->fail('非法请求验证失败！');
            }

            Cache::delete(SitesServices::SYSTEM_IYUU_HELPER);
            if ($request->method() === 'POST') {
                // 更新
                return $this->success();
            } else {
                // 获取
                [$where, $format, $limit, $field, $order] = $this->selectInput($request);
                $query = $this->doSelect($where, $field, $order);
                return $this->doFormat($query, $format, 1000);
            }
        } catch (Throwable $e) {
            return $this->fail($e->getMessage());
        }
    }

    /**
     * 浏览
     * @return Response
     */
    public function index(): Response
    {
        return view('site/index');
    }

    /**
     * 查询
     * @param Request $request
     * @return Response
     * @throws BusinessException
     */
    public function find(Request $request): Response
    {
        $primary_key = $this->model->getKeyName();
        $id = $request->get($primary_key);
        $model = $this->model->find($id);
        if (!$model) {
            throw new BusinessException('记录不存在', 2);
        }

        return $this->success('ok', $model->toArray());
    }

    /**
     * 插入
     * @param Request $request
     * @return Response
     */
    public function insert(Request $request): Response
    {
        if ($request->method() === 'POST') {
            return $this->success();
        }
        return view('site/insert');
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
            [$id, $data] = $this->updateInput($request);
            Site::backupToJson($this->model);
            // trim过滤首尾空白字符 david 2024年11月9日23:45:25
            $options = $data['options'] ?? [];
            if (!empty($options)) {
                $data['options'] = array_map(function ($item) {
                    return is_string($item) || is_numeric($item) ? trim($item) : $item;
                }, $options);
            }
            $this->doUpdate($id, $data);
            return $this->json(0);
        }

        $form = LayuiTemplate::generate($request->get('site', ''));
        return view('site/update', [
            'html' => $form->html(),
            'js' => $form->js(),
        ]);
    }

    /**
     * 获取合作站点
     * @param Request $request
     * @return Response
     */
    public function getRecommendSites(Request $request): Response
    {
        try {
            check_iyuu_token(iyuu_token());

            $reseedClient = new Client(iyuu_token());
            $recommend = $reseedClient->recommend();

            return $recommend ? $this->success('ok', $recommend['list']) : $this->fail('IYUU服务器无响应');
        } catch (Throwable $exception) {
            return $this->fail($exception->getMessage());
        }
    }

    /**
     * 合作站绑定
     * @param Request $request
     * @return Response
     */
    public function bind(Request $request): Response
    {
        if ($request->method() === 'POST') {
            try {
                $rule = [
                    'token|IYUU_TOKEN' => 'require|max:60',
                    'id|用户数字ID' => 'require|number',
                    'site|站点' => 'require',
                    'passkey|绑定密钥' => 'require',
                ];
                $data = [
                    'token' => iyuu_token(),
                    'id' => $request->post('id'),
                    'site' => $request->post('site'),
                    'passkey' => sha1($request->post('passkey', '')), // 避免泄露用户密钥passkey
                ];
                $this->validate($data, $rule);

                // 新版验证依赖的sid字段 2024年4月24日
                $siteModel = Site::uniqueSite($data['site']);
                if (!$siteModel) {
                    return $this->fail('客户端未查询到站点数据');
                }

                $data['sid'] = $siteModel->sid;
                Log::info('合作站绑定：', $data);
                $reseedClient = new Client(iyuu_token());
                $reseedClient->bind($data);
                return $this->success('绑定成功');
            } catch (Throwable $throwable) {
                return $this->fail($throwable->getMessage());
            }
        }

        return view('site/bind');
    }

    /**
     * 测试站点连通性
     * @param Request $request
     * @return Response
     */
    public function test_sid(Request $request): Response
    {
        if ($request->method() === 'POST') {
            try {
                $rule = [
                    'sid|站点ID' => 'require',
                    'torrent_id|站点种子ID' => 'require|number',
                    'group_id|站点种子分组ID' => 'require|number',
                ];
                $data = $request->post();
                $this->validate($data, $rule);
                $torrent_id = $data['torrent_id'];

                /** @var DownloaderServices $downloadServices */
                $downloadServices = App::pull(DownloaderServices::class);
                $response = $downloadServices->download($data);
                $model = ClientServices::getDefaultClient();
                $result = ClientServices::sendClientDownloader($response, $model);
                return $this->success("站点种子：{$torrent_id} 添加下载成功，返回值：" . json_encode($result, JSON_UNESCAPED_UNICODE));
            } catch (Throwable $throwable) {
                return $this->fail($throwable->getMessage());
            } finally {
                clear_instance_cache();
            }
        }

        return view('site/test-sid');
    }

    /**
     * 导入旧版配置
     * @param Request $request
     * @return Response
     */
    public function import(Request $request): Response
    {
        $file = $request->file('file');
        $list = json_decode(file_get_contents($file->getPathname()), true);
        $map = [
            'id' => 'uid',
            'passkey' => 'passkey',
            'torrent_pass' => 'torrent_pass',
            'authkey' => 'authkey',
            'torrent_key' => 'torrent_key',
            'rss_key' => 'rss_key',
            'rsskey' => 'rsskey',
        ];
        foreach ($list as $site => $item) {
            if ($model = Site::uniqueSite($site)) {
                $cookie = $item['cookie'] ?? '';
                if ($cookie) {
                    $model->cookie = $cookie;
                }

                $mirror = $item['mirror'] ?? '';
                if ($mirror) {
                    $model->mirror = $mirror;
                }

                $options = [];
                foreach ($map as $k => $v) {
                    $value = $item[$k] ?? '';
                    if ($value) {
                        $options[$v] = $value;
                    }
                }

                if ($options && in_array($site, [
                        'ttg', 'redleaves', 'pter', 'pt', 'hdsky',
                        'dicmusic', 'greatposterwall', 'audiences', 'hdhome', 'pthome',
                        'zhuque', 'ourbits', 'chdbits', 'piggo', 'zmpt',
                        'agsvpt', 'hdfans'
                    ], true)) {
                    $options['limit']['count'] = 20;
                    $options['limit']['sleep'] = 5;
                }

                if (!empty($options)) {
                    $model->options = $options;
                }
                $model->save();
            }
        }
        return $this->success();
    }

    /**
     * 同步站点
     * @param Request $request
     * @return Response
     */
    public function sync(Request $request): Response
    {
        try {
            check_iyuu_token(iyuu_token());
            SitesServices::sync();
            return $this->success();
        } catch (Throwable $exception) {
            return $this->fail($exception->getMessage());
        }
    }

    /**
     * 格式化下拉列表
     * @param $items
     * @return Response
     */
    protected function formatSelect($items): Response
    {
        $simple = (bool)\request()->input('simple', false);
        $value = \request()->input('value', 'site');
        if (!in_array($value, ['id', 'sid', 'site'], true)) {
            return $this->fail('非法value参数');
        }

        $formatted_items = [];
        /** @var Site $item */
        foreach ($items as $item) {
            $more = $simple ? '' : ($item->options ? '' : ' | 未配置') . ($item->disabled ? ' | 禁用' : '');
            $formatted_items[] = [
                'name' => ($item->nickname ?? '') . $item->site . $more,
                'value' => $item->{$value}
            ];
        }
        return $this->success('ok', $formatted_items);
    }

    /**
     * 对用户输入表单过滤
     * @param array $data
     * @return array
     * @throws BusinessException
     */
    protected function inputFilter(array $data): array
    {
        $table = config('plugin.admin.database.connections.mysql.prefix') . $this->model->getTable();
        $allow_column = $this->model->getConnection()->select("desc `$table`");
        if (!$allow_column) {
            throw new BusinessException('表不存在', 2);
        }
        $columns = array_column($allow_column, 'Type', 'Field');
        foreach ($data as $col => $item) {
            if (!isset($columns[$col])) {
                unset($data[$col]);
                continue;
            }
            // 非字符串类型传空则为null
            if ($item === '' && !str_contains(strtolower($columns[$col]), 'varchar') && !str_contains(strtolower($columns[$col]), 'text')) {
                $data[$col] = null;
            }
        }
        if (empty($data['created_at'])) {
            unset($data['created_at']);
        }
        if (empty($data['updated_at'])) {
            unset($data['updated_at']);
        }
        return $data;
    }
}
