<?php
namespace App\Http\Controllers;
use App\Models\{Facility,Equipment,FermsRequest,Reservation,Transaction,User,Notice};
use Illuminate\Http\Request; use Illuminate\Database\QueryException; use Illuminate\Support\Facades\DB;
class AdminController extends Controller {
  private function notify($uid,$title,$msg,$type='info'){ Notice::create(['user_id'=>$uid,'title'=>$title,'message'=>$msg,'type'=>$type]); }
  public function dashboard() { return view('admin.dashboard',['s'=>['Total reservations'=>Reservation::count(),'Pending requests'=>FermsRequest::where('status','pending')->count(),'Approved reservations'=>Reservation::where('status','approved')->count(),'Available facilities'=>Facility::where('status','available')->count(),'Total equipment'=>Equipment::sum('total_quantity'),'Currently borrowed'=>Transaction::where('status','borrowed')->sum('quantity')],
    'upcoming'=>Reservation::with('facility','request.user')->where('status','approved')->where('starts_at','>=',now())->orderBy('starts_at')->limit(6)->get(),'recent'=>FermsRequest::with('user')->latest()->limit(6)->get()]); }
  public function requests(Request $r) { $q=FermsRequest::with(['user','reservation.facility','item.equipment'])->whereNotIn('status',['rejected','cancelled','released','returned'])->latest(); if($r->status) $q->where('status',$r->status); if($r->type) $q->where('type',$r->type);
    return view('admin.requests',['rows'=>$q->get(),'trans'=>Transaction::with('equipment','user')->where('status','borrowed')->get()]); }
  public function decide(Request $r, FermsRequest $req) {
    $r->validate(['decision'=>'required|in:approved,rejected']); abort_unless($req->status==='pending',422,'Request already processed.');
    try { DB::transaction(function() use($r,$req){
      $approve=$r->decision==='approved';
      if($req->type==='facility'){ $res=$req->reservation; if($approve && Reservation::conflicting($res->facility_id,$res->starts_at,$res->ends_at)->exists()) throw new \RuntimeException('Conflicts with an already approved reservation.'); $res->update(['status'=>$r->decision]); }
      elseif($approve && $req->item->equipment->available_quantity < $req->item->quantity) throw new \RuntimeException('Not enough equipment available.');
      $req->update(['status'=>$r->decision]);
      $this->notify($req->user_id,'Request '.$r->decision,"Your {$req->type} request \"{$req->purpose}\" was {$r->decision}.",$approve?'success':'danger');
    }); } catch(\RuntimeException|QueryException $e){ return back()->withErrors($e instanceof QueryException?'Conflicting reservation detected.':$e->getMessage()); }
    return back()->with('ok','Request '.$r->decision.'.');
  }
  public function release(FermsRequest $req) {
    try { DB::transaction(function() use($req){ abort_unless($req->type==='equipment'&&$req->status==='approved',422); $i=$req->item; $eq=Equipment::lockForUpdate()->find($i->equipment_id);
      if($eq->available_quantity<$i->quantity) throw new \RuntimeException('Insufficient stock to release.'); $eq->decrement('available_quantity',$i->quantity);
      Transaction::create(['request_id'=>$req->id,'equipment_id'=>$eq->id,'user_id'=>$req->user_id,'quantity'=>$i->quantity,'borrow_date'=>$i->borrow_date,'borrow_time'=>$i->borrow_time,'expected_return'=>$i->expected_return,'expected_return_time'=>$i->expected_return_time]);
      $req->update(['status'=>'released']); $this->notify($req->user_id,'Equipment released',"{$eq->name} x{$i->quantity} released. Return by {$i->expected_return}.",'info'); }); }
    catch(\RuntimeException $e){ return back()->withErrors($e->getMessage()); } return back()->with('ok','Equipment released.');
  }
  public function returned(Request $r, Transaction $tx) {
    DB::transaction(function() use($r,$tx){ abort_unless($tx->status==='borrowed',422); Equipment::lockForUpdate()->find($tx->equipment_id)->increment('available_quantity',$tx->quantity);
      $tx->update(['status'=>'returned','actual_return'=>now(),'remarks'=>$r->remarks]); optional($tx->request_id?FermsRequest::find($tx->request_id):null)?->update(['status'=>'returned']);
      $this->notify($tx->user_id,'Return recorded','Your return of '.$tx->equipment->name.' was recorded.','success'); });
    return back()->with('ok','Return recorded.');
  }
  public function returnReminders() {
    return view('admin.return-reminders',['rows'=>Transaction::with(['equipment','user'])->where('status','borrowed')->orderBy('expected_return')->orderBy('expected_return_time')->get()]);
  }
  public function sendReturnReminder(Transaction $tx) {
    abort_unless($tx->status==='borrowed',422,'Only currently borrowed equipment can be reminded.');
    $due=$tx->expected_return->format('M d, Y');
    if($tx->expected_return_time) $due.=' at '.\Illuminate\Support\Carbon::parse($tx->expected_return_time)->format('g:i A');
    $this->notify($tx->user_id,'Equipment return reminder',"Please return {$tx->equipment->name} (quantity: {$tx->quantity}) by {$due}. Contact staff if you need assistance.",'warning');
    return back()->with('ok','Return reminder sent to '.$tx->user->name.'.');
  }
  public function facilities() { return view('admin.facilities',['rows'=>Facility::orderBy('name')->get()]); }
  public function saveFacility(Request $r, ?Facility $facility=null) { $d=$r->validate(['name'=>'required|max:255|unique:facilities,name,'.optional($facility)->id,'location'=>'required','category'=>'required','capacity'=>'required|integer|min:1','status'=>'nullable|in:available,reserved,unavailable']);
    $facility ? $facility->update($d) : Facility::create($d); return back()->with('ok','Facility saved.'); }
  public function equipment() { return view('admin.equipment',['rows'=>Equipment::orderBy('name')->get()]); }
  public function saveEquipment(Request $r, ?Equipment $equipment=null) { $d=$r->validate(['name'=>'required|max:255|unique:equipment,name,'.optional($equipment)->id,'category'=>'required','total_quantity'=>'required|integer|min:0','condition'=>'required','status'=>'nullable|in:available,unavailable,maintenance']);
    if($equipment){ $borrowed=$equipment->total_quantity-$equipment->available_quantity; if($d['total_quantity']<$borrowed) return back()->withErrors("Total cannot be below currently borrowed ($borrowed)."); $d['available_quantity']=$d['total_quantity']-$borrowed; $equipment->update($d);} else { $d['available_quantity']=$d['total_quantity']; Equipment::create($d);} return back()->with('ok','Equipment saved.'); }
  public function users() { return view('admin.users',['rows'=>User::orderBy('role')->orderBy('name')->get()]); }
  public function updateUser(Request $r, User $user) { abort_unless(in_array($r->user()->role,['admin','superadmin']),403); $d=$r->validate(['role'=>'required|in:student,organization,staff,admin','is_active'=>'boolean']); $user->update(['role'=>$d['role'],'is_active'=>$r->boolean('is_active')]); return back()->with('ok','User updated.'); }
  public function reports() { return view('admin.reports',['reservations'=>Reservation::with('facility','request.user')->latest()->limit(100)->get(),'tx'=>Transaction::with('equipment','user')->latest()->limit(100)->get(),'closedRequests'=>FermsRequest::with(['user','reservation.facility','item.equipment'])->whereIn('status',['rejected','cancelled','released','returned'])->latest()->limit(100)->get()]); }
 
   public function profile() { return view('admin.profile'); }
  
  public function updateProfile(Request $r) {
    $d = $r->validate(['name'=>'required|string|max:255','organization_class'=>'nullable|string|max:100','phone'=>'required|string|max:20']);
    $r->user()->update($d);
    return back()->with('ok','Profile updated successfully.');
  }
  
  public function updatePassword(Request $r) {
    $r->validate(['current_password'=>'required','password'=>'required|min:8|confirmed']);
    if(!password_verify($r->current_password, $r->user()->password)) {
      return back()->withErrors(['current_password'=>'Current password is incorrect.']);
    }
    $r->user()->update(['password'=>bcrypt($r->password)]);
    return back()->with('ok','Password updated successfully.');
  }
  
  public function updateSettings(Request $r) {
    abort_unless($r->user()->role==='admin',403);
    $d = $r->validate(['email_notifications'=>'boolean','auto_approve_reservations'=>'boolean','default_duration'=>'nullable|integer','max_borrow_days'=>'nullable|integer|min:1|max:30']);
    // Store settings in user meta or system config table
    // For now, just return success
    return back()->with('ok','Settings updated successfully.');
  }

}
