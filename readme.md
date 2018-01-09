## 注册API的实现
`cd /root/project/xiaohu_source/xiaohu/routes/web.php`
```php
<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::any('api',function(){
    return ['version' => 0.1];
});

Route::any('api/user',function(){
    $user=new App\User;
    return $user->signup();
});
```
创建User model:`php artisan make:model User`<br>
```php
<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    public function signup()
    {
        return 'signup';
    }
}
```
启动服务器:`php -S localhost:8000 -t xiaohu/public`<br>
浏览器访问:localhost:8000/api/user<br>
User.php<br>
```php
<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Request;
use Hash;
class User extends Model
{
    public function signup()
    {
        $username=Request::get('username');
        $password=Request::get('password');
        /*检查用户名是否为空*/
        if(!($username&&$password))
          return ['status' =>0,'msg'=>'用户名和密码皆不可为空'];
        /*检查用户名是否存在*/
        $user_exists=$this
          ->where('username',$username)
          ->exists();

        if($user_exists)
          return ['status'=>0,'msg'=>'用户名已存在'];

        /*加密密码*/
        $hashed_password=bcrypt($password);
        /*存入数据库*/
        $user=$this;
        $user->password=$hashed_password;
        $user->username=$username;
        if($user->save())
        {
          return ['status'=>1,'id'=>$user->id];
        }else{
          return ['status'=>0,'msg'=>'db insert failed'];
        }
    }
}
```
## 登录API的实现
`[root@localhost xiaohu]# cat routes/web.php`
```php
Route::any('api/login',function(){
    return user_ins()->login();
});
```
session配置:`config/session.php`可以配置,我们先保持默认不做修改<br>
User.php:<br>
```php
/*登录api*/
    public function login(){
      /*检查用户名和密码是否存在*/
      $has_username_and_password=$this->has_username_and_password();
      if(!$has_username_and_password)
        return ['status'=>0,'msg'=>'用户名和密码皆不可为空!'];
      $username=$has_username_and_password[0];
      $password=$has_username_and_password[1];

      /*检查用户是否存在*/
      $user=$this->where('username',$username)->first();
      if(!$user)
        return ['status'=>0,'msg'=>'用户不存在'];

      /*检查密码是否正确*/
      $hashed_password=$user->password;
      if(!Hash::check($password,$hashed_password))
        return ['status'=>0,'msg'=>'密码有误'];

      /*将用户信息写入Session*/
      session()->put('username',$user->username);
      session()->put('user_id',$user->id);

      return ['status'=>1,'id'=>$user->id];

    }


    public function has_username_and_password(){
        $username=Request::get('username');
        $password=Request::get('password');
        /*检查用户名是否为空*/
        if($username&&$password)
          return [$username,$password];
        return false;
    }
```
## 登出API
```php
Route::any('api/logout',function(){
    return user_ins()->logout();
});
Route::any('test',function(){
    dd(user_ins()->is_logged_in());
});
```
User.php:<br>
```php
    /*登出API*/
    public function logout(){
      /*删除username*/
      session()->forget('username');
      /*删除user_id*/
      session()->forget('user_id');
      //session()->set('person.friend.hanmeimei.age','20');
      //session()->put('username',null);
      //session()->put('user_id',null);
      //dd(session()->all());
      return ['status'=>1];
    }
    /*检测用户是否登录*/
    public function is_logged_in(){
      /*如果session中存在user_id就返回user_id,否则返回false*/
      return session('user_id') ?: false;
    }
```
## 问题API的实现
### Question Migration的建立
`cd /root/project/xiaohu_source/xiaohu/`<br>
`php artisan make:migration create_table_questions --create=questions`<br>
`2017_12_30_011612_create_table_questions.php:`<br>
```php
public function up()
    {
        Schema::create('questions', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title',64);
            $table->text('desc')->nullable()->comment('description');
            $table->unsignedInteger('user_id');
            $table->string('status')->default('ok');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users');
        });
    }
```
`php artisan migrate`<br>
新建一个Question Model:`php artisan make:model Question`<br>
### 增加问题API的实现
路由建立:<br>
```php
/*增加问题API的建立*/
Route::any('api/question/add',function(){
    return question_ins()->add();
});
```
Question.php:<br>
```php
public function add(){
     /*检查用户是否登录*/
     if(!user_ins()->is_logged_in())
       return ['status'=>0,'msg'=>'login required'];

     /*检查是否存在标题*/
     if(!rq('title'))
       return ['status'=>0,'msg'=>'required title'];

     $this->title=rq('title');
     $this->user_id=session('user_id');
     if(rq('desc'))
       $this->desc=rq('desc');//如果存在描述就添加描述

     //保存
     return $this->save() ?
       ['status'=>1,'id'=>$this->id]:
       ['status'=>0,'msg'=>'db insert failed'];
}
```
### 更新问题API的实现
路由建立:<br>
```php
Route::any('api/question/change',function(){
    return question_ins()->change();
});
```
```php
    //更新问题API
    public function change(){
      //检查用户是否登录
      if(!user_ins()->is_logged_in())
        return ['status'=>0,'msg'=>'login required'];

      //检查传参中是否有id
      if(!rq('id'))
        return ['status'=>0,'msg'=>'id is required'];

      //获取指定id的model
      $question=$this->find(rq('id'));

      //判断问题是否存在
      if(!$question)
        return ['status'=>0,'msg'=>'question not exists'];

      if($question->user_id!=session('user_id'))
        return ['status'=>0,'msg'=>'permission denied'];

      if(rq('title'))
        $question->title=rq('title');
      if(rq('desc'))
        $question->desc=rq('desc');

      return $question->save() ?
        ['status'=>1]:
        ['status'=>0,'msg'=>'db insert failed'];
    }
```
### 查看问题API的实现
路由建立:<br>
```php
Route::any('api/question/read',function(){
    return question_ins()->read();
});
```
```php
 //查看问题API
    public function read(){
      //请求参数中是否有id,如果有id直接返回id所在的行
      if(rq('id'))
        return ['status'=>1,'data'=>$this->find(rq('id'))];

      //limit条件
      $limit=rq('limit') ?: 15;
      //skip条件,用于分页
      $skip=(rq('page') ? rq('page')-1 : 0)*$limit;

      //构建query并返回collection数据
      $r=$this
        ->orderBy('created_at')
        ->limit($limit)
        ->skip($skip)
        ->get(['id','title','desc','user_id','created_at','updated_at'])
        ->keyBy('id');

      return ['status'=>1,'data'=>$r];
    }
```
### 删除问题API的实现
路由建立:<br>
```php
Route::any('api/question/remove',function(){
    return question_ins()->remove();
});
```
```php
    //删除问题API
    public function remove(){
      //检查用户是否登录
      if(!user_ins()->is_logged_in())
        return ['status'=>0,'msg'=>'login required'];

      //检查传参中是否有id
      if(!rq('id'))
        return ['status'=>0,'msg'=>'id is required'];

      //获取传参id所对应的model
      $question=$this->find(rq('id'));
      if(!$question)
        return ['status'=>0,'question not exists'];

      //检查当前用户是否为问题所有者
      if(session('user_id')!=$question->user_id)
        return ['status'=>0,'permission denied'];

      return $question->delete() ?
        ['status'=>1]:
        ['status'=>0,'msg'=>'db delete failed'];
    }
```
## 回答API的实现
### migration的建立
`php artisan make:migration create_table_answers --create=answers`<br>
```php
 public function up()
    {
        Schema::create('answers', function (Blueprint $table) {
            $table->increments('id');
            $table->text('content');
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('question_id');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('question_id')->references('id')->on('questions');
        });
    }
```
生成表:`php artisan migrate`
新建model:`php artisan make:model Answer`
### 添加回答API的实现
```php
Route::any('api/answer/add',function(){
    return answer_ins()->add();
});
```
```php
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
```
### 更新回答API的实现
路由建立:<br>
```php
Route::any('api/answer/change',function(){
    return answer_ins()->change();
});
```
```php
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
```
### 查看回答API的实现
路由建立:<br>
```php
Route::any('api/answer/read',function(){
    return answer_ins()->read();
});
```
```php
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
```
## 评论API的实现
### migration的建立
`php artisan make:migration create_table_comments --create=comments`
```php
    public function up()
    {
        Schema::create('comments', function (Blueprint $table) {
            $table->increments('id');
            $table->text('content');
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('question_id')->nullable();
            $table->unsignedInteger('answer_id')->nullable();
            $table->unsignedInteger('reply_to')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('question_id')->references('id')->on('questions');
            $table->foreign('answer_id')->references('id')->on('answers');
            $table->foreign('reply_to')->references('id')->on('comments');
        });
    }
```
检查语法:`php artisan migrate --pretend`<br>
`php artisan migrate`<br>
建立model:`php artisan make:model Comment`<br>
### 添加评论API的实现
路由建立:<br>
```php
Route::any('api/comment/add',function(){
    return comment_ins()->add();
});
```
```php
    /*添加评论API*/
    public function add(){
      if(!user_ins()->is_logged_in())
        return ['status'=>0,'msg'=>'login required'];

      if(!rq('content'))
        return ['status'=>0,'msg'=>'empty content'];

      /*检查是否存在question_id或answer_id*/
      if(
        (!rq('question_id') && !rq('answer_id')) || //none
        (rq('question_id') && rq('answer_id')) //all
      )
        return ['status'=>0,'msg'=>'question_id or answer_id is required'];

      if(rq('question_id')){
        /*评论问题*/
        $question=question_ins()->find(rq('question_id'));
        /*检查问题是否存在*/
        if(!$question)
          return ['status'=>0,'msg'=>'question not exists'];
        $this->question_id=rq('question_id');
      }else{
        /*评论答案*/
        $answer=answer_ins()->find(rq('answer_id'));
        /*检查答案是否存在*/
        if(!$answer)
          return ['status'=>0,'msg'=>'answer not exists'];
        $this->answer_id=rq('answer_id');
      }

      /*检查是否在回复评论*/
      if(rq('reply_to')){
        $target=$this->find(rq('reply_to'));
        /*检查目标评论是否存在*/
        if(!$target)
          return ['status'=>0,'msg'=>'target comment not exists'];
        /*检查是否在回复自己的评论*/
        if($target->user_id==session('user_id'))
          return ['status'=>0,'cannot replay to yourself'];
        $this->reply_to=rq('reply_to');
      }

      $this->content=rq('content');
      $this->user_id=session('user_id');
      return $this->save() ?
        ['status'=>1,'id'=>$this->id] :
        ['status'=>0,'msg'=>'db insert failed'];
    }
```
### 查看评论API的实现
路由建立:<br>
```php
Route::any('api/comment/read',function(){
    return comment_ins()->read();
});
```
```php
    /*查看评论API的实现*/
    public function read(){
      if(!rq('question_id') && !rq('answer_id'))
        return ['status'=>0,'question_id or answer_id is required'];

      if(rq('question_id')){
        $question=question_ins()->find(rq('question_id'));
        if(!$question)
          return ['status'=>0,'question not exists'];
        $data=$this->where('question_id',rq('question_id'));
      }else{
        $answer=answer_ins()->find(rq('answer_id'));
        if(!$answer)
          return ['status'=>0,'answer not exists'];
        $data=$this->where('answer_id',rq('answer_id'));
      }

      $data=$data->get()->keyBy('id');
      return ['status'=>1,'data'=>$data];
    }
```
### 删除评论API的建立
路由建立:<br>
```php
Route::any('api/commnet/remove',function(){
    return comment_ins()->remove();
});
```
```php
    /*删除评论API的实现*/
    public function remove(){
      if(!user_ins()->is_logged_in())
        return ['status'=>0,'msg'=>'login required'];

      if(!rq('id'))
        return ['status'=>0,'msg'=>'id is required'];

      $comment=$this->find(rq('id'));
      if(!$comment)
        return ['status'=>0,'msg'=>'comment not exists'];

      if($comment->user_id != session('user_id'))
        return ['status'=>0,'msg'=>'permission denied'];

      //先删除此评论下所有的回复
      $this->where('reply_to',rq('id'))->delete();
      return $comment->delete() ?
        ['status'=>1]:
        ['status'=>0,'db delete failed'];
    }
```
## 通用API的实现
`php artisan make:migration create_table_answer_user --create=answer_user`
```php
    public function up()
    {
        Schema::create('answer_user', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('answer_id');
            $table->unsignedSmallInteger('vote');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('answer_id')->references('id')->on('answers');
            $table->unique(['user_id','answer_id','vote']);
        });
    }
```
`php artisan migrate`<br>
```php
Route::any('api/answer/vote',function(){
    return answer_ins()->vote();
});
```
Answer.php:<br>
```php
    public function users(){
        return $this
            ->belongsToMany('App\User')
            ->withPivot('vote')
            ->withTimestamps();
    }
```
User.php:<br>
```php
    public function answers(){
        return $this
            ->belongsToMany('App\Answer')
            ->withPivot('vote')
            ->withTimestamps();
    }
```
### 投票API
Answer.php:<br>
```php
  //投票API
  public function vote(){
    if(!user_ins()->is_logged_in())
      return ['status'=>0,'msg'=>'login required'];

    if(!rq('id') || !rq('vote'))
      return ['status'=>0,'msg'=>'id and vote are required'];

    $answer=$this->find(rq('id'));
    if(!$answer)
      return ['status'=>0,'msg'=>'answer not exists'];

    /*1赞同,2反对*/
    $vote=rq('vote')<=1 ?1:2;

    /*检查此用户是否在相同条件下投过票,如果投过票就删除投票*/
    $vote_ins=$answer->users()
      ->newPivotStatement()
      ->where('user_id',session('user_id'))
      ->where('answer_id',rq('id'))
      ->delete();

    $answer
      ->users()
      ->attach(session('user_id'),['vote'=>$vote]);

    return ['status'=>1];
  }
```
### 时间线API
`php artisan make:controller CommonController`<br>
路由建立:<br>
```php
//时间线API
Route::any('api/timeline','CommonController@timeline');
```
`cd xiaohu/app/Http/Controllers`<br>
`vim CommonController.php`<br>
```php
    /*时间线API*/
    public function timeline(){
      list($limit,$skip)=paginate(rq('page'),rq('limit'));

      /*获取问题数据*/
      $questions=question_ins()
        ->limit($limit)
        ->skip($skip)
        ->orderBy('created_at','desc')
        ->get();

      /*获取回答数据*/
      $answers=answer_ins()
        ->limit($limit)
        ->skip($skip)
        ->orderBy('created_at','desc')
        ->get();

      /*合并数据*/
      $data=$questions->merge($answers);
      /*将合并的数据按时间排序*/
      $data=$data->sortByDesc(function($item){
        return $item->created_at;
      });

      $data=$data->values()->all();

      return $data;
    }
```
### 修改密码API
路由建立:<br>
```php
Route::any('api/user/change_password',function(){
    return user_ins()->change_password();
});
```
```php
    //修改密码API
    public function change_password(){
      if(!$this->is_logged_in())
        return ['status'=>0,'msg'=>'login required'];

      if(!rq('old_password')||!rq('new_password'))
        return ['status'=>0,'msg'=>'old_password and new_password are required'];

      $user=$this->find(session('user_id'));

      if(!Hash::check(rq('old_password'),$user->password))
        return ['status'=>0,'msg'=>'invalid old_password'];

      $user->password=bcrypt(rq('new_password'));
      return $user->save() ?
        ['status'=>1]:
        ['status'=>0,'msg'=>'db update failed'];
    }
```
### 找回密码API
路由建立:<br>
```php
Route::any('api/user/reset_password',function(){
    return user_ins()->reset_password();
});
```
### 获取用户信息API
路由建立:<br>
```php
Route::any('api/user/read',function(){
    return user_ins()->read();
});
```
```php
    /*获取用户信息API*/
    public function read(){
      if(!rq('id'))
        return err('required id');

      $get=['id','username','avatar_url','intro'];
      $user=$this->find(rq('id'),$get);
      $data=$user->toArray();
      $answer_count=answer_ins()->where('user_id',rq('id'))->count();
      $question_count=question_ins()->where('user_id',rq('id'))->count();
      $data['answer_count']=$answer_count;
      $data['question_count']=$question_count;

      return suc($data);
    }
```
## Angular的安装及项目的部署
- 安装Angular<br>
- 安装ui-router<br>
- 安装normalize.css<br>
- 安装JQuery(可选)<br>
- 创建基础文件及基础页面<br>
安装npm`yum install nodejs(先要添加epel源)`<br>
查看版本:`npm -v`<br>
`cd public`<br>
`npm install angular jquery angular-ui-router normalize-css`<br>
`mv node_modules public/`<br>
`浏览器输入:localhost:8000/node_modules/angular/angular.js`<br>
### 首页建立
首页路由:<br>
`vim routes/web.php`<br>
```php
Route::get('/',function(){
    return view('index');
});
```
`建立首页页面: cd resources/views/`<br>
`vim index.blade.php`<br>
```html
<!doctype html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <title>晓乎</title>
    <link rel="stylesheet" href="\node_modules\normalize-css\normalize.css">
    <script src="\node_modules\jquery\dist\jquery.min.js"></script>
    <script src="\node_modules\angular\angular.min.js"></script>
    <script src="\node_modules\angular-ui-router\release\angular-ui-router.min.js"></script>
</head>
<body>
1234
</body>
<html>
```




