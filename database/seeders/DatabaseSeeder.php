<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder; use App\Models\{User,Facility,Equipment};
class DatabaseSeeder extends Seeder { public function run(): void {
  foreach (['student'=>'Kenneth Sigua','organization'=>'Student Council','staff'=>'Maria Dela Peña','admin'=>'Ariel Amoguis'] as $r=>$n) User::firstOrCreate(['email'=>"$r@mcc.edu.ph"],['name'=>$n,'role'=>$r,'organization_class'=>$r==='student'?'BSIT 3B':null]);
  foreach ([['Gymnasium – Court A','Sports Complex','Courts',150],['AVR Room','Main Building','Rooms',40],['Conference Room 1','Admin Building','Rooms',16],['Cultural Hall','MCC Main','Halls',300],['Open Field','East Grounds','Outdoor',300]] as [$n,$l,$c,$cap]) Facility::firstOrCreate(['name'=>$n],['location'=>$l,'category'=>$c,'capacity'=>$cap]);
  foreach ([['Projector','AV',6],['Wireless Microphone','AV',4],['Badminton Set','Sports',12],['Folding Tables','Furniture',20]] as [$n,$c,$q]) Equipment::firstOrCreate(['name'=>$n],['category'=>$c,'total_quantity'=>$q,'available_quantity'=>$q]);
}}
