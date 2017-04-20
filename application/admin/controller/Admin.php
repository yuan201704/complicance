<?php
namespace app\admin\controller;
use app\common\model\Admin as AdminModel;
use app\common\controller\AdminBase;



class Admin extends AdminBase
{
    private $model;
    
    public function __construct()
    {

        parent::__construct();        
        $this->model = new AdminModel();
        //部门列表
        $depart_list = db('depart')->order('depart_name')->select(); 
        $depart_tree = subTree($depart_list);
        $depart = departstr($depart_tree);
        $this->view->depart_list=$depart;       
        
    }

    /**
     * 部门用户列表
     */

    public function index()
    {
        $depart_list = $this->view->depart_list;
        //dump($depart_list);exit;
       //$depart_list = $depart_list->toArray();
       foreach($depart_list as $dep)
        {
            $depart_admin = db('admin')->where('depart_id',$dep['depart_id'])->select();
            //dump($depart_admin);exit();
            
            //以depart_id为键名
            foreach($depart_admin as $admin)
            {
                $depart_admins[$dep['depart_id']]['admin_list'][] = $admin;
            }
            //dump($depart_admins);exit();
            
            $depart_admins[$dep['depart_id']][] = $dep;

        }
        //dump($depart_admins);exit();
        $this->view->depart_admins=$depart_admins;
        
        return $this->fetch();
    }

    

    /**
     * 用户列表
     */
    
    public function list()
    {
        
        $map=[];
        //查询条件，按分类和关键词查询
        if(!empty($this->get['kw']))
        {
            $kw = daddslashes($this->get['kw']);
            $map['username'] = ['like',"%{$kw}%"];
        }
        if(!empty($this->get['depart_id']))
        {
            $depart_id = daddslashes($this->get['depart_id']);
            $map['depart_id'] = $depart_id;
        }
        $list = $this->model->where($map)->order('admin_id','DESC')->paginate(10);        
        return $this->fetch('',['list'=>$list]); 
    } 

    /**
     * 添加用户
     */

    public function add()
    {
        if($this->ispost)
        {
                 
            //一个部门职能有一个负责人
            $condition['depart_id']=$this->post['depart_id'];
            $condition['is_head'] = 1;
            $result = db('admin')->where($condition)->find();
            if($result&&($this->post['is_head']==1)) $this->error('该部门已有负责人'); 

            $this->post['addtime']=time();            
            if(is_array($this->post['rights'])) $this->post['rights'] = implode(',',$this->post['rights']); 
            $this->post['password'] = md5($this->post['password']);
            $this->post['password_confirm'] = md5($this->post['password_confirm']);
            //模型排除不在数据表内的字段,allowField(true)
            //模型验证
            $result = $this->model->allowField(true)->validate(true)->save($this->post);
            
            if(false === $result)
            {
                $this->error($this->model->getError());
            } else {
                $this->success('新增成功', 'admin/list');
            }

        }
        return $this->fetch();
    }

    public function edit($admin_id)
    {
        $admin_info = $this->model->where('admin_id',$admin_id)->find();
        $admin_info['right'] = explode(',',$admin_info['right']);
        
        if($this->ispost)
        {
            //没有传递密码，说明不更换密码
            if(!$this->post['password']) 
            {
                unset($this->post['password']);
                unset($admin_info['password']);               
            }else{
                $this->post['password'] = dpassword($this->post['password']);
            }

            //不允许修改用户名
            if($admin_info['username']!==$this->post['username'])
            {
                $this->error('用户名不能修改');
            }
           

            $this->post['edit_time'] = time();            
            $result = $this->model->allowField(true)->validate('Admin.edit')->isUpdate(true)->save($this->post);

            if(false === $result)
            {
                $this->error($this->model->getError());
            } else {
                $this->success('修改成功', '/admin/admin/list');
            }


        }
        
        return $this->fetch('',["admin_info"=>$admin_info]);
        
    }

    public function delete($admin_id)
    {
        $this->model->where('admin_id',$admin_id)->delete() or $this->error('删除失败');
        $this->success('删除成功','/admin/admin/list');
        
    }



}