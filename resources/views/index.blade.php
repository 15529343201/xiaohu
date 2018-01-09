<!doctype html>
<html lang="zh" ng-app="xiaohu">
<head>
    <meta charset="UTF-8">
    <title>晓乎</title>
    <link rel="stylesheet" href="/node_modules/normalize-css/normalize.css">
    <link rel="stylesheet" href="/css/base.css">
    <script src="/node_modules/jquery/dist/jquery.min.js"></script>
    <script src="/node_modules/angular/angular.min.js"></script>
    <script src="/node_modules/angular-ui-router/release/angular-ui-router.min.js"></script>
    <script src="/js/base.js"></script>
</head>
<body>
<div class="navbar clearfix">
  <div class="container">
    <div class="fl">
      <div class="navbar-item brand">晓乎</div>
      <div class="navbar-item">
          <input type="text">
      </div>
    </div>
    <div class="fr">
      <a ui-sref="home" class="navbar-item">首页</a>
      <a ui-sref="login" class="navbar-item">登录</a>
      <a ui-sref="signup" class="navbar-item">注册</a>
    </div>
  </div>
</div>

<div class="page">
    <div ui-view><div>
</div>

</body>

<script type="text/ng-template" id="home.tpl">
    <div class="home container">
      首页
    </div>
</script>

<script type="text/ng-template" id="signup.tpl">
    <div class="home container">
      注册
    </div>
</script>

<script type="text/ng-template" id="login.tpl">
    <div class="home container">
      登录
    </div>
</script>
<html>
