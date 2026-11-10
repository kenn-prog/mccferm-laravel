<?php
namespace App\Http\Controllers;
use App\Models\{Facility,Equipment,FermsRequest,Reservation,EquipmentItem,Transaction,User,Notice};
use Illuminate\Http\Request; use Illuminate\Support\Carbon; use Illuminate\Support\Facades\DB; use Illuminate\Support\Facades\Hash;
class StudentController extends Controller {
  public function dashboard(Request $r) { $u=$r->user(); $q=$u->requests();
    return view('student.dashboard',['counts'=>['pending'=>(clone $q)->where('status','pending')->count(),'approved'=>(clone $q)->whereIn('status',['approved','released','returned'])->count(),'rejected'=>(clone $q)->where('status','rejected')->count()],
      'upcoming'=>Reservation::with('facility')->where('status','approved')->where('starts_at','>=',now())->whereHas('request',fn($x)=>$x->where('user_id',$u->id))->orderBy('starts_at')->limit(5)->get(),
      'borrowed'=>Transaction::with('equipment')->where('user_id',$u->id)->where('status','borrowed')->get(),'facilities'=>Facility::where('status','available')->count(),'equipment'=>Equipment::sum('available_quantity')]); }
  public function facilities() { return view('student.facilities',['facilities'=>Facility::orderBy('name')->get(),'booked'=>Reservation::with('facility')->where('status','approved')->where('ends_at','>=',now())->orderBy('starts_at')->get()]); }
  public function reserve(Request $r) {
    $d=$r->validate(['facility_id'=>'required|exists:facilities,id','date'=>'required|date|after_or_equal:today','start_time'=>'required|date_format:H:i','end_time'=>'required|date_format:H:i|after:start_time','purpose'=>'required|string|max:255','participants'=>'nullable|integer|min:1','notes'=>'nullable|string|max:1000']);
    $f=Facility::findOrFail($d['facility_id']); if($f->status!=='available') return back()->withErrors('That facility is not available for reservation.')->withInput();
    $s=Carbon::parse("{$d['date']} {$d['start_time']}"); $e=Carbon::parse("{$d['date']} {$d['end_time']}");
    if(Reservation::conflicting($f->id,$s,$e)->exists()) return back()->withErrors('That time slot conflicts with an approved reservation.')->withInput();
    DB::transaction(function() use($r,$d,$s,$e){ $q=FermsRequest::create(['user_id'=>$r->user()->id,'type'=>'facility','purpose'=>$d['purpose'],'participants'=>$d['participants']??null,'organization_class'=>$r->user()->organization_class,'notes'=>$d['notes']??null]);
      Reservation::create(['request_id'=>$q->id,'facility_id'=>$d['facility_id'],'starts_at'=>$s,'ends_at'=>$e]); });
    $redirect=redirect('/requests')->with('ok','Reservation request submitted.');
    return $r->boolean('request_followup')
      ? $redirect->with('request_reminder',true)
      : $redirect->with('request_followup','equipment');
  }
  public function equipment() { return view('student.equipment',['items'=>Equipment::orderBy('category')->orderBy('name')->get()]); }
  public function borrow(Request $r) {
    $d=$r->validate(['equipment_id'=>'required|exists:equipment,id','quantity'=>'required|integer|min:1','borrow_date'=>'required|date|after_or_equal:today','expected_return'=>'required|date|after_or_equal:borrow_date','purpose'=>'required|string|max:255']);
    $latestReturnDate=Carbon::parse($d['borrow_date'])->addDays(5);
    if(Carbon::parse($d['expected_return'])->greaterThan($latestReturnDate)) return back()->withErrors(['expected_return'=>'Expected return date must be no more than 5 days after the borrow date.'])->withInput();
    $eq=Equipment::findOrFail($d['equipment_id']); if($eq->status!=='available'||$d['quantity']>$eq->available_quantity) return back()->withErrors(['quantity'=>'Requested quantity is not available.'])->withInput();
    DB::transaction(function() use($r,$d){ $q=FermsRequest::create(['user_id'=>$r->user()->id,'type'=>'equipment','purpose'=>$d['purpose']]);
      EquipmentItem::create(['request_id'=>$q->id,'equipment_id'=>$d['equipment_id'],'quantity'=>$d['quantity'],'borrow_date'=>$d['borrow_date'],'expected_return'=>$d['expected_return']]); });
    $redirect=redirect('/requests')->with('ok','Borrowing request submitted.');
    return $r->boolean('request_followup')
      ? $redirect->with('request_reminder',true)
      : $redirect->with('request_followup','facility');
  }
  public function requests(Request $r) { return view('student.requests',['rows'=>$r->user()->requests()->where('status','pending')->with(['reservation.facility','item.equipment'])->latest()->get(),'facilities'=>Facility::orderBy('name')->get(),'items'=>Equipment::orderBy('name')->get()]); }
  public function updateRequest(Request $r, FermsRequest $req) {
    abort_unless($req->user_id===$r->user()->id && $req->status==='pending',404);

    if ($req->type==='facility') {
      $d=$r->validate(['facility_id'=>'required|exists:facilities,id','date'=>'required|date|after_or_equal:today','start_time'=>'required|date_format:H:i','end_time'=>'required|date_format:H:i|after:start_time','purpose'=>'required|string|max:255','participants'=>'nullable|integer|min:1','notes'=>'nullable|string|max:1000']);
      $facility=Facility::findOrFail($d['facility_id']);
      if ($facility->status!=='available' && $facility->id!==$req->reservation->facility_id) return back()->withErrors(['facility_id'=>'That facility is not available.'])->withInput();
      $startsAt=Carbon::parse("{$d['date']} {$d['start_time']}");
      $endsAt=Carbon::parse("{$d['date']} {$d['end_time']}");
      if (Reservation::conflicting($facility->id,$startsAt,$endsAt)->where('id','!=',$req->reservation->id)->exists()) return back()->withErrors(['start_time'=>'That time slot conflicts with an approved reservation.'])->withInput();

      DB::transaction(function() use($req,$d,$facility,$startsAt,$endsAt) {
        $req->update(['purpose'=>$d['purpose'],'participants'=>$d['participants']??null,'notes'=>$d['notes']??null]);
        $req->reservation->update(['facility_id'=>$facility->id,'starts_at'=>$startsAt,'ends_at'=>$endsAt]);
      });
    } elseif ($req->type==='equipment') {
      $d=$r->validate(['equipment_id'=>'required|exists:equipment,id','quantity'=>'required|integer|min:1','borrow_date'=>'required|date|after_or_equal:today','expected_return'=>'required|date|after_or_equal:borrow_date','purpose'=>'required|string|max:255']);
      if (Carbon::parse($d['expected_return'])->greaterThan(Carbon::parse($d['borrow_date'])->addDays(5))) return back()->withErrors(['expected_return'=>'Expected return date must be no more than 5 days after the borrow date.'])->withInput();
      $equipment=Equipment::findOrFail($d['equipment_id']);
      if ($equipment->status!=='available' || $d['quantity']>$equipment->available_quantity) return back()->withErrors(['quantity'=>'Requested quantity is not available.'])->withInput();

      DB::transaction(function() use($req,$d) {
        $req->update(['purpose'=>$d['purpose']]);
        $req->item->update(['equipment_id'=>$d['equipment_id'],'quantity'=>$d['quantity'],'borrow_date'=>$d['borrow_date'],'expected_return'=>$d['expected_return']]);
      });
    } else {
      abort(404);
    }

    return back()->with('ok','Request updated.');
  }
  public function cancelRequest(Request $r, FermsRequest $req) {
    abort_unless($req->user_id===$r->user()->id && $req->status==='pending',404);
    DB::transaction(function() use($req) {
      $req->update(['status'=>'cancelled']);
      if ($req->type==='facility') $req->reservation?->update(['status'=>'cancelled']);
    });
    return back()->with('ok','Request cancelled.');
  }
  public function transactions(Request $r) {
    return view('student.transactions',[
      'rows'=>$r->user()->requests()->where('status','!=','pending')->with(['reservation.facility','item.equipment'])->latest()->get(),
    ]);
  }
  public function notifications(Request $r) { $n=$r->user()->notices()->latest()->get(); $r->user()->notices()->update(['is_read'=>true]); return view('student.notifications',['rows'=>$n]); }
  public function profile(Request $r) { $u=$r->user(); return view('student.profile',['requestCount'=>$u->requests()->count(),'borrowedCount'=>Transaction::where('user_id',$u->id)->where('status','borrowed')->sum('quantity')]); }
  public function updateProfile(Request $r) { $d=$r->validate(['name'=>'required|string|max:255','organization_class'=>'nullable|string|max:100','phone'=>'nullable|string|max:20']); $r->user()->update($d); return back()->with('ok','Profile updated.'); }
  public function updatePassword(Request $r) {
    $r->validate(['current_password'=>'required','password'=>'required|min:8|confirmed']);
    if (!Hash::check($r->current_password, $r->user()->password ?? '')) {
      return back()->withErrors(['current_password'=>'Current password is incorrect.']);
    }
    $r->user()->update(['password'=>Hash::make($r->password)]);
    return back()->with('ok','Password updated successfully.');
  }
}
