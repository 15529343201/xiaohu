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
