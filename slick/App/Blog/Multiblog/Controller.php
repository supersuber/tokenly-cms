<?php
namespace App\Blog;
/*
 * @module-type = dashboard
 * @menu-label = My Blogs
 * 
 * */
use Util, App\Tokenly;
class Multiblog_Controller extends \App\ModControl
{
    public $data = array();
    public $args = array();
    
    function __construct()
    {
        parent::__construct();
        $this->model = new Multiblog_Model;
    }
    
    public function init()
    {
		$output = parent::init();
		$this->data['perms'] = \App\Meta_Model::getUserAppPerms($this->data['user']['userId'], 'blog');
			
        if(isset($this->args[2])){
			switch($this->args[2]){
				case 'view':
					$output = $this->showBlogs();
					break;
				case 'add':
					$output = $this->addBlog();
					break;
				case 'edit':
					$output = $this->editBlog();
					break;
				case 'delete':
					$output = $this->deleteBlog();
					break;
				case 'remove-role':
					$output = $this->removeBlogRole();
					break;
				default:
					$output = $this->showBlogs();
					break;
			}
		}
		else{
			$output = $this->showBlogs();
		}
		$output['template'] = 'admin';
        $output['perms'] = $this->data['perms'];	
        return $output;
    }
    
    private function showBlogs()
    {
		$output = array('view' => 'list');
		$wheres = array('siteId' => $this->data['site']['siteId']);		
		$output['blogList'] = $this->model->getAll('blogs', $wheres);
		foreach($output['blogList'] as &$blog){
			$roles = $this->model->getBlogUserRoles($blog['blogId']);
			$roleList = array();
			foreach($roles as $role){
				if($role['userId'] == 0){
					continue;
				}		
				if(!isset($roleList[$role['type']])){
					$roleList[$role['type']] = array();
				}
				$roleList[$role['type']][] = $role['userId'];
			}
			$blog['roles'] = $roleList;
		}
		return $output;
	}
	
	private function addBlog()
	{
		$output = array('view' => 'form');
		if(!$this->data['perms']['canCreateBlogs']){
			$output['view'] = '403';
			return $output;
		}
		$output['form'] = $this->model->getBlogForm($this->data['site']['siteId']);

		if(!$this->data['perms']['canChangeBlogOwner']){
			$output['form']->remove('userId');
		}

		$output['formType'] = 'Add';
		
		if(posted()){
			$data = $output['form']->grabData();
			$data['siteId'] = $this->data['site']['siteId'];
			if(!$this->data['perms']['canChangeBlogOwner']){
				$data['userId'] = $this->data['user']['userId'];
			}
			try{
				$add = $this->model->addBlog($data);
			}
			catch(\Exception $e){
				$output['error'] = $e->getMessage();
				$add = false;
			}
			
			if($add){
				Util\Session::flash('blog-message', 'Blog created!', 'success');	
				redirect($this->site.$this->moduleUrl);
			}
		}
		return $output;
	}
	
	private function editBlog()
	{
		if(!isset($this->args[3])){
			redirect($this->site);
		}
		
		$getBlog = $this->model->get('blogs', $this->args[3]);
		if(!$getBlog){
			redirect($this->site.$this->moduleUrl);
		}
		
		$output = array('view' => 'form');
		$output['blogRoles'] = $this->model->getBlogUserRoles($getBlog['blogId']);
		$is_admin = false;
		foreach($output['blogRoles'] as $role){
			if($role['userId'] == $this->data['user']['userId'] AND $role['type'] == 'admin'){
				$is_admin = true;
			}
		}
		if(!$is_admin AND !$this->data['perms']['canManageAllBlogs'] AND $getBlog['userId'] != $this->data['user']['userId']){
			$output['view'] = '403';
			return $output;
		}
		
		$output['form'] = $this->model->getBlogForm($this->data['site']['siteId']);
		if(!$this->data['perms']['canChangeBlogOwner']){
			$output['form']->remove('userId');
		}

		$output['formType'] = 'Edit';
		$output['roleForm'] = $this->model->getBlogRoleForm();
		$output['getBlog'] = $getBlog;
		
		if(posted()){
			if(isset($_POST['roleUserId']) AND isset($_POST['roleType'])){
				try{
					$add = $this->model->addBlogRole($getBlog['blogId'], $_POST['roleUserId'], $_POST['roleType'], $this->data['user']);
				}
				catch(\Exception $e){
					$add = false;
					$output['error'] = $e->getMessage();
				}
				if($add){
					Util\Session::flash('blog-message', 'Blog role added!', 'success');	
					redirect($this->site.$this->moduleUrl.'/edit/'.$getBlog['blogId']);
				}							
			}
			else{			
				$data = $output['form']->grabData();
				$data['siteId'] = $this->data['site']['siteId'];
				try{
					$edit = $this->model->editBlog($this->args[3], $data);
				}
				catch(\Exception $e){
					$output['error'] = $e->getMessage();
					$edit = false;
				}
				if($edit){
					Util\Session::flash('blog-message', 'Blog edited!', 'success');	
					redirect($this->site.$this->moduleUrl);
				}				
			}			
		}
		$output['form']->setValues($getBlog);
		return $output;
	}
	
