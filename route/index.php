<?php

use think\facade\Route;

//Route::allowCrossDomain();//解决跨域

//一般路由规则，访问的url为：v1/address/1,对应的文件为Address类下的read方法

Route::get('index/my','index/user_info/read');   //获取个人信息
Route::put('index/my','index/user_info/update'); //更新个人信息
Route::get('index/order_details/:id','index/order/read');
Route::put('index/order/:id','index/order/update');
Route::get('index/print/get_price/:id','index/print/price');
//Route::post('print/upload','index/print/upload'); //上传文件
//Route::get('print','index/print');
//Route::get('print','index/print');

Route::post('index/login','index/public/login');//登录路由
