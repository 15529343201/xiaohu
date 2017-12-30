<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Answer extends Model
{
    public function add(){
      /*添加回答API*/
      if(!user_ins()->is_logged_in())
        return ['status'=>0,'msg'=>'login required'];

      /*检查参数中是否存在question_id和content*/
      if(!rq('question_id') || !rq('content'))
        return ['status'=>0,'msg'=>'question_id and content are required'];
 
      /*检查问题是否存在*/
      $question=question_ins()->find(rq('question_id'));
      if(!$question)
        return ['status'=>0,'msg'=>'question not exists'];

      /*检查是否重复回答*/
      $answered=$this
        ->where(['question_id'=>rq('question_id'),'user_id'=>session('user_id')])
        ->count();

      if($answered)
        return ['status'=>0,'msg'=>'duplicate answers'];
 
      /*保存数据*/
      $this->content=rq('content');
      $this->question_id=rq('question_id');
      $this->user_id=session('user_id');

      return $this->save() ?
        ['status'=>1,'id'=>$this->id]:
        ['status'=>0,'msg'=>'db insert failed'];
    }
    
    /*更新回答API*/
    public function change(){
      if(!user_ins()->is_logged_in())
        return ['status'=>0,'msg'=>'login required'];

      if(!rq('id')||!rq('content'))
        return ['status'=>0,'msg'=>'id and content are required'];

      $answer=$this->find(rq('id'));
      if($answer->user_id!=session('user_id'))
        return ['status'=>0,'msg'=>'permission denied'];

      $answer->content=rq('content');
      return $answer->save() ?
        ['status'=>1]:
        ['status'=>0,'msg'=>'db update failed'];
   }

   /*查看回答API*/
   public function read(){
     if(!rq('id') && !rq('question_id'))
       return ['status'=>0,'msg'=>'id or question_id is required'];

     if(rq('id')){
       /*单个回答查看*/
       $answer=$this->find(rq('id'));
       if(!$answer)
         return ['status'=>0,'msg'=>'answer not exists'];
       return ['status'=>1,'data'=>$answer];
     }
  
     /*在检查回答前,检查问题是否存在*/
     if(!question_ins()->find(rq('question_id')))
       return ['status'=>0,'msg'=>'question not exists'];

     /*同一问题下的所有回答*/
     $answers=$this
       ->where('question_id',rq('question_id'))
       ->get()
       ->keyBy('id');

     return ['status'=>1,'data'=>$answers];
  }
}
