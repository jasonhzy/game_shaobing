<?php
	class GiftAction extends CommonAction{
		
		public function giftInfo(){
			
			$Gift=M('Gift');
			
			
			import('ORG.Util.Page');// 导入分页类
			$count      = $Gift->count();// 查询满足要求的总记录数
			$Page       = new Page($count,C('GIFT_PAGE_COUNT'));// 实例化分页类 传入总记录数和每页显示的记录数
			$show       = $Page->show();// 分页显示输出
			// 进行分页数据查询 注意limit方法的参数要使用Page类的属性
			$list = $Gift->order('id asc')->limit($Page->firstRow.','.$Page->listRows)->select();
			$this->assign('data',$list);// 赋值数据集
			
			$this->assign('page',$show);// 赋值分页输出
			
			$sum = $Gift->field('SUM(chance_value) as sum_chance')->find();
			$this->assign('sum_chance',$sum['sum_chance']);// 赋值概率和
			$this->display(); // 输出模板
		}
		

		public function addGift(){
			
			$this->display();
		}
		
		public function doAdd(){
			
			$Gift=M('Gift');
			
			$Gift->create();

			$lastId=$Gift->add();
			if($lastId){
				$this->success('添加礼品成功','giftInfo');
			}else{
				$this->error('添加礼品失败');
			}
		}
		
		public function doDel(){
			$Gift = M('Gift');
		
			$id = $_GET['id'];
		
			$count = $Gift->delete($id);
			if($count>0){
				$this->success('删除礼品成功');
			}else {
				$this->error('删除礼品失败');
			}
		}
		
		/*
		 *	显示修改页面
		* */
		public function modifyGift(){
			$id=$_GET['id'];

			$m=M('Gift');
			$arr=$m->find($id);

			$this->assign('data',$arr);
			$this->display();
		}
		
		public function doUpdate(){
			$m=M('Gift');
			$data['id']=$_POST['id'];
			
			$data['gift_name']=$_POST['gift_name'];
			$data['score_low']=$_POST['score_low'];
			$data['score_high']=$_POST['score_high'];
			$data['gift_num']=$_POST['gift_num'];
			$data['chance_value']=$_POST['chance_value'];
			$data['day_num']=$_POST['day_num'];
			
			$count=$m->save($data);
			if($count>0){
				$this->success('修改礼品成功','giftInfo');
			}else{
				$this->error('修改礼品失败');
			}
		}
		
		
	}
?>
