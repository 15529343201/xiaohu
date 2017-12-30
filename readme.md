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






