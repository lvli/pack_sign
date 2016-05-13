<?php
namespace Home\Controller;
use Think\Controller;

class UserController extends Controller {
    public function login(){
        $this->display();
    }

    public function login_up(){
        $user = I('post.user', '', 'string');
        $password = I('post.password', '', 'string');

        if(empty($user)){
            $this->error('用户名不能为空');
        }

        if(empty($password)){
            $this->error('密码不能为空');
        }

        $status = D('User')->login($user, $password);
        if($status){
            $this->success('登录成功', '/Home/Index/index');
        }else{
            $this->error('登录失败');
        }
    }

    public function register(){
        $this->display();
    }

    public function register_up(){
        $user = I('post.user', '', 'string');
        $password = I('post.password', '', 'string');
        $password_reply = I('post.password_reply', '', 'string');

        if(empty($user)){
            $this->error('用户名不能为空');
        }

        if(empty($password)){
            $this->error('密码不能为空');
        }

        if($password != $password_reply){
            $this->error('两次密码输入不一致');
        }

        $status = D('User')->oldUser($user);
        if($status){
            $this->error('该管理员已存在');
        }

        $status = D('User')->register($user, $password);
        if($status){
            $this->success('注册成功', '/Home/Index/index');
        }else{
            $this->error('注册失败');
        }
    }

    public function edit(){
        $this->display();
    }

    public function edit_up(){
        $password_old = I('post.password_old', '', 'string');
        $password = I('post.password', '', 'string');
        $password_reply = I('post.password_reply', '', 'string');

        if(empty($password_old)){
            $this->error('旧密码不能为空');
        }

        if(empty($password)){
            $this->error('新密码不能为空');
        }

        if($password_old == $password){
            $this->error('旧密码和新密码相同');
        }

        $status = D('User')->oldPasssword($password_old);
        if(!$status){
            $this->error('旧密码输入错误');
        }

        if($password != $password_reply){
            $this->error('两次密码输入不一致');
        }

        $status = D('User')->edit($password);
        if($status){
            $this->success('修改成功', '/Home/Index/index');
        }else{
            $this->error('修改失败');
        }
    }

    public function logout(){
        D('User')->logout();
        $this->success('注销成功', '/Home/Index/index');
    }
}