<?php

namespace App\Http\Controllers;

use App\Permission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class PermissionController extends BaseController
{
  public function index()
  {
    return $this->success($this->format(Permission::all()->toArray()));
  }
  public function store(Request $request)
  {
    $rules = [
      'name' => [
        'required',
        'unique:permissions,name'
      ],
      'type' => 'required',
      'pid'=>['required'],
      'status'=>'required'
    ];
    $messages = [
      'name.required' => '名称不能为空',
      'name.unique' => '该名称已存在',
    ];
    $this->validate($request, $rules, $messages);
    $role = new Permission($request->only(['name', 'type','pid','url','status']));
    return $role->save() ? $this->message('添加成功') : $this->failed('添加失败');
  }
  public function show(Permission $permission)
  {
    return $permission ? $this->success($permission) : $this->failed('没有找到此权限');
  }

  public function update(Request $request, Permission $permission)
  {

    $rules = [
      'name' => [
        'required',
        'unique:permissions,name'
      ],
      'type' => 'required',
      'pid'=>['required'],
      'status'=>'required'
    ];
    $messages = [
      'name.required' => '名称不能为空',
      'name.unique' => '该名称已存在',
    ];
    $this->validate($request, $rules, $messages);
    $permission->name = $request->input('name');
    $permission->pid = $request->input('pid');
    $permission->status = $request->input('status');
    $permission->type = $request->input('type');
    $permission->url = $request->input('url');
    return $permission->save() ? $this->message('修改成功') : $this->failed('修改失败');

  }
  public function destroy(Permission $permission)
  {
    //      创建数据库事务
    DB::beginTransaction();
    try {
      //    删除关联
      $permission->roles()->detach();

      //    删除权限
      $res = $permission->delete();

      DB::commit();
      return $res ? $this->message('权限删除成功') : $this->failed('权限删除失败');
    } catch (\Exception $exception) {
      //      遇到异常回滚事务
      DB::rollback();
      return $this->failed('操作失败，请刷新重试');
    }
  }
}