	private function deleteBlog()
	{
		if(isset($this->args[3])){
			$getBlog = $this->model->get('blogs', $this->args[3]);
			if($getBlog){
				if($this->data['perms']['canCreateBlogs'] OR $getBlog['userId'] == $this->data['user']['userId']){
					$delete = $this->model->delete('blogs', $this->args[3]);
					Util\Session::flash('blog-message', 'Blog deleted.', 'success');
				}	
			}
		}
		redirect($this->site.$this->moduleUrl);
	}
	
	private function removeBlogRole()
	{		
		if(!isset($this->args[3]) OR !isset($this->args[4])){
			redirect($this->site.$this->moduleUrl);
		}
		
		$getBlog = $this->model->get('blogs', $this->args[3]);
		$getBlogRole = $this->model->get('blog_roles', $this->args[4]);
		if(!$getBlog OR !$getBlogRole){
			redirect($this->site.$this->moduleUrl);
		}

		$getUser = false;
		if($getBlogRole['userId'] != 0){
			$getUser = $this->model->get('users', $getBlogRole['userId']);
		}
		
		$inventory = new Tokenly\Inventory_Model;		
		$getToken = false;
		if($getBlogRole['token'] != ''){
			$getToken = $inventory->getAssetData($getBlogRole['token']);
		}
		
		$output['blogRoles'] = $this->model->getBlogUserRoles($getBlog['blogId']);
		$is_admin = false;
		foreach($output['blogRoles'] as $role){
			if($role['userId'] == $this->data['user']['userId'] AND $role['type'] == 'admin'){
				$is_admin = true;
			}
		}		
		
		if(!$is_admin AND !$this->data['perms']['canManageAllBlogs'] AND $getBlog['userId'] != $this->data['user']['userId']){
			redirect($this->site.$this->moduleUrl.'/edit/'.$getBlog['blogId']);
		}				
			
		$delete = $this->model->delete('blog_roles', $getBlogRole['userRoleId']);
		
		if($delete){
			if($getBlogRole['userId'] == 0 AND $getBlogRole['token'] != ''){
				//token entry, remove TCA rules
				$this->model->delete('token_access', 'blog-role:'.$getBlogRole['userRoleId'], 'reference');
			}
			elseif($getUser){
				$other_roles = $this->model->getAll('blog_roles', array('userId' => $getUser['userId']));
				$foundEditor = false;
				$foundOwner = false;
				foreach($other_roles as $role){
					if($role['userRoleId'] != $getBlogRole['userRoleId']){
						if($role['type'] == 'editor'){
							$foundEditor = true;
						}
						elseif($role['type'] == 'admin'){
							$foundOwner = true;
						}
					}
				}
				
				if(!$foundEditor){
					$editorGroup = $this->model->get('groups', 'blog-editor', array(), 'slug');
					if($editorGroup){
						$findGroups = $this->model->getAll('group_users', array('groupId' => $editorGroup['groupId'], 'userId' => $getUser['userId']));
						foreach($findGroups as $group){
							$this->model->delete('group_users', $group['groupUserId']);
						}
					}
				}
				
				if(!$foundOwner){
					$ownerGroup = $this->model->get('groups', 'blog-owner', array(), 'slug');
					if($ownerGroup){
						$findGroups = $this->model->getAll('group_users', array('groupId' => $ownerGroup['groupId'], 'userId' => $getUser['userId']));
						foreach($findGroups as $group){
							$this->model->delete('group_users', $group['groupUserId']);
						}
					}
				}
			}
			
		}
		redirect($this->site.$this->moduleUrl.'/edit/'.$getBlog['blogId']);
	}
}

