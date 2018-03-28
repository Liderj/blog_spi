<?php

namespace App\Http\Controllers;

use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserController extends BaseController
{
//   所有用户
  public function index(Request $request)
  {
    $page_size = $request->query('page_size', 10);//每页条数
    $search = $request->query('search', '');//关键字搜索
    $status = $request->query('status', 1);//用户状态
    $type = $request->query('type', 1);//用户类型
//    查询结果分页
    $res = User::where([
      ['nickname', 'like', '%' . $search . '%'],
      ['status', '=', $status],
      ['type', '=', $type]
    ])
      ->orWhere(
        [
          ['mobile', 'like', '%' . $search . '%'],
          ['status', '=', $status],
          ['type', '=', $type]
        ]
      )
      ->orderBy('created_at', 'desc')
      ->paginate($page_size);
    return $this->success($res);
  }

//  用户详情
  public function show(User $user)
  {
    return $this->success($user);
  }

//  更新用户信息
  public function update(Request $request, User $user)
  {

    $rules = [
      'password' => 'required',
      'nickname' => 'required',
    ];
    $messages = [
      'nickname.required' => '昵称不能为空',
      'nickname.unique' => '昵称已存在',
    ];
    $this->validate($request, $rules, $messages);

    if(User::where('nickname',$request->input('nickname'))->count()&&$user->nickname !=$request->input('nickname')){
       return $this->failed('此昵称已存在');
    }

    $params = null;
    //用户修改自己的信息
    if (Auth::user()->id == $user->id) {
      $old_pwd = Hash::check( $request->input('old_password'),Auth::user()->password);
      if (!$old_pwd) {
        return $this->failed('原密码输入错误');
      }
      $params = $request->only(['password', 'nickname', 'avatar', 'sex']);
    }
    //超管修改用户的信息
    else if ( Auth::user()->id == 1) {
      $params = $request->only(['password', 'nickname', 'avatar', 'sex', 'status', 'roles', 'type']);
    }else{
      return $this->failed('仅超级管理员可修改其他用户信息');
    }
    $params['password'] = bcrypt($params['password']);
    foreach ($params as $key => $v) {
      if ($v != $user[$key]) {
        $user[$key] = $v;
      }
    }
    return $user->save() ? $this->message('修改成功') : $this->failed('修改失败');

  }

//  新建管理员
  public function store(Request $request)
  {

    $rules = [
      'mobile' => [
        'required',
        'unique:users,mobile'
      ],
      'password' => 'required',
      'nickname' => [
        'required',
        'unique:users,nickname'
      ],
    ];
    $messages = [
      'mobile.required' => '手机号不能为空',
      'mobile.unique' => '该手机号已存在',
      'nickname.required' => '昵称不能为空',
      'nickname.unique' => '该昵称已存在',
      'password.required' => '密码不能为空'
    ];
    $this->validate($request, $rules, $messages);
    $user = new User($request->only(['mobile', 'password', 'nickname', 'avatar', 'sex', 'status', 'roles']));
    $user->type = 0;
    $user->password = bcrypt($user->password);
    return $user->save() ? $this->message('添加成功') : $this->failed('添加失败');
  }

//  删除用户
  public function destroy(User $user)
  {
    if( Auth::user()->type !=0){
      return $this->failed('仅管理员可删除用户');
    }
    if ($user->type != 0 && Auth::user()->id != 1) {
      return $this->failed('仅超级管理员可删除管理员');
    }
    if ($user->id == 1) {
      return $this->failed('超级管理员不可以删除');
    }
    //      创建数据库事务
    DB::beginTransaction();
    try {
      //    删除用户
      $res = $user->delete();
      DB::commit();
      return $res ? $this->message('删除成功') : $this->failed('删除失败');
    } catch (\Exception $exception) {
      //      遇到异常回滚事务
      DB::rollback();
      return $this->failed('操作失败，请刷新重试');
    }
  }

//  用户状态更改
  public function frozen(User $user)
  {
    if( Auth::user()->type !=0){
      return $this->failed('仅管理员可修改用户状态');
    }
    if ($user->type != 0 && Auth::user()->id != 1) {
      return $this->failed('仅超级管理员可修改管理员状态');
    }
    $user->status == 0 ? $user->status = 1 : $user->status = 0;
    return $user->save() ? $this->message('修改状态成功') : $this->failed('修改状态失败');

  }
}
