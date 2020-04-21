<?php

namespace app\api\controller\v1;

use app\api\controller\AdminToken;
use app\api\model\Admin;
use app\api\model\Role;
use think\exception\DbException;
use think\Request;
use app\api\controller\Api;
use think\Validate;

class AdminController extends Api
{
    /**
     * login方法不需要鉴权
     * [*]所有方法都不需要鉴权
     */
    protected $noAuth = ['login'];

    /**
     * 获取管理员列表
     */
    public function index()
    {
        try {
            $where = $data = [];
            $pagenum = $this->request->param('pagenum', 1);
            $pagesize = $this->request->param('pagesize', 15);
            $keyword = $this->request->param('query');
            $where[] = ['id', 'neq', 1];
            if ($keyword) {
                $where[] = ['admin_name', 'like', "%{$keyword}%"];
            }
            $field = 'id,role_id,admin_name,nickname,mobile,email,status,last_login_ip,last_login_time,create_time';
            $data['data'] = Admin::where($where)->field($field)->page($pagenum)->limit($pagesize)->select();
            $data['count'] = Admin::where($where)->count('id');
            $this->returnMsg(200, '获取管理员列表完成', $data);
        } catch (DbException $e) {
            $this->returnMsg(400, $e->getMessage());
        }
    }

    /**
     * 获取管理员详情
     * @param $id
     */
    public function read($id)
    {
        try {
            $field = 'id,role_id,admin_name,nickname,mobile,email';
            $adminInfo = Admin::field($field)->find($id);
            $this->returnMsg(200, '获取数据成功', $adminInfo);
        } catch (DbException $e) {
            $this->returnMsg(400, $e->getMessage());
        }
    }

    /**
     * 添加管理员
     * @param Request $request
     */
    public function save(Request $request)
    {
        $data = $request->post();
        if (isset($data['id'])){
            $admin=new Admin();
            $res=$admin->updateAdmin($data,$data['id']);
            if ($res[0] === 0) $this->returnMsg(0, $res[1]);
            $this->returnMsg(200, '更新成功');
        }
        $validate = new Validate(['mobile' => 'require', 'password' => 'require', 'role_id' => 'require']);
        $validate->message(['mobile.require' => '请输入手机号!', 'password.require' => '请输入密码!', 'role_id.require' => '请设置角色']);

        $data['admin_password'] = trim(md5($data['password']));

        if (empty(Role::get($data['role_id'])))
            $this->returnMsg(0, '角色不存在');

        if (Admin::where('mobile', 'eq', $data['mobile'])->count() > 0)
            $this->returnMsg(0, '此手机号码已存在');

        $data['last_login_ip'] = '';
        $data['admin_password'] = password_hash($data['admin_password'], PASSWORD_DEFAULT);

        if (!Admin::create($data)) {
            $this->returnMsg(0, '添加管理员失败');
        }

        $this->returnMsg(200, '添加管理员成功');
    }

    /**
     * 更新资源 put请求
     * @param Request $request
     * @param $id
     * @throws DbException
     */
    public function update(Request $request, $id)
    {
        $data = $request->post();
        $admin=new Admin();
        $res=$admin->updateAdmin($data,$id);
        if ($res[0] === 0) $this->returnMsg(0, $res[1]);
        $this->returnMsg(200, '更新成功');
    }

    /**
     * 删除指定管理员
     * @param $id
     */
    public function delete($id)
    {
        try {
            if (Admin::where(['id' => $id])->delete()) {
                $this->returnMsg('200', '删除成功');
            }
            $this->returnMsg('400', '删除失败');
        } catch (\Exception $e) {
            $this->returnMsg('400', '删除失败' . $e->getMessage());
        }
    }


    public function address($id)
    {
        echo "address-";
        echo $id;
    }

    /**
     * 用户登录
     * @param Request $request
     */
    public function login(Request $request)
    {
        $validate = new Validate(['username' => 'require', 'password' => 'require']);
        $validate->message(['username.require' => '请输入手机号,邮箱或用户名!', 'password.require' => '请输入您的密码!']);

        $param = $request->param();

        if (!$validate->check($param)) {
            $this->returnMsg('400', $validate->getError());
        }

        $where = [];
        if ($validate->is($param['username'], 'email')) {
            $where['email'] = $param['username'];
        } else if (preg_match('/(^(13\d|15[^4\D]|17[013678]|18\d)\d{8})$/', $param['username'])) {
            $where['mobile'] = $param['username'];
        } else {
            $where['admin_name'] = $param['username'];
        }
        $model = new Admin();
        $info = $model->get($where);
        if (!$info) $this->returnMsg(0, '用户不存在');
        if ($info['status'] === 0) $this->returnMsg(0, '您已被拉黑');
        if (!password_verify(md5($param['password']), $info['admin_password'])) $this->returnMsg(0, '密码不正确');

        $ip = get_client_ip($type = 0, $adv = false);
        $up['last_login_ip'] = $ip;
        $up['last_login_time'] = time();
        $updateUser = $model->save($up, ['id' => $info['id']]);

        if ($updateUser === 0) {
            $this->returnMsg(0, "登录失败!");
        }
        $info = [
            'uid' => $info['id'],
            'role_id' => $info['role_id'],
            'username' => $info['admin_name'],
            'nickname' => $info['nickname'],
            'mobile' => $info['mobile'],
            'email' => $info['email'],
            'last_login_ip' => $info['last_login_ip'],
            'last_login_time' => $info['last_login_time'],
        ];
        $adminToken = new AdminToken();
        $token = $adminToken->getToken($info);//生成token

        $this->returnMsg(200, '登录成功', ['token' => $token, 'info' => $info]);
    }
}
