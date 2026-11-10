<?php
use Illuminate\Support\Facades\Route; use App\Http\Controllers\{AuthController,StudentController as S,AdminController as A}; use App\Http\Middleware\RoleMiddleware as R;
Route::get('/login',[AuthController::class,'show'])->name('login'); Route::get('/auth/microsoft',[AuthController::class,'redirect']); Route::get('/auth/microsoft/callback',[AuthController::class,'callback']);
Route::get('/dev-login/{role}',[AuthController::class,'dev']); Route::post('/logout',[AuthController::class,'logout'])->middleware('auth');
Route::middleware('auth')->get('/',fn()=>redirect(auth()->user()->isAdminSide()?'/admin':'/dashboard'));
Route::middleware(['auth',R::class.':student,organization'])->group(function(){
  Route::get('/dashboard',[S::class,'dashboard']); Route::get('/facilities',[S::class,'facilities']); Route::post('/reservations',[S::class,'reserve']);
  Route::get('/equipment',[S::class,'equipment']); Route::post('/borrow',[S::class,'borrow']); Route::get('/requests',[S::class,'requests']);
  Route::post('/requests/{req}/edit',[S::class,'updateRequest']); Route::post('/requests/{req}/cancel',[S::class,'cancelRequest']);
  Route::get('/transactions',[S::class,'transactions']); Route::get('/notifications',[S::class,'notifications']);
  Route::get('/profile',[S::class,'profile']); Route::post('/profile',[S::class,'updateProfile']); Route::post('/profile/password',[S::class,'updatePassword']);
});
Route::prefix('admin')->middleware(['auth',R::class.':staff,admin'])->group(function(){
  Route::get('/',[A::class,'dashboard']); Route::get('/requests',[A::class,'requests']); Route::post('/requests/{req}/decide',[A::class,'decide']); Route::post('/requests/{req}/release',[A::class,'release']);
  Route::post('/transactions/{tx}/return',[A::class,'returned']);
  Route::get('/facilities',[A::class,'facilities']); Route::post('/facilities/{facility?}',[A::class,'saveFacility']);
  Route::get('/equipment',[A::class,'equipment']); Route::post('/equipment/{equipment?}',[A::class,'saveEquipment']);
  Route::get('/reports',[A::class,'reports']); Route::get('/users',[A::class,'users']); Route::post('/users/{user}',[A::class,'updateUser']);
  Route::get('/profile',[A::class,'profile']); Route::post('/profile',[A::class,'updateProfile']); Route::post('/profile/password',[A::class,'updatePassword']); Route::post('/settings',[A::class,'updateSettings']);
});