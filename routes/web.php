<?php
use Illuminate\Support\Facades\Route; use App\Http\Controllers\{AuthController,StudentController as S,AdminController as A,SuperAdminController as SA}; use App\Http\Middleware\RoleMiddleware as R;
Route::get('/login',[AuthController::class,'show'])->name('login'); Route::get('/auth/microsoft',[AuthController::class,'redirect']); Route::get('/auth/microsoft/callback',[AuthController::class,'callback']);
Route::get('/dev-login/{role}',[AuthController::class,'dev']); Route::post('/logout',[AuthController::class,'logout'])->middleware('auth');
Route::middleware('auth')->get('/',function(){
  $user = auth()->user();
  if($user->role === 'superadmin') return redirect('/superadmin');
  return redirect($user->isAdminSide()?'/admin':'/dashboard');
});
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
  Route::get('/return-reminders',[A::class,'returnReminders']); Route::post('/transactions/{tx}/remind',[A::class,'sendReturnReminder']);
  Route::get('/facilities',[A::class,'facilities']); Route::post('/facilities/{facility?}',[A::class,'saveFacility']);
  Route::get('/equipment',[A::class,'equipment']); Route::post('/equipment/{equipment?}',[A::class,'saveEquipment']);
  Route::get('/reports',[A::class,'reports']); Route::get('/users',[A::class,'users']); Route::post('/users/{user}',[A::class,'updateUser']);
  Route::get('/profile',[A::class,'profile']); Route::post('/profile',[A::class,'updateProfile']); Route::post('/profile/password',[A::class,'updatePassword']); Route::post('/settings',[A::class,'updateSettings']);
});
Route::prefix('superadmin')->middleware(['auth',R::class.':superadmin'])->group(function(){
  Route::get('/',[SA::class,'dashboard']); Route::get('/settings',[SA::class,'systemSettings']); Route::post('/settings',[SA::class,'updateSystemSettings']);
  Route::get('/logs',[SA::class,'systemLogs']); Route::post('/logs/clear',[SA::class,'clearLogs']);
  Route::get('/users',[SA::class,'userManagement']); Route::post('/users/{user}/toggle',[SA::class,'toggleUserStatus']); Route::post('/users/{user}/role',[SA::class,'changeUserRole']);
  Route::get('/monitoring',[SA::class,'systemMonitoring']); Route::post('/clear-cache',[SA::class,'clearCache']); Route::post('/optimize',[SA::class,'optimizeDatabase']);
  Route::post('/backup',[SA::class,'backupDatabase']); Route::post('/maintenance-toggle',[SA::class,'toggleMaintenanceMode']);
});